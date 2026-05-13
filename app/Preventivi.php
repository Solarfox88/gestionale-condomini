<?php
require_once __DIR__ . '/../config/config.php';

class Preventivi
{
    public static function all(array $filters = []): array
    {
        global $pdo;
        $sql = 'SELECT p.*, c.nome AS condominio_nome, e.nome AS esercizio_nome
                FROM preventivi p
                JOIN condomini c ON c.id=p.condominio_id
                JOIN esercizi e ON e.id=p.esercizio_id';
        $where = [];
        $params = [];
        if (!empty($filters['condominio_id'])) {
            $where[] = 'p.condominio_id=:cid';
            $params['cid'] = (int)$filters['condominio_id'];
        }
        if (!empty($filters['esercizio_id'])) {
            $where[] = 'p.esercizio_id=:eid';
            $params['eid'] = (int)$filters['esercizio_id'];
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT p.*, c.nome AS condominio_nome, e.nome AS esercizio_nome
                               FROM preventivi p
                               JOIN condomini c ON c.id=p.condominio_id
                               JOIN esercizi e ON e.id=p.esercizio_id
                               WHERE p.id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO preventivi (esercizio_id,condominio_id,titolo,stato,note)
                               VALUES (:esercizio_id,:condominio_id,:titolo,:stato,:note)');
        $ok = $stmt->execute([
            'esercizio_id' => (int)$data['esercizio_id'],
            'condominio_id' => (int)$data['condominio_id'],
            'titolo' => trim($data['titolo']),
            'stato' => $data['stato'] ?? 'bozza',
            'note' => trim($data['note'] ?? ''),
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE preventivi SET titolo=:titolo, stato=:stato, note=:note, updated_at=NOW() WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'titolo' => trim($data['titolo']),
            'stato' => $data['stato'] ?? 'bozza',
            'note' => trim($data['note'] ?? ''),
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM preventivi WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }

    public static function voci(int $preventivoId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT v.*, cs.nome AS categoria_nome
                               FROM preventivo_voci v
                               LEFT JOIN categorie_spesa cs ON cs.id=v.categoria_id
                               WHERE v.preventivo_id=:pid ORDER BY v.tipo ASC, v.descrizione ASC');
        $stmt->execute(['pid' => $preventivoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addVoce(int $preventivoId, array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO preventivo_voci (preventivo_id,categoria_id,descrizione,tipo,importo_previsto)
                               VALUES (:preventivo_id,:categoria_id,:descrizione,:tipo,:importo_previsto)');
        $ok = $stmt->execute([
            'preventivo_id' => $preventivoId,
            'categoria_id' => !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            'descrizione' => trim($data['descrizione']),
            'tipo' => $data['tipo'] ?? 'uscita',
            'importo_previsto' => (float)$data['importo_previsto'],
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function updateVoce(int $voceId, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE preventivo_voci SET categoria_id=:categoria_id, descrizione=:descrizione, tipo=:tipo, importo_previsto=:importo_previsto WHERE id=:id');
        return $stmt->execute([
            'id' => $voceId,
            'categoria_id' => !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            'descrizione' => trim($data['descrizione']),
            'tipo' => $data['tipo'] ?? 'uscita',
            'importo_previsto' => (float)$data['importo_previsto'],
        ]);
    }

    public static function deleteVoce(int $voceId): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM preventivo_voci WHERE id=:id');
        return $stmt->execute(['id' => $voceId]);
    }

    public static function totali(int $preventivoId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT
            COALESCE(SUM(CASE WHEN tipo='entrata' THEN importo_previsto ELSE 0 END),0) AS entrate_previste,
            COALESCE(SUM(CASE WHEN tipo='uscita' THEN importo_previsto ELSE 0 END),0) AS uscite_previste
            FROM preventivo_voci WHERE preventivo_id=:pid");
        $stmt->execute(['pid' => $preventivoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findByEsercizio(int $esercizioId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT p.*, c.nome AS condominio_nome, e.nome AS esercizio_nome
                               FROM preventivi p
                               JOIN condomini c ON c.id=p.condominio_id
                               JOIN esercizi e ON e.id=p.esercizio_id
                               WHERE p.esercizio_id=:eid LIMIT 1');
        $stmt->execute(['eid' => $esercizioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Calcola consuntivo da movimenti reali raggruppati per categoria.
     */
    public static function consuntivo(int $esercizioId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT
            cs.id AS categoria_id, cs.nome AS categoria_nome,
            COALESCE(SUM(CASE WHEN m.tipo='entrata' THEN m.importo ELSE 0 END),0) AS entrate_reali,
            COALESCE(SUM(CASE WHEN m.tipo='uscita' THEN m.importo ELSE 0 END),0) AS uscite_reali
            FROM movimenti m
            LEFT JOIN categorie_spesa cs ON cs.id=m.categoria_id
            WHERE m.esercizio_id=:eid
            GROUP BY cs.id, cs.nome
            ORDER BY cs.nome");
        $stmt->execute(['eid' => $esercizioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Confronto preventivo vs consuntivo per un esercizio.
     */
    public static function confronto(int $esercizioId): array
    {
        $preventivo = self::findByEsercizio($esercizioId);
        $vociPrev = $preventivo ? self::voci($preventivo['id']) : [];
        $consuntivo = self::consuntivo($esercizioId);

        $catPrev = [];
        foreach ($vociPrev as $v) {
            $catId = $v['categoria_id'] ?? 0;
            if (!isset($catPrev[$catId])) {
                $catPrev[$catId] = ['categoria_nome' => $v['categoria_nome'] ?? 'Senza categoria', 'entrate_previste' => 0, 'uscite_previste' => 0];
            }
            if ($v['tipo'] === 'entrata') {
                $catPrev[$catId]['entrate_previste'] += (float)$v['importo_previsto'];
            } else {
                $catPrev[$catId]['uscite_previste'] += (float)$v['importo_previsto'];
            }
        }

        $catCons = [];
        foreach ($consuntivo as $c) {
            $catId = $c['categoria_id'] ?? 0;
            $catCons[$catId] = $c;
        }

        $allCats = array_unique(array_merge(array_keys($catPrev), array_keys($catCons)));
        sort($allCats);

        $result = [];
        foreach ($allCats as $catId) {
            $nome = $catPrev[$catId]['categoria_nome']
                    ?? $catCons[$catId]['categoria_nome']
                    ?? 'Senza categoria';
            $ep = $catPrev[$catId]['entrate_previste'] ?? 0;
            $up = $catPrev[$catId]['uscite_previste'] ?? 0;
            $er = (float)($catCons[$catId]['entrate_reali'] ?? 0);
            $ur = (float)($catCons[$catId]['uscite_reali'] ?? 0);
            $result[] = [
                'categoria_id' => $catId,
                'categoria_nome' => $nome,
                'entrate_previste' => $ep,
                'uscite_previste' => $up,
                'entrate_reali' => $er,
                'uscite_reali' => $ur,
                'scostamento_entrate' => $er - $ep,
                'scostamento_uscite' => $ur - $up,
            ];
        }
        return $result;
    }
}
