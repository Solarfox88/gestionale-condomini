<?php
require_once __DIR__ . '/../config/config.php';

class Tickets
{
    public static function all(array $filters = []): array
    {
        global $pdo;
        $sql = 'SELECT t.*, c.nome AS condominio_nome, u.name AS autore_nome
                FROM ticket t
                JOIN condomini c ON c.id=t.condominio_id
                JOIN users u ON u.id=t.aperto_da';
        $where = [];
        $params = [];
        if (!empty($filters['condominio_id'])) {
            $where[] = 't.condominio_id=:cid';
            $params['cid'] = (int)$filters['condominio_id'];
        }
        if (!empty($filters['stato'])) {
            $where[] = 't.stato=:stato';
            $params['stato'] = $filters['stato'];
        }
        if (!empty($filters['priorita'])) {
            $where[] = 't.priorita=:priorita';
            $params['priorita'] = $filters['priorita'];
        }
        if (!empty($filters['aperto_da'])) {
            $where[] = 't.aperto_da=:uid';
            $params['uid'] = (int)$filters['aperto_da'];
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.updated_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT t.*, c.nome AS condominio_nome, u.name AS autore_nome, pa.nome AS assegnato_nome, pa.cognome AS assegnato_cognome FROM ticket t JOIN condomini c ON c.id=t.condominio_id JOIN users u ON u.id=t.aperto_da LEFT JOIN persone pa ON pa.id=t.assegnato_a WHERE t.id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE ticket SET assegnato_a=:assegnato_a, categoria=:categoria, priorita=:priorita, stato=:stato, updated_at=NOW() WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'assegnato_a' => !empty($data['assegnato_a']) ? (int)$data['assegnato_a'] : null,
            'categoria' => trim($data['categoria'] ?? ''),
            'priorita' => $data['priorita'] ?? 'media',
            'stato' => $data['stato'] ?? 'aperto',
        ]);
    }

    public static function updateStatus(int $id, string $stato): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE ticket SET stato=:stato, updated_at=NOW() WHERE id=:id');
        return $stmt->execute(['stato' => $stato, 'id' => $id]);
    }

    public static function getMessaggi(int $ticketId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT tm.*, u.name AS autore_nome FROM ticket_messaggi tm JOIN users u ON u.id=tm.user_id WHERE tm.ticket_id=:tid ORDER BY tm.created_at ASC');
        $stmt->execute(['tid' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addMessaggio(int $ticketId, int $userId, string $messaggio, bool $interno = false): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO ticket_messaggi (ticket_id,user_id,messaggio,interno) VALUES (:tid,:uid,:msg,:int)');
        $ok = $stmt->execute([
            'tid' => $ticketId,
            'uid' => $userId,
            'msg' => trim($messaggio),
            'int' => $interno ? 1 : 0,
        ]);
        if ($ok) {
            $pdo->prepare('UPDATE ticket SET updated_at=NOW() WHERE id=:id')->execute(['id' => $ticketId]);
        }
        return $ok;
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM ticket WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }
}
