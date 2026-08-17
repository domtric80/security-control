<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('questionari', 'read');

$id = get_int('id');
$questionario = get_questionario($id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_permission('questionari', 'update');
    save_risposte($id, $_POST['answers'] ?? [], $_POST['notes'] ?? []);
    if (isset($_POST['calculate'])) {
        calcola_risultati($id);
        flash('success', 'Risposte salvate e risultati calcolati.');
        redirect(APP_BASE_URL . '/questionario/risultati.php?id=' . $id);
    }
    flash('success', 'Risposte salvate.');
    redirect(APP_BASE_URL . '/questionario/compila.php?id=' . $id);
}

$answers = get_risposte($id);
$groups = get_domande_grouped(true);
$page_title = 'Compila questionario';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h1 class="h4 mb-1"><?= h($questionario['nome_progetto']) ?></h1>
    <div class="text-muted">
      <?= h($questionario['nome_servizio']) ?>
      <?php if (!empty($questionario['business_line'])): ?>
        ? <?= h($questionario['business_line']) ?>
      <?php endif; ?>
      ? Stato: <?= h($questionario['stato']) ?>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/modifica.php?id=<?= $id ?>">Dati iniziali</a>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/lista.php">Torna alla lista</a>
  </div>
</div>

<form method="post">
  <?= csrf_field() ?>
  <?php foreach ($groups as $categoria => $domande): ?>
    <div class="card shadow-sm mb-3">
      <div class="card-header fw-semibold"><?= h($categoria) ?></div>
      <div class="card-body">
        <?php foreach ($domande as $domanda): ?>
          <?php
            $domanda_id = (int)$domanda['id'];
            $current = $answers[$domanda_id]['valore'] ?? '';
            $note = $answers[$domanda_id]['note'] ?? '';
          ?>
          <div class="question-row">
            <label class="form-label fw-semibold mb-1"><?= h($domanda['testo']) ?></label>
            <div class="row g-2 align-items-start">
              <div class="col-lg-4">
                <?php if ($domanda['tipo'] === 'bool'): ?>
                  <select class="form-select" name="answers[<?= $domanda_id ?>]">
                    <option value="0" <?= (string)$current === '0' ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= (string)$current === '1' ? 'selected' : '' ?>>Sì</option>
                  </select>
                <?php elseif ($domanda['tipo'] === 'text'): ?>
                  <input class="form-control" name="answers[<?= $domanda_id ?>]" value="<?= h($current) ?>">
                <?php else: ?>
                  <input class="form-control" name="answers[<?= $domanda_id ?>]" value="<?= h($current) ?>">
                <?php endif; ?>
              </div>
              <div class="col-lg-8">
                <input class="form-control" name="notes[<?= $domanda_id ?>]" value="<?= h($note) ?>" placeholder="Note opzionali">
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="sticky-actions">
    <button class="btn btn-outline-primary" name="save" value="1">Salva bozza</button>
    <button class="btn btn-success" name="calculate" value="1">Salva e calcola risultati</button>
  </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>

