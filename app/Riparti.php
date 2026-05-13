<?php
require_once __DIR__ . '/../config/config.php';

class Riparti
{
    public static function all(?int $condominioId = null): array
    {
        global $pdo;
        $sql = 'SELECT r.*, c.nome AS condominio_nome, e.nome AS esercizio_nome
                FROM riparti r
                JOIN condomini c ON c.id = r.condominio_id
                JOIN esercizi e ON e.id = r.esercizio_id';
        $params = [];
        if ($condominioId) {
            $sql .= ' WHERE r.condominio_id = :cid';
            $params['cid'] = $condominioId;
        }
        $sql .= ' ORDER BY r.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT r.*, c.nome AS condominio_nome, e.nome AS esercizio_nome
                               FROM riparti r
                               JOIN condomini c ON c.id = r.condominio_id
                               JOIN esercizi e ON e.id = r.esercizio_id
                               WHERE r.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO riparti (condominio_id, esercizio_id, descrizione, tipo_millesimi, importo_totale, tipo_spesa, stato, num_rate, note)
                               VALUES (:condominio_id, :esercizio_id, :descrizione, :tipo_millesimi, :importo_totale, :tipo_spesa, :stato, :num_rate, :note)');
        $ok = $stmt->execute([
            'condominio_id' => (int)$data['condominio_id'],
            'esercizio_id' => (int)$data['esercizio_id'],
            'descrizione' => trim($data['descrizione']),
            'tipo_millesimi' => $data['tipo_millesimi'] ?? 'proprieta',
            'importo_totale' => (float)$data['importo_totale'],
            'tipo_spesa' => $data['tipo_spesa'] ?? 'ordinaria',
            'stato' => 'bozza',
            'num_rate' => max(1, (int)($data['num_rate'] ?? 1)),
            'note' => trim($data['note'] ?? '')
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE riparti SET descrizione = :descrizione, tipo_millesimi = :tipo_millesimi,
                               importo_totale = :importo_totale, tipo_spesa = :tipo_spesa, num_rate = :num_rate, note = :note,
                               updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'descrizione' => trim($data['descrizione']),
            'tipo_millesimi' => $data['tipo_millesimi'] ?? 'proprieta',
            'importo_totale' => (float)$data['importo_totale'],
            'tipo_spesa' => $data['tipo_spesa'] ?? 'ordinaria',
            'num_rate' => max(1, (int)($data['num_rate'] ?? 1)),
            'note' => trim($data['note'] ?? '')
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM riparti WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function updateStato(int $id, string $stato): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE riparti SET stato = :stato, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id, 'stato' => $stato]);
    }

    /**
     * Calcola il riparto: per ogni unita attiva del condominio, determina la quota
     * in base alla tabella millesimale scelta e salva i dettagli.
     */
    public static function calcola(int $ripartoId): bool
    {
        global $pdo;
        $riparto = self::find($ripartoId);
        if (!$riparto) {
            return false;
        }

        $colonna = self::colonnaMillesimi($riparto['tipo_millesimi']);

        $stmt = $pdo->prepare("SELECT id, {$colonna} AS millesimi FROM unita_immobiliari WHERE condominio_id = :cid AND status = 'active' ORDER BY scala, piano, interno");
        $stmt->execute(['cid' => (int)$riparto['condominio_id']]);
        $unita = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($unita)) {
            return false;
        }

        $totaleMillesimi = 0;
        foreach ($unita as $u) {
            $totaleMillesimi += (float)$u['millesimi'];
        }

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM riparti_dettaglio WHERE riparto_id = :rid');
            $del->execute(['rid' => $ripartoId]);

            $ins = $pdo->prepare('INSERT INTO riparti_dettaglio (riparto_id, unita_id, millesimi, importo) VALUES (:rid, :uid, :mill, :imp)');
            $importoTotale = (float)$riparto['importo_totale'];
            $sommaImporti = 0;

