<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('questionari', 'create');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = create_questionario($_POST);
    flash('success', 'Questionario creato. Ora puoi compilare le domande.');
    redirect(APP_BASE_URL . '/questionario/compila.php?id=' . $id);
}

$business_lines = get_business_lines(true);
$utenti = get_utenti(true);
$page_title = 'Nuovo questionario';
require __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Nuovo questionario</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label">Nome progetto</label>
        <input class="form-control" name="nome_progetto" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Codice <?= acronym_help('PRJ', 'Codice identificativo del progetto') ?></label>
        <input class="form-control" name="codice_progetto">
      </div>
      <div class="col-md-3">
        <label class="form-label">Business line</label>
        <select class="form-select" name="business_line">
          <option value="">Seleziona...</option>
          <?php foreach ($business_lines as $business_line): ?>
            <option value="<?= h($business_line['nome']) ?>"><?= h($business_line['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Nome servizio</label>
        <input class="form-control" name="nome_servizio">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('PM', 'Project Manager') ?> (Project Manager)</label>
        <input class="form-control" name="pm">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('PM', 'Product Manager') ?> (Product Manager)</label>
        <input class="form-control" name="pm_product_manager">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('PO', 'Product Owner') ?> (Product Owner)</label>
        <input class="form-control" name="po">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?= acronym_help('TPO', 'Technical Product Owner') ?> (Technical Product Owner)</label>
        <input class="form-control" name="tpo">
      </div>
      <div class="col-md-4">
        <label class="form-label">Tipologia progetto</label>
        <select class="form-select" name="tipologia_progetto">
          <option value="">Seleziona...</option>
          <option>Nuova realizzazione</option>
          <option>Modifica</option>
          <option>Assessment</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?></label>
        <input class="form-control" name="task_jira" placeholder="Es. SEC-1234">
      </div>
      <div class="col-md-4">
        <label class="form-label">Analista sicurezza questionario</label>
        <select class="form-select" name="analista_questionario_id">
          <option value="">Non assegnato</option>
          <?php foreach ($utenti as $utente): ?>
            <option value="<?= (int)$utente['id'] ?>"><?= h(user_label($utente)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Descrizione</label>
        <textarea class="form-control" name="descrizione" rows="3"></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Note</label>
        <textarea class="form-control" name="note" rows="2"></textarea>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Crea e compila</button>
        <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/lista.php">Annulla</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
