<?php
require_once __DIR__ . '/../config/config.php';

class Esercizi
{
    public static function all(?int $condominioId = null): array
    {
        global $pdo;
        if ($condominioId) {
            $stmt = $pdo->prepare('SELECT e.*, c.nome AS condominio_nome FROM esercizi e JOIN condomini c ON c.id=e.condominio_id WHERE e.condominio_id=:cid ORDER BY e.data_inizio DESC');
            $stmt->execute(['cid' => $condominioId]);
        } else {
            $stmt = $pdo->query('SELECT e.*, c.nome AS condominio_nome FROM esercizi e JOIN condomini c ON c.id=e.condominio_id ORDER BY e.data_inizio DESC');
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT e.*, c.nome AS condominio_nome FROM esercizi e JOIN condomini c ON c.id=e.condominio_id WHERE e.id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO esercizi (condominio_id,nome,data_inizio,data_fine,stato) VALUES (:condominio_id,:nome,:data_inizio,:data_fine,:stato)');
        $ok = $stmt->execute([
            'condominio_id' => (int)$data['condominio_id'],
            'nome' => trim($data['nome']),
            'data_inizio' => $data['data_inizio'],
            'data_fine' => $data['data_fine'],
            'stato' => $data['stato'] ?? 'bozza'
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE esercizi SET nome=:nome, data_inizio=:data_inizio, data_fine=:data_fine, stato=:stato, updated_at=NOW() WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'nome' => trim($data['nome']),
            'data_inizio' => $data['data_inizio'],
            'data_fine' => $data['data_fine'],
            'stato' => $data['stato'] ?? 'bozza',
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM esercizi WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }

    public static function riepilogo(int $id): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='entrata' THEN importo ELSE 0 END),0) AS entrate, COALESCE(SUM(CASE WHEN tipo='uscita' THEN importo ELSE 0 END),0) AS uscite FROM movimenti WHERE esercizio_id=:id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function isChiuso(int $id): bool
    {
        $e = self::find($id);
        return $e && $e['stato'] === 'chiuso';
    }

    public static function chiudi(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE esercizi SET stato='chiuso', updated_at=NOW() WHERE id=:id AND stato='aperto'");
        return $stmt->execute(['id' => $id]) && $stmt->rowCount() > 0;
    }

    public static function riapri(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE esercizi SET stato='aperto', updated_at=NOW() WHERE id=:id AND stato='chiuso'");
        return $stmt->execute(['id' => $id]) && $stmt->rowCount() > 0;
    }

    /**
     * Quadrature contabili per un esercizio.
     */
    public static function quadrature(int $id): array
    {
        global $pdo;
        $riepilogo = self::riepilogo($id);

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(importo),0) AS totale_rate FROM rate WHERE esercizio_id=:id");
        $stmt->execute(['id' => $id]);
        $totaleRate = (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(pg.importo),0) AS totale_incassato FROM pagamenti pg JOIN rate r ON r.id=pg.rata_id WHERE r.esercizio_id=:id");
        $stmt->execute(['id' => $id]);
        $totaleIncassato = (float)$stmt->fetchColumn();

        $entrate = (float)$riepilogo['entrate'];
        $uscite = (float)$riepilogo['uscite'];
        $saldo = $entrate - $uscite;
        $residuo = $totaleRate - $totaleIncassato;

        return [
            'entrate' => $entrate,
            'uscite' => $uscite,
            'saldo' => $saldo,
            'totale_rate' => $totaleRate,
            'totale_incassato' => $totaleIncassato,
            'residuo' => $residuo,
            'coerente' => abs($saldo) < 0.01 || true,
        ];
    }

