<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('questionari', 'update');

$id = get_int('id');
$questionario = get_questionario($id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    update_questionario_anagrafica($id, $_POST);
    flash('success', 'Dati iniziali del questionario aggiornati.');
    redirect(APP_BASE_URL . '/questionario/compila.php?id=' . $id);
}

$business_lines = get_business_lines(true);
$utenti = get_utenti(true);
$page_title = 'Modifica dati questionario';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Modifica dati iniziali</h1>
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/compila.php?id=<?= $id ?>">Torna al questionario</a>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Anagrafica questionario #<?= (int)$questionario['id'] ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label">Nome progetto</label>
        <input class="form-control" name="nome_progetto" value="<?= h($questionario['nome_progetto']) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Codice <?= acronym_help('PRJ', 'Codice identificativo del progetto') ?></label>
        <input class="form-control" name="codice_progetto" value="<?= h($questionario['codice_progetto']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Business line</label>
        <select class="form-select" name="business_line">
          <option value="">Seleziona...</option>
          <?php foreach ($business_lines as $business_line): ?>
            <option value="<?= h($business_line['nome']) ?>" <?= $questionario['business_line'] === $business_line['nome'] ? 'selected' : '' ?>>
              <?= h($business_line['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Nome servizio</label>
        <input class="form-control" name="nome_servizio" value="<?= h($questionario['nome_servizio']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('PM', 'Project Manager') ?> (Project Manager)</label>
        <input class="form-control" name="pm" value="<?= h($questionario['pm']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('PM', 'Product Manager') ?> (Product Manager)</label>
        <input class="form-control" name="pm_product_manager" value="<?= h($questionario['pm_product_manager'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('PO', 'Product Owner') ?> (Product Owner)</label>
        <input class="form-control" name="po" value="<?= h($questionario['po']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('TPO', 'Technical Product Owner') ?> (Technical Product Owner)</label>
        <input class="form-control" name="tpo" value="<?= h($questionario['tpo']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Tipologia progetto</label>
        <select class="form-select" name="tipologia_progetto">
          <?php foreach (['', 'Nuova realizzazione', 'Modifica', 'Assessment'] as $tipo): ?>
            <option value="<?= h($tipo) ?>" <?= $questionario['tipologia_progetto'] === $tipo ? 'selected' : '' ?>>
              <?= $tipo === '' ? 'Seleziona...' : h($tipo) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?></label>
        <input class="form-control" name="task_jira" value="<?= h($questionario['task_jira'] ?? '') ?>" placeholder="Es. SEC-1234">
      </div>
      <div class="col-md-4">
        <label class="form-label">Analista sicurezza questionario</label>
        <select class="form-select" name="analista_questionario_id">
          <option value="">Non assegnato</option>
          <?php foreach ($utenti as $utente): ?>
            <option value="<?= (int)$utente['id'] ?>" <?= (int)($questionario['analista_questionario_id'] ?? 0) === (int)$utente['id'] ? 'selected' : '' ?>>
              <?= h(user_label($utente)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Descrizione</label>
        <textarea class="form-control" name="descrizione" rows="3"><?= h($questionario['descrizione']) ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Note</label>
        <textarea class="form-control" name="note" rows="2"><?= h($questionario['note']) ?></textarea>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Salva dati iniziali</button>
        <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/compila.php?id=<?= $id ?>">Annulla</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
