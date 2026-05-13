<?php
require_once __DIR__ . '/../config/config.php';

function get_unita_persone(int $unitaId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT up.*, p.nome, p.cognome, p.codice_fiscale, p.email AS persona_email FROM unita_persone up JOIN persone p ON up.persona_id = p.id WHERE up.unita_id = :uid ORDER BY up.ruolo, p.cognome");
    $stmt->execute(['uid' => $unitaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_persona_unita(int $personaId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT up.*, u.scala, u.piano, u.interno, c.nome AS condominio_nome FROM unita_persone up JOIN unita_immobiliari u ON up.unita_id = u.id JOIN condomini c ON u.condominio_id = c.id WHERE up.persona_id = :pid ORDER BY c.nome, u.scala, u.piano");
    $stmt->execute(['pid' => $personaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_unita_persona(array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO unita_persone (unita_id, persona_id, user_id, ruolo, percentuale, data_inizio, data_fine, created_at, updated_at) VALUES (:unita_id, :persona_id, :user_id, :ruolo, :percentuale, :data_inizio, :data_fine, NOW(), NOW())");
    return $stmt->execute([
        'unita_id' => (int)$data['unita_id'],
        'persona_id' => (int)$data['persona_id'],
        'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
        'ruolo' => $data['ruolo'] ?? 'proprietario',
        'percentuale' => (float)($data['percentuale'] ?? 100),
        'data_inizio' => !empty($data['data_inizio']) ? $data['data_inizio'] : null,
        'data_fine' => !empty($data['data_fine']) ? $data['data_fine'] : null,
    ]);
}

function delete_unita_persona(int $id): bool
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM unita_persone WHERE id = :id");
    return $stmt->execute(['id' => $id]);
}
