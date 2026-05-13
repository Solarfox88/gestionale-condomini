<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/UnitaPersone.php';
require_once __DIR__ . '/../app/Persone.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$unita = get_unita_singola($id);
if (!$unita) {
    echo 'Unita non trovata.';
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna_unita') {
        $_POST['condominio_id'] = $unita['condominio_id'];
        if (update_unita($id, $_POST)) {
            $msg = 'Unita aggiornata con successo.';
            $unita = get_unita_singola($id);
        } else {
            $msg = 'Errore durante l\'aggiornamento.';
        }
    } elseif ($azione === 'associa_persona') {
        $_POST['unita_id'] = $id;
        if (create_unita_persona($_POST)) {
            $msg = 'Persona associata con successo.';
        } else {
            $msg = 'Errore nell\'associazione della persona.';
        }
    } elseif ($azione === 'rimuovi_associazione') {
        $assocId = (int)($_POST['associazione_id'] ?? 0);
        if ($assocId > 0 && delete_unita_persona($assocId)) {
            $msg = 'Associazione rimossa.';
        } else {
            $msg = 'Errore nella rimozione.';
        }
    }
}

$associazioni = get_unita_persone($id);
$persone = get_persone();
include __DIR__ . '/../includes/header.php';
?>
<h2>Dettaglio Unita #<?php echo (int)$unita['id']; ?> - <?php echo htmlspecialchars($unita['condominio_nome']); ?></h2>
<p>Scala <?php echo htmlspecialchars($unita['scala'] ?? '-'); ?>, Piano <?php echo htmlspecialchars($unita['piano'] ?? '-'); ?>, Interno <?php echo htmlspecialchars($unita['interno'] ?? '-'); ?></p>
<?php if ($msg): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <h4>Modifica Unita</h4>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="aggiorna_unita">
            <div class="mb-2">
                <label class="form-label">Scala</label>
                <input type="text" class="form-control" name="scala" value="<?php echo htmlspecialchars($unita['scala'] ?? ''); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Piano</label>
                <input type="text" class="form-control" name="piano" value="<?php echo htmlspecialchars($unita['piano'] ?? ''); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Interno</label>
                <input type="text" class="form-control" name="interno" value="<?php echo htmlspecialchars($unita['interno'] ?? ''); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Subalterno</label>
                <input type="text" class="form-control" name="subalterno" value="<?php echo htmlspecialchars($unita['subalterno'] ?? ''); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Descrizione</label>
                <input type="text" class="form-control" name="descrizione" value="<?php echo htmlspecialchars($unita['descrizione'] ?? ''); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">MQ</label>
                <input type="number" step="0.01" class="form-control" name="mq" value="<?php echo htmlspecialchars($unita['mq'] ?? ''); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Proprieta</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_proprieta" value="<?php echo htmlspecialchars($unita['millesimi_proprieta']); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Scale</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_scale" value="<?php echo htmlspecialchars($unita['millesimi_scale']); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Ascensore</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_ascensore" value="<?php echo htmlspecialchars($unita['millesimi_ascensore']); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Riscaldamento</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_riscaldamento" value="<?php echo htmlspecialchars($unita['millesimi_riscaldamento']); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Stato</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo $unita['status'] === 'active' ? 'selected' : ''; ?>>Attivo</option>
                    <option value="inactive" <?php echo $unita['status'] === 'inactive' ? 'selected' : ''; ?>>Inattivo</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Salva</button>
            <a href="<?php echo url('/admin/unita.php'); ?>" class="btn btn-secondary">Indietro</a>
        </form>
    </div>
    <div class="col-md-6">
        <h4>Persone associate</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Cognome Nome</th>
                    <th>Ruolo</th>
                    <th>%</th>
                    <th>Da</th>
                    <th>A</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($associazioni as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['cognome'] . ' ' . $a['nome']); ?></td>
                        <td><?php echo htmlspecialchars($a['ruolo']); ?></td>
                        <td><?php echo number_format((float)$a['percentuale'], 2, ',', '.'); ?>%</td>
                        <td><?php echo htmlspecialchars($a['data_inizio'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($a['data_fine'] ?? '-'); ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="azione" value="rimuovi_associazione">
                                <input type="hidden" name="associazione_id" value="<?php echo (int)$a['id']; ?>">
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Rimuovere questa associazione?')">Rimuovi</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h5>Associa Persona</h5>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="associa_persona">
            <div class="mb-2">
                <label class="form-label">Persona*</label>
                <select name="persona_id" class="form-select" required>
                    <option value="">Seleziona...</option>
                    <?php foreach ($persone as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['cognome'] . ' ' . $p['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Ruolo</label>
                <select name="ruolo" class="form-select">
                    <option value="proprietario">Proprietario</option>
                    <option value="comproprietario">Comproprietario</option>
                    <option value="inquilino">Inquilino</option>
                    <option value="usufruttuario">Usufruttuario</option>
                    <option value="delegato">Delegato</option>
                    <option value="altro">Altro</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Percentuale</label>
                <input type="number" step="0.01" class="form-control" name="percentuale" value="100">
            </div>
            <div class="mb-2">
                <label class="form-label">Data inizio</label>
                <input type="date" class="form-control" name="data_inizio">
            </div>
            <div class="mb-2">
                <label class="form-label">Data fine</label>
                <input type="date" class="form-control" name="data_fine">
            </div>
            <button type="submit" class="btn btn-primary">Associa</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php';