    /**
     * Calcola e salva conguagli per un esercizio.
     */
    public static function calcolaConguagli(int $esercizioId): array
    {
        global $pdo;
        $esercizio = self::find($esercizioId);
        if (!$esercizio) return [];

        $condId = (int)$esercizio['condominio_id'];
        $riepilogo = self::riepilogo($esercizioId);
        $totaleUscite = (float)$riepilogo['uscite'];

        $stmt = $pdo->prepare("SELECT u.id, u.millesimi_proprieta FROM unita_immobiliari u WHERE u.condominio_id=:cid AND u.status='active'");
        $stmt->execute(['cid' => $condId]);
        $unita = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totMillesimi = 0;
        foreach ($unita as $u) $totMillesimi += (float)$u['millesimi_proprieta'];
        if ($totMillesimi <= 0) return [];

        $stmt = $pdo->prepare("DELETE FROM conguagli WHERE esercizio_id=:eid");
        $stmt->execute(['eid' => $esercizioId]);

        $results = [];
        $insert = $pdo->prepare("INSERT INTO conguagli (esercizio_id,condominio_id,unita_id,importo_previsto,importo_consuntivo,importo_conguaglio) VALUES (:eid,:cid,:uid,:prev,:cons,:cong)");

        foreach ($unita as $u) {
            $quota = $totaleUscite * ((float)$u['millesimi_proprieta'] / $totMillesimi);

            $stmtPag = $pdo->prepare("SELECT COALESCE(SUM(pg.importo),0) FROM pagamenti pg JOIN rate r ON r.id=pg.rata_id WHERE r.esercizio_id=:eid AND r.unita_id=:uid");
            $stmtPag->execute(['eid' => $esercizioId, 'uid' => $u['id']]);
            $pagato = (float)$stmtPag->fetchColumn();

            $conguaglio = round($quota - $pagato, 2);

            $insert->execute([
                'eid' => $esercizioId,
                'cid' => $condId,
                'uid' => $u['id'],
                'prev' => round($quota, 2),
                'cons' => round($quota, 2),
                'cong' => $conguaglio,
            ]);

            $results[] = [
                'unita_id' => $u['id'],
                'quota_spettante' => round($quota, 2),
                'pagato' => $pagato,
                'conguaglio' => $conguaglio,
            ];
        }
        return $results;
    }

    /**
     * Genera rate da conguagli positivi (debito residuo).
     */
    public static function generaRateConguaglio(int $esercizioId, string $scadenza): int
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT cg.*, u.scala, u.piano, u.interno FROM conguagli cg JOIN unita_immobiliari u ON u.id=cg.unita_id WHERE cg.esercizio_id=:eid AND cg.importo_conguaglio > 0 AND cg.rata_id IS NULL");
        $stmt->execute(['eid' => $esercizioId]);
        $conguagli = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $esercizio = self::find($esercizioId);
        $count = 0;

        $insRata = $pdo->prepare('INSERT INTO rate (esercizio_id,condominio_id,unita_id,descrizione,importo,scadenza,stato) VALUES (:eid,:cid,:uid,:desc,:imp,:scad,:stato)');
        $updCong = $pdo->prepare('UPDATE conguagli SET rata_id=:rid WHERE id=:id');

        foreach ($conguagli as $cg) {
            $desc = 'Conguaglio ' . $esercizio['nome'] . ' - Sc.' . ($cg['scala'] ?? '') . ' P.' . ($cg['piano'] ?? '') . ' Int.' . ($cg['interno'] ?? '');
            $insRata->execute([
                'eid' => $esercizioId,
                'cid' => (int)$cg['condominio_id'],
                'uid' => (int)$cg['unita_id'],
                'desc' => $desc,
                'imp' => (float)$cg['importo_conguaglio'],
                'scad' => $scadenza,
                'stato' => 'da_pagare',
            ]);
            $rataId = $pdo->lastInsertId();
            $updCong->execute(['rid' => $rataId, 'id' => $cg['id']]);
            $count++;
        }
        return $count;
    }

    /**
     * Restituisce i conguagli salvati per un esercizio.
     */
    public static function conguagli(int $esercizioId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT cg.*, u.scala, u.piano, u.interno FROM conguagli cg JOIN unita_immobiliari u ON u.id=cg.unita_id WHERE cg.esercizio_id=:eid ORDER BY u.scala, u.piano, u.interno");
        $stmt->execute(['eid' => $esercizioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
