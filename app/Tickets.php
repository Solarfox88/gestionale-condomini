<?php
require_once __DIR__ . '/../config/config.php';

class Tickets
{
    public static function all(?int $condominioId = null): array
    {
        global $pdo;
        $sql = 'SELECT t.*, c.nome AS condominio_nome, u.name AS autore_nome FROM ticket t JOIN condomini c ON c.id=t.condominio_id JOIN users u ON u.id=t.aperto_da';
        $params = [];
        if ($condominioId) {
            $sql .= ' WHERE t.condominio_id=:cid';
            $params['cid'] = $condominioId;
        }
        $sql .= ' ORDER BY t.updated_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data, int $userId)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO ticket (condominio_id,unita_id,aperto_da,assegnato_a,titolo,categoria,descrizione,priorita,stato) VALUES (:condominio_id,:unita_id,:aperto_da,:assegnato_a,:titolo,:categoria,:descrizione,:priorita,:stato)');
        $ok = $stmt->execute([
            'condominio_id' => (int)$data['condominio_id'],
            'unita_id' => !empty($data['unita_id']) ? (int)$data['unita_id'] : null,
            'aperto_da' => $userId,
            'assegnato_a' => !empty($data['assegnato_a']) ? (int)$data['assegnato_a'] : null,
            'titolo' => trim($data['titolo']),
            'categoria' => trim($data['categoria'] ?? ''),
            'descrizione' => trim($data['descrizione']),
            'priorita' => $data['priorita'] ?? 'media',
            'stato' => $data['stato'] ?? 'aperto'
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function updateStatus(int $id, string $stato): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE ticket SET stato=:stato WHERE id=:id');
        return $stmt->execute(['stato' => $stato, 'id' => $id]);
    }
}
