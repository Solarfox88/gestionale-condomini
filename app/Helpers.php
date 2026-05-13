<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Restituisce i dati dell'utente corrente dalla sessione.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Scrive una riga nell'audit_logs.
 */
function audit_log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    global $pdo;
    $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address,user_agent,details) VALUES (:uid,:action,:etype,:eid,:ip,:ua,:details)');
    $stmt->execute([
        'uid' => $userId,
        'action' => $action,
        'etype' => $entityType,
        'eid' => $entityId,
        'ip' => substr($ip, 0, 64),
        'ua' => $ua,
        'details' => $details,
    ]);
}

/**
 * Formatta un importo in euro.
 */
function format_euro(float $amount): string
{
    return number_format($amount, 2, ',', '.');
}

/**
 * Restituisce badge HTML per uno stato generico.
 */
function stato_badge(string $stato): string
{
    $map = [
        'active' => 'bg-success', 'inactive' => 'bg-secondary', 'archived' => 'bg-dark',
        'pending' => 'bg-warning text-dark',
        'bozza' => 'bg-secondary', 'aperto' => 'bg-success', 'chiuso' => 'bg-dark',
        'da_pagare' => 'bg-warning text-dark', 'parziale' => 'bg-info', 'pagata' => 'bg-success', 'scaduta' => 'bg-danger',
        'convocata' => 'bg-primary', 'svolta' => 'bg-success', 'annullata' => 'bg-dark',
        'calcolato' => 'bg-info', 'approvato' => 'bg-success', 'rate_generate' => 'bg-primary',
        'preso_in_carico' => 'bg-info', 'in_attesa' => 'bg-warning text-dark',
        'in_lavorazione' => 'bg-primary', 'risolto' => 'bg-success', 'respinto' => 'bg-dark',
    ];
    $cls = $map[$stato] ?? 'bg-secondary';
    $label = str_replace('_', ' ', $stato);
    return '<span class="badge ' . $cls . '">' . htmlspecialchars(ucfirst($label)) . '</span>';
}

/**
 * Scorciatoia per escape HTML.
 */
function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Restituisce le categorie documenti previste.
 */
function categorie_documenti(): array
{
    return ['verbali','bilanci','preventivi','consuntivi','fatture','contratti','manutenzioni','assicurazioni','regolamento','comunicazioni','solleciti','altro'];
}
