<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = Esercizi::create($_POST);
    $msg = $id ? 'Esercizio creato correttamente.' : 'Errore durante la creazione dell'esercizio.';
}

$condomini = get_condomini();
$esercizi = Esercizi::all();
include __DIR__ . '/../includes/header.php';
?>
<h2>Esercizi contabili</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<div class="row">
  <div class="col-md-8">
    <table class="table table-striped table-bordered">
      <thead><tr><th>ID</th><th>Condominio</th><th>Nome</th><th>Periodo</th><th>Stato</th></tr></thead>
      <tbody>
      <?php foreach ($esercizi as $e): ?>
        <tr>
          <td><?php echo (int)$e['id']; ?></td>
          <td><?php echo htmlspecialchars($e['condominio_nome'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['nome']); ?></td>
          <td><?php echo htmlspecialchars($e['data_inizio'].' - '.$e['data_fine']); ?></td>
          <td><?php echo htmlspecialchars($e['stato']); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="col-md-4">
    <h4>Nuovo esercizio</h4>
    <form method="post">
      <?php echo csrf_field(); ?>
      <div class="mb-2"><label class="form-label">Condominio</label><select name="condominio_id" class="form-select" required><?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><label class="form-label">Nome</label><input name="nome" class="form-control" required placeholder="Esercizio 2026"></div>
      <div class="mb-2"><label class="form-label">Data inizio</label><input type="date" name="data_inizio" class="form-control" required></div>
      <div class="mb-2"><label class="form-label">Data fine</label><input type="date" name="data_fine" class="form-control" required></div>
      <div class="mb-2"><label class="form-label">Stato</label><select name="stato" class="form-select"><option value="bozza">Bozza</option><option value="aperto">Aperto</option><option value="chiuso">Chiuso</option></select></div>
      <button class="btn btn-primary">Crea</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
