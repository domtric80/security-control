<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('pir', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action', 'save_exception');
    if ($action === 'delete_exception') {
        require_permission('pir', 'delete');
        delete_security_exception((int)post('id'));
        flash('success', 'Eccezione eliminata.');
        redirect(APP_BASE_URL . '/pir/eccezioni.php');
    }

    require_permission('pir', ((int)post('id') > 0) ? 'update' : 'create');
    $result = save_security_exception($_POST);
    flash($result['ok'] ? 'success' : 'danger', $result['message']);
    redirect(APP_BASE_URL . '/pir/eccezioni.php' . ($result['ok'] ? '#eccezioni' : '#nuova-eccezione'));
}

$rows = get_security_exception_rows_by_project();
$questionari = get_questionari();
$calendarItems = get_security_exception_calendar_items();
$month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? '')) ? (string)$_GET['month'] : date('Y-m');
$monthStart = new DateTimeImmutable($month . '-01');
$prevMonth = $monthStart->modify('-1 month')->format('Y-m');
$nextMonth = $monthStart->modify('+1 month')->format('Y-m');
$daysInMonth = (int)$monthStart->format('t');
$firstWeekday = (int)$monthStart->format('N');
$itemsByDate = [];
foreach ($calendarItems as $item) {
    $date = (string)($item['data_rientro'] ?? '');
    if (str_starts_with($date, $month . '-')) {
        $itemsByDate[$date][] = $item;
    }
}

$grouped = [];
foreach ($rows as $row) {
    $projectId = (int)($row['questionario']['id'] ?? $row['questionario']['questionario_id'] ?? 0);
    $grouped[$projectId]['project'] = $row['questionario'];
    $grouped[$projectId]['rows'][] = $row;
}

$page_title = 'Eccezioni sicurezza';
require __DIR__ . '/../includes/header.php';

function exception_row_payload(array $row): array {
    if (($row['kind'] ?? '') === 'saved') {
        $e = $row['exception'];
        return [
            'id' => (int)$e['id'],
            'questionario_id' => (int)$e['questionario_id'],
            'source' => (string)$e['source'],
            'requisito_tipo' => (string)$e['requisito_tipo'],
            'requisito_ref_id' => (int)($e['requisito_ref_id'] ?? 0),
            'pir_review_id' => (int)($e['pir_review_id'] ?? 0),
            'codice' => (string)($e['codice'] ?? ''),
            'titolo' => (string)($e['titolo'] ?? ''),
            'categoria' => (string)($e['categoria'] ?? ''),
            'motivo' => (string)($e['motivo'] ?? ''),
            'data_rientro' => (string)($e['data_rientro'] ?? ''),
            'approvato_da' => (string)($e['approvato_da'] ?? ''),
            'stato' => (string)($e['stato'] ?? 'aperta'),
            'note' => (string)($e['note'] ?? ''),
            'review_stato' => '',
        ];
    }
    $req = $row['requirement'];
    $review = $row['review'];
    $motivo = trim((string)($review['applicazione'] ?? ''));
    if ($motivo === '') $motivo = trim((string)($review['note'] ?? ''));
    return [
        'id' => 0,
        'questionario_id' => (int)$row['questionario']['id'],
        'source' => 'pir',
        'requisito_tipo' => (string)$req['pir_tipo'],
        'requisito_ref_id' => (int)$req['pir_ref_id'],
        'pir_review_id' => (int)($review['id'] ?? 0),
        'codice' => (string)($req['codice'] ?? ''),
        'titolo' => (string)($req['titolo'] ?? ''),
        'categoria' => (string)($req['categoria'] ?? ''),
        'motivo' => $motivo,
        'data_rientro' => '',
        'approvato_da' => '',
        'stato' => 'aperta',
        'note' => trim((string)($review['rientro_eccezione'] ?? '')),
        'review_stato' => (string)($review['stato'] ?? ''),
    ];
}

