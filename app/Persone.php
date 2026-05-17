<?php
require_once __DIR__ . '/../config/config.php';

function get_persone(): array
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM persone ORDER BY cognome ASC, nome ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_persona(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM persone WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function create_persona(array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO persone (nome, cognome, ragione_sociale, tipo, codice_fiscale, partita_iva, email, pec, telefono, indirizzo, note, created_at, updated_at) VALUES (:nome, :cognome, :ragione_sociale, :tipo, :codice_fiscale, :partita_iva, :email, :pec, :telefono, :indirizzo, :note, NOW(), NOW())");
    return $stmt->execute([
        'nome' => $data['nome'],
        'cognome' => $data['cognome'],
        'ragione_sociale' => $data['ragione_sociale'] ?? null,
        'tipo' => $data['tipo'] ?? 'persona',
        'codice_fiscale' => $data['codice_fiscale'] ?? null,
        'partita_iva' => $data['partita_iva'] ?? null,
        'email' => $data['email'] ?? null,
        'pec' => $data['pec'] ?? null,
        'telefono' => $data['telefono'] ?? null,
        'indirizzo' => $data['indirizzo'] ?? null,
        'note' => $data['note'] ?? null,
    ]);
}

function update_persona(int $id, array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE persone SET nome = :nome, cognome = :cognome, ragione_sociale = :ragione_sociale, tipo = :tipo, codice_fiscale = :codice_fiscale, partita_iva = :partita_iva, email = :email, pec = :pec, telefono = :telefono, indirizzo = :indirizzo, note = :note, updated_at = NOW() WHERE id = :id");
    return $stmt->execute([
        'id' => $id,
        'nome' => $data['nome'],
        'cognome' => $data['cognome'],
        'ragione_sociale' => $data['ragione_sociale'] ?? null,
        'tipo' => $data['tipo'] ?? 'persona',
        'codice_fiscale' => $data['codice_fiscale'] ?? null,
        'partita_iva' => $data['partita_iva'] ?? null,
        'email' => $data['email'] ?? null,
        'pec' => $data['pec'] ?? null,
        'telefono' => $data['telefono'] ?? null,
        'indirizzo' => $data['indirizzo'] ?? null,
        'note' => $data['note'] ?? null,
    ]);
}

function delete_persona(int $id): bool
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE persone SET deleted_at=NOW() WHERE id = :id AND deleted_at IS NULL");
    return $stmt->execute(['id' => $id]);
}

function restore_persona(int $id): bool
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE persone SET deleted_at=NULL WHERE id = :id");
    return $stmt->execute(['id' => $id]);
}
