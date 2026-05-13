<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Tickets.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea_ticket') {
        $id = Tickets::create($_POST, $_SESSION['user']['id']);
        $msg = $id ? 'Ticket creato correttamente.' : 'Errore nella creazione del ticket.';
    } elseif ($azione === 'aggiorna_stato') {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $stato = $_POST['stato'] ?? '';
        if ($ticketId > 0 && $stato !== '') {
            $ok = Tickets::updateStatus($ticketId, $stato);
            $msg = $ok ? 'Stato aggiornato.' : 'Errore nell\'aggiornamento dello stato.';
        }
    }
}

$condominioFilter = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;
$condomini = get_condomini();
$tickets = Tickets::all($condominioFilter);
include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Ticket</h2>
<?php if ($msg): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Filtro per condominio</label>
            <select name="condominio_id" class="form-select" onchange="this.form.submit()">
                <option value="">Tutti</option>
                <?php foreach ($condomini as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo $condominioFilter == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Condominio</th>
            <th>Titolo</th>
            <th>Categoria</th>
            <th>Priorita</th>
            <th>Stato</th>
            <th>Aperto da</th>
            <th>Data</th>
            <th>Azione</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td><?php echo (int)$t['id']; ?></td>
                <td><?php echo htmlspecialchars($t['condominio_nome']); ?></td>
                <td><?php echo htmlspecialchars($t['titolo']); ?></td>
                <td><?php echo htmlspecialchars($t['categoria'] ?? ''); ?></td>
                <td>
                    <?php
                    $badgeClass = match($t['priorita']) {
                        'urgente' => 'bg-danger',
                        'alta' => 'bg-warning text-dark',
                        'media' => 'bg-info text-dark',
                        default => 'bg-secondary',
                    };
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($t['priorita']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($t['stato']); ?></td>
                <td><?php echo htmlspecialchars($t['autore_nome']); ?></td>
                <td><?php echo htmlspecialchars($t['created_at']); ?></td>
                <td>
                    <form method="post" class="d-flex gap-1">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="azione" value="aggiorna_stato">
                        <input type="hidden" name="ticket_id" value="<?php echo (int)$t['id']; ?>">
                        <select name="stato" class="form-select form-select-sm" style="width:auto">
                            <?php foreach (['aperto','preso_in_carico','in_attesa','in_lavorazione','risolto','chiuso','respinto'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $t['stato'] === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-primary">OK</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h4>Apri nuovo ticket</h4>
<form method="post" class="row g-2">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="crea_ticket">
    <div class="col-md-3">
        <label class="form-label">Condominio*</label>
        <select name="condominio_id" class="form-select" required>
            <option value="">Seleziona...</option>
            <?php foreach ($condomini as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Titolo*</label>
        <input type="text" class="form-control" name="titolo" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Categoria</label>
        <input type="text" class="form-control" name="categoria" placeholder="es. Manutenzione">
    </div>
    <div class="col-md-3">
        <label class="form-label">Priorita</label>
        <select name="priorita" class="form-select">
            <option value="bassa">Bassa</option>
            <option value="media" selected>Media</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
        </select>
    </div>
    <div class="col-md-12">
        <label class="form-label">Descrizione*</label>
        <textarea class="form-control" name="descrizione" rows="3" required></textarea>
    </div>
    <input type="hidden" name="unita_id" value="">
    <input type="hidden" name="assegnato_a" value="">
    <div class="col-md-12"><button class="btn btn-primary">Crea Ticket</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php';
