<?php
require_once __DIR__ . '/../config/config.php';

class Pagamenti
{
    public static function all(array $filters = []): array
    {
        global $pdo;
        $sql = 'SELECT pg.*, r.descrizione AS rata_descrizione, r.importo AS rata_importo, r.scadenza,
                c.nome AS condominio_nome, ui.scala, ui.piano, ui.interno,
                p.nome AS persona_nome, p.cognome AS persona_cognome
                FROM pagamenti pg
                JOIN rate r ON r.id=pg.rata_id
                JOIN condomini c ON c.id=r.condominio_id
                JOIN unita_immobiliari ui ON ui.id=r.unita_id
                LEFT JOIN persone p ON p.id=pg.persona_id';
        $where = [];
        $params = [];
        if (!empty($filters['condominio_id'])) {
            $where[] = 'r.condominio_id=:cid';
            $params['cid'] = (int)$filters['condominio_id'];
        }
        if (!empty($filters['unita_id'])) {
            $where[] = 'r.unita_id=:uid';
            $params['uid'] = (int)$filters['unita_id'];
        }
        if (!empty($filters['persona_id'])) {
            $where[] = 'pg.persona_id=:pid';
            $params['pid'] = (int)$filters['persona_id'];
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY pg.data_pagamento DESC, pg.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM pagamenti WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $pag = self::find($id);
        $stmt = $pdo->prepare('UPDATE pagamenti SET deleted_at=NOW() WHERE id=:id AND deleted_at IS NULL');
        $ok = $stmt->execute(['id' => $id]);
        if ($ok && $pag) {
            Rate::aggiornaStato((int)$pag['rata_id']);
        }
        return $ok;
    }

    public static function restore(int $id): bool
    {
        global $pdo;
        $pag = self::find($id);
        $stmt = $pdo->prepare('UPDATE pagamenti SET deleted_at=NULL WHERE id=:id');
        $ok = $stmt->execute(['id' => $id]);
        if ($ok && $pag) {
            Rate::aggiornaStato((int)$pag['rata_id']);
        }
        return $ok;
    }
}