            foreach ($unita as $i => $u) {
                $mill = (float)$u['millesimi'];
                if ($totaleMillesimi > 0) {
                    $quota = round($importoTotale * $mill / $totaleMillesimi, 2);
                } else {
                    $quota = round($importoTotale / count($unita), 2);
                }
                if ($i === count($unita) - 1) {
                    $quota = round($importoTotale - $sommaImporti, 2);
                }
                $sommaImporti += $quota;
                $ins->execute([
                    'rid' => $ripartoId,
                    'uid' => (int)$u['id'],
                    'mill' => $mill,
                    'imp' => $quota
                ]);
            }

            self::updateStato($ripartoId, 'calcolato');
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function getDettaglio(int $ripartoId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT rd.*, ui.scala, ui.piano, ui.interno, ui.descrizione AS unita_desc,
                               ui.millesimi_proprieta, ui.millesimi_scale, ui.millesimi_ascensore, ui.millesimi_riscaldamento
                               FROM riparti_dettaglio rd
                               JOIN unita_immobiliari ui ON ui.id = rd.unita_id
                               WHERE rd.riparto_id = :rid
                               ORDER BY ui.scala, ui.piano, ui.interno');
        $stmt->execute(['rid' => $ripartoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Genera le rate dal riparto calcolato.
     * Suddivide l'importo di ogni unita in N rate con scadenze mensili a partire dalla data indicata.
     */
    public static function generaRate(int $ripartoId, string $dataInizioScadenza): bool
    {
        global $pdo;
        $riparto = self::find($ripartoId);
        if (!$riparto || !in_array($riparto['stato'], ['calcolato', 'approvato'])) {
            return false;
        }

        $dettaglio = self::getDettaglio($ripartoId);
        if (empty($dettaglio)) {
            return false;
        }

        $numRate = max(1, (int)$riparto['num_rate']);

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO rate (esercizio_id, condominio_id, unita_id, descrizione, importo, scadenza, stato)
                                  VALUES (:eid, :cid, :uid, :desc, :imp, :scad, :stato)');

            foreach ($dettaglio as $d) {
                $importoUnita = (float)$d['importo'];
                $importoRata = round($importoUnita / $numRate, 2);
                $somma = 0;

                for ($r = 0; $r < $numRate; $r++) {
                    $scadenza = date('Y-m-d', strtotime($dataInizioScadenza . " +{$r} months"));
                    $imp = ($r === $numRate - 1) ? round($importoUnita - $somma, 2) : $importoRata;
                    $somma += $imp;

                    $unitaLabel = trim(($d['scala'] ?? '') . ' ' . ($d['piano'] ?? '') . ' ' . ($d['interno'] ?? ''));
                    $desc = $riparto['descrizione'] . ' - ' . ($unitaLabel ?: 'Unita ' . $d['unita_id']);
                    if ($numRate > 1) {
                        $desc .= ' (rata ' . ($r + 1) . '/' . $numRate . ')';
                    }

                    $ins->execute([
                        'eid' => (int)$riparto['esercizio_id'],
                        'cid' => (int)$riparto['condominio_id'],
                        'uid' => (int)$d['unita_id'],
                        'desc' => $desc,
                        'imp' => $imp,
                        'scad' => $scadenza,
                        'stato' => 'da_pagare'
                    ]);
                }
            }

            self::updateStato($ripartoId, 'rate_generate');
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Riepilogo millesimi per condominio: totale per ogni colonna millesimale.
     */
    public static function riepilogoMillesimi(int $condominioId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT
            SUM(millesimi_proprieta) AS tot_proprieta,
            SUM(millesimi_scale) AS tot_scale,
            SUM(millesimi_ascensore) AS tot_ascensore,
            SUM(millesimi_riscaldamento) AS tot_riscaldamento,
            COUNT(*) AS num_unita
            FROM unita_immobiliari WHERE condominio_id = :cid AND status = 'active'");
        $stmt->execute(['cid' => $condominioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private static function colonnaMillesimi(string $tipo): string
    {
        $map = [
            'proprieta' => 'millesimi_proprieta',
            'scale' => 'millesimi_scale',
            'ascensore' => 'millesimi_ascensore',
            'riscaldamento' => 'millesimi_riscaldamento',
            'personalizzato' => 'millesimi_proprieta',
        ];
        return $map[$tipo] ?? 'millesimi_proprieta';
    }
}