function render_exception_form(array $payload, bool $compact = false): void {
    $formId = 'secException' . ($payload['id'] > 0 ? 'Saved' . $payload['id'] : 'New' . preg_replace('/[^A-Za-z0-9]/', '', $payload['questionario_id'] . $payload['requisito_tipo'] . $payload['requisito_ref_id']));
?>
  <form id="<?= h($formId) ?>" method="post" class="row g-2 align-items-start">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_exception">
    <input type="hidden" name="id" value="<?= (int)$payload['id'] ?>">
    <input type="hidden" name="questionario_id" value="<?= (int)$payload['questionario_id'] ?>">
    <input type="hidden" name="source" value="<?= h($payload['source']) ?>">
    <input type="hidden" name="requisito_tipo" value="<?= h($payload['requisito_tipo']) ?>">
    <input type="hidden" name="requisito_ref_id" value="<?= (int)$payload['requisito_ref_id'] ?>">
    <input type="hidden" name="pir_review_id" value="<?= (int)$payload['pir_review_id'] ?>">
    <input type="hidden" name="codice" value="<?= h($payload['codice']) ?>">
    <input type="hidden" name="titolo" value="<?= h($payload['titolo']) ?>">
    <input type="hidden" name="categoria" value="<?= h($payload['categoria']) ?>">
    <div class="col-lg-2">
      <div class="small text-muted">Requisito</div>
      <div><code><?= h($payload['codice'] ?: 'manuale') ?></code></div>
      <?php if ($payload['review_stato']): ?><span class="badge text-bg-danger">PIR <?= h($payload['review_stato']) ?></span><?php endif; ?>
      <?php if ($payload['id'] <= 0): ?><span class="badge text-bg-warning">Da censire</span><?php endif; ?>
    </div>
    <div class="col-lg-3">
      <div class="fw-semibold"><?= h($payload['titolo']) ?></div>
      <div class="small text-muted"><?= h($payload['categoria']) ?></div>
    </div>
    <div class="col-lg-3">
      <textarea class="form-control form-control-sm" name="motivo" rows="3" placeholder="Motivo eccezione"><?= h($payload['motivo']) ?></textarea>
    </div>
    <div class="col-lg-2">
      <label class="form-label small mb-1">Data rientro</label>
      <input class="form-control form-control-sm" type="date" name="data_rientro" value="<?= h($payload['data_rientro']) ?>">
      <label class="form-label small mb-1 mt-2">Approvato da</label>
      <input class="form-control form-control-sm" name="approvato_da" value="<?= h($payload['approvato_da']) ?>" placeholder="Nome approvatore">
    </div>
    <div class="col-lg-2">
      <label class="form-label small mb-1">Stato</label>
      <select class="form-select form-select-sm" name="stato">
        <?php foreach (['aperta' => 'Aperta', 'rientrata' => 'Rientrata', 'annullata' => 'Annullata'] as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $payload['stato'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea class="form-control form-control-sm mt-2" name="note" rows="2" placeholder="Note interne"><?= h($payload['note']) ?></textarea>
      <div class="d-flex gap-1 mt-2">
        <button class="btn btn-sm btn-primary"><?= $payload['id'] > 0 ? 'Aggiorna' : 'Crea eccezione' ?></button>
        <?php if ($payload['id'] > 0): ?>
          <button class="btn btn-sm btn-outline-danger" name="action" value="delete_exception" onclick="return confirm('Eliminare questa eccezione?')">Elimina</button>
        <?php endif; ?>
      </div>
    </div>
  </form>
<?php }
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h1 class="h4 mb-1">Eccezioni di sicurezza</h1>
    <div class="text-muted">Eccezioni da requisiti PIR non rispettati e inserimenti manuali, raggruppate per progetto.</div>
  </div>
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/pir/lista.php">Torna alle PIR</a>
</div>

<div class="card shadow-sm mb-4" id="nuova-eccezione">
  <div class="card-header fw-semibold">Nuova eccezione manuale</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_exception">
      <input type="hidden" name="source" value="manuale">
      <input type="hidden" name="requisito_tipo" value="manuale">
      <div class="col-md-4">
        <label class="form-label">Progetto</label>
        <select class="form-select" name="questionario_id" required>
          <option value="">Seleziona progetto...</option>
          <?php foreach ($questionari as $q): ?>
            <option value="<?= (int)$q['id'] ?>">#<?= (int)$q['id'] ?> - <?= h($q['nome_progetto']) ?><?= $q['task_jira'] ? ' · ' . h($q['task_jira']) : '' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Codice</label>
        <input class="form-control" name="codice" placeholder="Opzionale">
      </div>
      <div class="col-md-6">
        <label class="form-label">Titolo eccezione</label>
        <input class="form-control" name="titolo" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Categoria</label>
        <input class="form-control" name="categoria">
      </div>
      <div class="col-md-3">
        <label class="form-label">Data rientro</label>
        <input class="form-control" type="date" name="data_rientro">
      </div>
      <div class="col-md-3">
        <label class="form-label">Approvato da</label>
        <input class="form-control" name="approvato_da">
      </div>
      <div class="col-md-3">
        <label class="form-label">Stato</label>
        <select class="form-select" name="stato">
          <option value="aperta">Aperta</option>
          <option value="rientrata">Rientrata</option>
          <option value="annullata">Annullata</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Motivo</label>
        <textarea class="form-control" name="motivo" rows="3"></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Note</label>
        <textarea class="form-control" name="note" rows="3"></textarea>
      </div>
      <div class="col-12"><button class="btn btn-primary">Crea eccezione manuale</button></div>
    </form>
  </div>
</div>

<div class="card shadow-sm mb-4" id="eccezioni">
  <div class="card-header fw-semibold">Eccezioni raggruppate per progetto</div>
  <div class="card-body p-0">
    <div class="accordion" id="exceptionsAccordion">
      <?php $idx = 0; foreach ($grouped as $projectId => $group): $project = $group['project']; $collapse = 'excProject' . (int)$projectId; ?>
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button <?= $idx++ > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= h($collapse) ?>">
              <span class="fw-semibold me-2">#<?= (int)$projectId ?> <?= h($project['nome_progetto'] ?? '') ?></span>
              <span class="text-muted small"><?= h($project['task_jira'] ?? '') ?> · <?= count($group['rows']) ?> eccezioni / candidati</span>
            </button>
          </h2>
          <div id="<?= h($collapse) ?>" class="accordion-collapse collapse <?= $idx === 1 ? 'show' : '' ?>" data-bs-parent="#exceptionsAccordion">
            <div class="accordion-body">
              <?php foreach ($group['rows'] as $row): ?>
                <div class="border rounded p-3 mb-3 <?= ($row['kind'] ?? '') === 'pir_candidate' ? 'bg-warning-subtle' : '' ?>">
                  <?php render_exception_form(exception_row_payload($row)); ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$grouped): ?>
        <div class="p-4 text-center text-muted">Nessuna eccezione o requisito PIR KO/parziale presente.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4" id="calendario">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Calendario scadenze eccezioni</span>
    <div class="btn-group btn-group-sm">
      <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/pir/eccezioni.php?month=<?= h($prevMonth) ?>#calendario">Mese precedente</a>
      <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/pir/eccezioni.php?month=<?= h(date('Y-m')) ?>#calendario">Oggi</a>
      <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/pir/eccezioni.php?month=<?= h($nextMonth) ?>#calendario">Mese successivo</a>
    </div>
  </div>
  <div class="card-body">
    <h2 class="h6 mb-3"><?= h($monthStart->format('m/Y')) ?></h2>
    <div class="table-responsive">
      <table class="table table-bordered align-top calendar-table">
        <thead class="table-light"><tr><th>Lun</th><th>Mar</th><th>Mer</th><th>Gio</th><th>Ven</th><th>Sab</th><th>Dom</th></tr></thead>
        <tbody>
        <?php $day = 1; for ($week = 0; $week < 6 && $day <= $daysInMonth; $week++): ?>
          <tr>
          <?php for ($dow = 1; $dow <= 7; $dow++): ?>
            <?php if (($week === 0 && $dow < $firstWeekday) || $day > $daysInMonth): ?>
              <td class="bg-light" style="height: 120px;"></td>
            <?php else: $date = $month . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT); ?>
              <td style="height: 120px; min-width: 155px;">
                <div class="fw-semibold small mb-1"><?= $day ?></div>
                <?php foreach ($itemsByDate[$date] ?? [] as $item): ?>
                  <?php $tone = security_exception_status_tone((string)$item['stato'], (string)$item['data_rientro']); ?>
                  <div class="border-start border-3 border-<?= h($tone) ?> ps-2 mb-2 small">
                    <div class="fw-semibold"><?= h(short_text($item['nome_progetto'] ?? '', 28)) ?></div>
                    <div><?= h(short_text($item['titolo'] ?? '', 48)) ?></div>
                    <span class="badge text-bg-<?= h($tone) ?>"><?= h(security_exception_status_label((string)$item['stato'])) ?></span>
                  </div>
                <?php endforeach; ?>
              </td>
              <?php $day++; ?>
            <?php endif; ?>
          <?php endfor; ?>
          </tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
