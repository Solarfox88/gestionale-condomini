<?php
require_once __DIR__ . '/../config/config.php';

function get_unita(?int $condominioId = null): array
{
    global $pdo;
    if ($condominioId) {
        $stmt = $pdo->prepare("SELECT u.*, c.nome AS condominio_nome FROM unita_immobiliari u JOIN condomini c ON u.condominio_id = c.id WHERE u.condominio_id = :cid ORDER BY u.scala, u.piano, u.interno");
        $stmt->execute(['cid' => $condominioId]);
    } else {
        $stmt = $pdo->query("SELECT u.*, c.nome AS condominio_nome FROM unita_immobiliari u JOIN condomini c ON u.condominio_id = c.id ORDER BY c.nome, u.scala, u.piano, u.interno");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_unita_singola(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.*, c.nome AS condominio_nome FROM unita_immobiliari u JOIN condomini c ON u.condominio_id = c.id WHERE u.id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function create_unita(array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO unita_immobiliari (condominio_id, scala, piano, interno, subalterno, descrizione, mq, millesimi_proprieta, millesimi_scale, millesimi_ascensore, millesimi_riscaldamento, status, created_at, updated_at) VALUES (:condominio_id, :scala, :piano, :interno, :subalterno, :descrizione, :mq, :millesimi_proprieta, :millesimi_scale, :millesimi_ascensore, :millesimi_riscaldamento, :status, NOW(), NOW())");
    return $stmt->execute([
        'condominio_id' => (int)$data['condominio_id'],
        'scala' => $data['scala'] ?? null,
        'piano' => $data['piano'] ?? null,
        'interno' => $data['interno'] ?? null,
        'subalterno' => $data['subalterno'] ?? null,
        'descrizione' => $data['descrizione'] ?? null,
        'mq' => !empty($data['mq']) ? (float)$data['mq'] : null,
        'millesimi_proprieta' => (float)($data['millesimi_proprieta'] ?? 0),
        'millesimi_scale' => (float)($data['millesimi_scale'] ?? 0),
        'millesimi_ascensore' => (float)($data['millesimi_ascensore'] ?? 0),
        'millesimi_riscaldamento' => (float)($data['millesimi_riscaldamento'] ?? 0),
        'status' => $data['status'] ?? 'active',
    ]);
}

function update_unita(int $id, array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE unita_immobiliari SET condominio_id = :condominio_id, scala = :scala, piano = :piano, interno = :interno, subalterno = :subalterno, descrizione = :descrizione, mq = :mq, millesimi_proprieta = :millesimi_proprieta, millesimi_scale = :millesimi_scale, millesimi_ascensore = :millesimi_ascensore, millesimi_riscaldamento = :millesimi_riscaldamento, status = :status, updated_at = NOW() WHERE id = :id");
    return $stmt->execute([
        'id' => $id,
        'condominio_id' => (int)$data['condominio_id'],
        'scala' => $data['scala'] ?? null,
        'piano' => $data['piano'] ?? null,
        'interno' => $data['interno'] ?? null,
        'subalterno' => $data['subalterno'] ?? null,
        'descrizione' => $data['descrizione'] ?? null,
        'mq' => !empty($data['mq']) ? (float)$data['mq'] : null,
        'millesimi_proprieta' => (float)($data['millesimi_proprieta'] ?? 0),
        'millesimi_scale' => (float)($data['millesimi_scale'] ?? 0),
        'millesimi_ascensore' => (float)($data['millesimi_ascensore'] ?? 0),
        'millesimi_riscaldamento' => (float)($data['millesimi_riscaldamento'] ?? 0),
        'status' => $data['status'] ?? 'active',
    ]);
}

function delete_unita(int $id): bool
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM unita_immobiliari WHERE id = :id");
    return $stmt->execute(['id' => $id]);
}
