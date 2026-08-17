<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('questionari', 'delete');

$id = get_int('id');
$questionario = get_questionario($id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}

$specificCount = count_requisiti_specifici_questionario($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (post('confirm') !== 'DELETE') {
        flash('error', 'Conferma non valida: il questionario non è stato eliminato.');
        redirect(APP_BASE_URL . '/questionario/elimina.php?id=' . $id);
    }
    $deleteSpecifici = post('specifici_action', 'keep') === 'delete';
    delete_questionario($id, $deleteSpecifici);
    flash('success', 'Questionario eliminato.');
    redirect(APP_BASE_URL . '/questionario/lista.php');
}

$page_title = 'Elimina questionario';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card shadow-sm border-danger">
      <div class="card-header bg-danger text-white fw-semibold">Conferma eliminazione questionario</div>
      <div class="card-body">
        <p>Stai per eliminare il questionario:</p>
        <ul>
          <li><strong>ID:</strong> <?= (int)$questionario['id'] ?></li>
          <li><strong>Progetto:</strong> <?= h($questionario['nome_progetto']) ?></li>
          <li><strong>Codice <?= acronym_help('PRJ', 'Codice identificativo del progetto') ?>:</strong> <?= h($questionario['codice_progetto']) ?></li>
          <li><strong>Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?>:</strong> <?= h($questionario['task_jira'] ?? '') ?></li>
        </ul>
        <div class="alert alert-warning">
          Verranno eliminate anche risposte, risultati calcolati e override manuali collegati al questionario.
        </div>
        <form method="post">
          <?= csrf_field() ?>
          <?php if ($specificCount > 0): ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Requisiti specifici collegati: <?= $specificCount ?></label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="specifici_action" id="keepSpecifici" value="keep" checked>
              <label class="form-check-label" for="keepSpecifici">Mantieni i requisiti specifici e rimuovi solo il legame con questo questionario</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="specifici_action" id="deleteSpecifici" value="delete">
              <label class="form-check-label" for="deleteSpecifici">Cancella anche i requisiti specifici non collegati ad altri questionari</label>
            </div>
          </div>
          <?php else: ?>
          <input type="hidden" name="specifici_action" value="keep">
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Per confermare scrivi <code>DELETE</code></label>
            <input class="form-control" name="confirm" required>
          </div>
          <button class="btn btn-danger" onclick="return confirm('Confermi l’eliminazione definitiva del questionario?')">Elimina questionario</button>
          <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/lista.php">Annulla</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
