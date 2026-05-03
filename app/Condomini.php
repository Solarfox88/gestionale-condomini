<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Restituisce l'elenco di tutti i condomini.
 * @return array
 */
function get_condomini(): array
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM condomini ORDER BY nome ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Recupera un singolo condominio per ID.
 * @param int $id
 * @return array|null
 */
function get_condominio(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM condomini WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $condominio = $stmt->fetch(PDO::FETCH_ASSOC);
    return $condominio ?: null;
}

/**
 * Crea un nuovo condominio.
 * @param array $data
 * @return bool
 */
function create_condominio(array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO condomini (nome, codice_fiscale, indirizzo, comune, provincia, cap, iban, banca, email, pec, note, status, created_at, updated_at) VALUES (:nome, :codice_fiscale, :indirizzo, :comune, :provincia, :cap, :iban, :banca, :email, :pec, :note, :status, NOW(), NOW())");
    return $stmt->execute([
        'nome' => $data['nome'],
        'codice_fiscale' => $data['codice_fiscale'] ?? null,
        'indirizzo' => $data['indirizzo'] ?? null,
        'comune' => $data['comune'] ?? null,
        'provincia' => $data['provincia'] ?? null,
        'cap' => $data['cap'] ?? null,
        'iban' => $data['iban'] ?? null,
        'banca' => $data['banca'] ?? null,
        'email' => $data['email'] ?? null,
        'pec' => $data['pec'] ?? null,
        'note' => $data['note'] ?? null,
        'status' => $data['status'] ?? 'active'
    ]);
}

/**
 * Aggiorna un condominio esistente.
 * @param int $id
 * @param array $data
 * @return bool
 */
function update_condominio(int $id, array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE condomini SET nome = :nome, codice_fiscale = :codice_fiscale, indirizzo = :indirizzo, comune = :comune, provincia = :provincia, cap = :cap, iban = :iban, banca = :banca, email = :email, pec = :pec, note = :note, status = :status, updated_at = NOW() WHERE id = :id");
    return $stmt->execute([
        'id' => $id,
        'nome' => $data['nome'],
        'codice_fiscale' => $data['codice_fiscale'] ?? null,
        'indirizzo' => $data['indirizzo'] ?? null,
        'comune' => $data['comune'] ?? null,
        'provincia' => $data['provincia'] ?? null,
        'cap' => $data['cap'] ?? null,
        'iban' => $data['iban'] ?? null,
        'banca' => $data['banca'] ?? null,
        'email' => $data['email'] ?? null,
        'pec' => $data['pec'] ?? null,
        'note' => $data['note'] ?? null,
        'status' => $data['status'] ?? 'active'
    ]);
}

/**
 * Elimina un condominio.
 * @param int $id
 * @return bool
 */
function delete_condominio(int $id): bool
{
    global $pdo;
    // Attenzione: elimina anche tutte le unità e i documenti collegati
    $stmt = $pdo->prepare("DELETE FROM condomini WHERE id = :id");
    return $stmt->execute(['id' => $id]);
}
