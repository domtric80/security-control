<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('questionari', 'create');

$page_title = 'Importa questionario XLSX';
$preview = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action', 'preview');
    try {
        if ($action === 'import') {
            $payload = (string)post('payload', '');
            $json = base64_decode($payload, true);
            $preview = $json !== false ? json_decode($json, true) : null;
            if (!is_array($preview)) {
                throw new RuntimeException('Dati di anteprima non validi. Ricarica il file XLSX.');
            }
            $id = questionario_import_commit($preview, (bool)post('calculate', '1'));
            flash('success', 'Questionario importato correttamente.');
            redirect(APP_BASE_URL . '/questionario/risultati.php?id=' . $id);
        }

        if (!isset($_FILES['xlsx']) || !is_array($_FILES['xlsx'])) {
            throw new RuntimeException('Seleziona un file XLSX da importare.');
        }
        if ((int)($_FILES['xlsx']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload non completato. Codice errore: ' . (int)$_FILES['xlsx']['error']);
        }
        $filename = (string)($_FILES['xlsx']['name'] ?? '');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            throw new RuntimeException('Formato non supportato: carica un file .xlsx.');
        }
        $preview = questionario_import_analyze_xlsx((string)$_FILES['xlsx']['tmp_name'], $filename);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$payload = $preview ? base64_encode(json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) : '';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Importa questionario XLSX</h1>
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/lista.php">Torna alla lista</a>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Carica file compilato offline</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="preview">
      <div class="col-md-8">
        <label class="form-label">File questionario .xlsx</label>
        <input class="form-control" type="file" name="xlsx" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        <div class="form-text">Il file viene letto e mostrato in anteprima prima del salvataggio nel database.</div>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Analizza file</button>
      </div>
    </form>
  </div>
</div>

<?php if ($preview): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Anteprima import</div>
    <div class="card-body">
      <?php if (!empty($preview['warnings'])): ?>
        <div class="alert alert-warning">
          <div class="fw-semibold mb-1">Avvisi</div>
          <ul class="mb-0">
            <?php foreach ($preview['warnings'] as $warning): ?>
              <li><?= h($warning) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <div class="border rounded p-3 bg-light">
            <div class="text-muted small">Domande riconosciute</div>
            <div class="fs-4 fw-semibold"><?= (int)($preview['stats']['answers'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="border rounded p-3 bg-light">
            <div class="text-muted small">Risposte positive</div>
            <div class="fs-4 fw-semibold text-success"><?= (int)($preview['stats']['positive'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="border rounded p-3 bg-light">
            <div class="text-muted small">Risposte negative</div>
            <div class="fs-4 fw-semibold text-secondary"><?= (int)($preview['stats']['negative'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="border rounded p-3 bg-light">
            <div class="text-muted small">Fogli letti</div>
            <div class="fs-4 fw-semibold"><?= (int)($preview['stats']['sheets'] ?? 0) ?></div>
          </div>
        </div>
      </div>

      <h2 class="h6">Dati iniziali</h2>
      <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle">
          <tbody>
          <?php foreach (($preview['questionario'] ?? []) as $key => $value): ?>
            <?php if ($key === 'analista_questionario_id') continue; ?>
            <tr>
              <th class="bg-light" style="width: 240px;"><?= h($key) ?></th>
              <td><?= nl2br(h((string)$value)) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h2 class="h6">Risposte riconosciute</h2>
      <div class="table-responsive" style="max-height: 520px;">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light sticky-top">
            <tr>
              <th>Codice</th>
              <th>Domanda DB</th>
              <th>Sorgente XLSX</th>
              <th>Valore XLSX</th>
              <th>Import</th>
              <th>Note</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach (($preview['rows'] ?? []) as $row): ?>
            <tr>
              <td><code><?= h($row['codice'] ?? '') ?></code></td>
              <td><?= h($row['domanda'] ?? '') ?></td>
              <td class="text-muted small"><?= h($row['sorgente'] ?? '') ?></td>
              <td><?= h($row['raw'] ?? '') ?></td>
              <td>
                <span class="badge text-bg-<?= (string)($row['valore'] ?? '') === '1' ? 'success' : 'secondary' ?>">
                  <?= (string)($row['valore'] ?? '') === '1' ? 'Sì' : 'No' ?>
                </span>
              </td>
              <td class="small"><?= nl2br(h($row['note'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <form method="post" class="mt-3 d-flex gap-2 align-items-center">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import">
        <input type="hidden" name="payload" value="<?= h($payload) ?>">
        <input type="hidden" name="calculate" value="0">
        <div class="form-check me-auto">
          <input class="form-check-input" type="checkbox" name="calculate" value="1" id="calculateImport" checked>
          <label class="form-check-label" for="calculateImport">Calcola subito requisiti e servizi</label>
        </div>
        <button class="btn btn-success"><i class="bi bi-database-check me-1"></i>Conferma import</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
