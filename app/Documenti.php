<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Restituisce l'elenco dei documenti per un dato condominio o per tutti.
 * @param int|null $condominioId
 * @return array
 */
function get_documenti(?int $condominioId = null): array
{
    global $pdo;
    if ($condominioId) {
        $stmt = $pdo->prepare("SELECT d.*, c.nome AS condominio_nome FROM documenti d JOIN condomini c ON d.condominio_id = c.id WHERE d.condominio_id = :cid ORDER BY d.created_at DESC");
        $stmt->execute(['cid' => $condominioId]);
    } else {
        $stmt = $pdo->query("SELECT d.*, c.nome AS condominio_nome FROM documenti d JOIN condomini c ON d.condominio_id = c.id ORDER BY d.created_at DESC");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Carica un nuovo documento sul server e lo registra nel database.
 * @param array $file L'array $_FILES['file'] del documento
 * @param int $condominioId
 * @param string $titolo
 * @param string $categoria
 * @param int|null $unitaId
 * @param string $visibility
 * @param int $uploadedBy
 * @return bool|string Ritorna il file path salvato o false in caso di errore
 */
function upload_documento(array $file, int $condominioId, string $titolo, string $categoria, ?int $unitaId, string $visibility, int $uploadedBy)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    // genera nome file unico
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid('doc_', true) . '.' . $ext;
    $destDir = STORAGE_PATH . '/documents';
    if (!file_exists($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $destPath = $destDir . '/' . $uniqueName;
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO documenti (condominio_id, unita_id, titolo, categoria, descrizione, file_path, visibility, uploaded_by, created_at) VALUES (:cid, :uid, :titolo, :categoria, :descrizione, :file_path, :visibility, :uploaded_by, NOW())");
        $ok = $stmt->execute([
            'cid' => $condominioId,
            'uid' => $unitaId,
            'titolo' => $titolo,
            'categoria' => $categoria,
            'descrizione' => '',
            'file_path' => $uniqueName,
            'visibility' => $visibility,
            'uploaded_by' => $uploadedBy,
        ]);
        if ($ok) {
            return $uniqueName;
        }
    }
    return false;
}

/**
 * Recupera un documento per ID.
 * @param int $id
 * @return array|null
 */
function get_documento(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT d.*, c.nome AS condominio_nome FROM documenti d JOIN condomini c ON d.condominio_id = c.id WHERE d.id = :id");
    $stmt->execute(['id' => $id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    return $doc ?: null;
}
