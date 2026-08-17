<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('risultati', 'read');

$id = get_int('id');
$questionario = get_questionario($id);
if (!$questionario) {
    http_response_code(404);
    exit('Questionario non trovato.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_permission('risultati', 'update');
    $action = (string)post('action', 'recalc');
    if ($action === 'recalc') {
        calcola_risultati($id);
        flash('success', 'Risultati ricalcolati.');
    } elseif ($action === 'exclude_selected') {
        foreach ($_POST['exclude_ids'] ?? [] as $requisito_id) {
            set_requisito_override($id, (int)$requisito_id, 'exclude', trim((string)post('override_note', '')));
        }
        flash('success', 'Requisiti selezionati esclusi dai risultati.');
    } elseif ($action === 'include_catalog') {
        $requisito_id = (int)post('requisito_id');
        if ($requisito_id > 0) {
            set_requisito_override($id, $requisito_id, 'include', 'Aggiunto manualmente da catalogo');
            flash('success', 'Requisito aggiunto dai requisiti a catalogo.');
        }
    } elseif ($action === 'link_specifico') {
        $specifico_id = (int)post('specifico_id');
        if ($specifico_id > 0) {
            link_requisito_specifico_to_questionario($id, $specifico_id);
            flash('success', 'Requisito specifico esistente collegato al questionario.');
        }
    } elseif ($action === 'restore_catalog') {
        clear_requisito_override($id, (int)post('requisito_id'));
        flash('success', 'Override requisito rimosso.');
    }
    redirect(APP_BASE_URL . '/questionario/risultati.php?id=' . $id);
}

$gruppi = get_requisiti_revisionati($id);
$requisiti_specifici = $gruppi['specifici'];
$requisiti_catalogo = $gruppi['catalogo'];
$requisiti_standard = $gruppi['standard'];
$requisiti_esclusi = get_requisiti_esclusi($id);
$servizi = get_risultati_servizi($id, true);
$catalogo = get_requisiti(true);
$specifici_riusabili = get_requisiti_specifici_riusabili($id);
$spiegazioni_ia = has_permission('ai_assistant', 'read')
    ? get_ai_suggestions_by_type($id, 'explanation', ['approvato', 'applicato'])
    : [];

$page_title = 'Risultati questionario';
require __DIR__ . '/../includes/header.php';

function render_requirement_table(array $rows, string $emptyMessage, bool $allowExclude = true, string $tone = ''): void {
?>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0 <?= $tone ?>">
      <thead>
        <tr>
          <?php if ($allowExclude): ?><th class="text-center">Escludi</th><?php endif; ?>
          <th>Codice</th>
          <th>Titolo</th>
          <th>Categoria</th>
          <th>Importanza</th>
          <th>STD</th>
          <th>Owner</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="<?= requirement_is_standard($r) ? 'table-secondary' : '' ?>">
          <?php if ($allowExclude): ?>
            <td class="text-center"><input class="form-check-input" type="checkbox" name="exclude_ids[]" value="<?= (int)$r['id'] ?>"></td>
          <?php endif; ?>
          <td class="text-nowrap"><code><?= h($r['codice'] ?? '') ?></code></td>
          <td>
            <strong><?= h($r['titolo'] ?? '') ?></strong>
            <?php if (($r['override_azione'] ?? '') === 'include'): ?>
              <span class="badge text-bg-info ms-1">manuale</span>
            <?php endif; ?>
            <div class="small text-muted"><?= h(short_text($r['descrizione'] ?? '', 140)) ?></div>
          </td>
          <td class="small"><?= h(short_text($r['categoria'] ?? '', 70)) ?></td>
          <td><?= h($r['importanza'] ?? '') ?></td>
          <td><?= h($r['std'] ?? '') ?></td>
          <td><?= h($r['owner'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="<?= $allowExclude ? 7 : 6 ?>" class="text-center text-muted py-4"><?= h($emptyMessage) ?></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php
}
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h1 class="h4 mb-1">Risultati: <?= h($questionario['nome_progetto']) ?></h1>
    <div class="text-muted">
      <?= h($questionario['nome_servizio']) ?>
      <?php if (!empty($questionario['task_jira'])): ?> · Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?>: <?= h($questionario['task_jira']) ?><?php endif; ?>
      · <?= count($requisiti_specifici) ?> specifici
      · <?= count($requisiti_catalogo) ?> catalogo
      · <?= count($requisiti_standard) ?> standard
      · <?= count($servizi) ?> servizi
    </div>
  </div>
  <form method="post" class="d-flex flex-wrap gap-2">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="recalc">
    <button class="btn btn-outline-primary">Ricalcola</button>
    <a class="btn btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/modifica.php?id=<?= $id ?>">Dati iniziali</a>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/compila.php?id=<?= $id ?>">Modifica risposte</a>
    <?php if (has_permission('ai_assistant', 'read')): ?>
      <a class="btn btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=specific_requirements"><i class="bi bi-stars me-1"></i>Suggerimenti IA</a>
    <?php endif; ?>
  </form>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body d-flex flex-wrap align-items-center gap-2">
    <span class="fw-semibold me-2"><i class="bi bi-download me-1"></i>Scarica risultati</span>
    <a class="btn btn-sm btn-outline-success" href="<?= APP_BASE_URL ?>/questionario/export.php?id=<?= $id ?>&format=xls">XLS</a>
    <a class="btn btn-sm btn-outline-success" href="<?= APP_BASE_URL ?>/questionario/export.php?id=<?= $id ?>&format=csv">CSV</a>
    <a class="btn btn-sm btn-outline-danger" href="<?= APP_BASE_URL ?>/questionario/export.php?id=<?= $id ?>&format=pdf">PDF</a>
    <a class="btn btn-sm btn-outline-secondary" href="<?= APP_BASE_URL ?>/questionario/export.php?id=<?= $id ?>&format=confluence">Codice Confluence</a>
    <?php if (has_permission('ai_assistant', 'read')): ?>
      <span class="vr mx-2"></span>
      <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=false_positives">Falsi positivi IA</a>
      <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=result_explanations">Spiegazioni IA</a>
      <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=question_quality">Qualità questionario</a>
      <a class="btn btn-sm btn-outline-info" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= $id ?>&type=service_mapping">Mapping servizi IA</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($spiegazioni_ia): ?>
<div class="card shadow-sm mb-4 border-info">
  <div class="card-header fw-semibold bg-info-subtle">
    <i class="bi bi-stars me-1"></i>Spiegazioni IA pubblicate
    <span class="small text-muted ms-2">Generate da “Spiegazione risultati” e approvate/applicate dall’analista.</span>
  </div>
  <div class="accordion accordion-flush" id="spiegazioniIaAccordion">
    <?php foreach ($spiegazioni_ia as $index => $spiegazione): ?>
      <?php $payload = json_decode((string)$spiegazione['payload_json'], true) ?: []; ?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="spiegazioneHeading<?= (int)$spiegazione['id'] ?>">
          <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#spiegazioneCollapse<?= (int)$spiegazione['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="spiegazioneCollapse<?= (int)$spiegazione['id'] ?>">
            <span class="badge text-bg-<?= $spiegazione['status'] === 'applicato' ? 'success' : 'primary' ?> me-2"><?= h($spiegazione['status']) ?></span>
            <?php if (!empty($payload['codice'])): ?><code class="me-2"><?= h((string)$payload['codice']) ?></code><?php endif; ?>
            <?= h($spiegazione['title']) ?>
          </button>
        </h2>
        <div id="spiegazioneCollapse<?= (int)$spiegazione['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#spiegazioniIaAccordion">
          <div class="accordion-body">
            <div class="mb-2"><?= nl2br(h((string)$spiegazione['body'])) ?></div>
            <?php if (!empty($payload['spiegazione'])): ?>
              <div class="small"><strong>Spiegazione:</strong> <?= nl2br(h((string)$payload['spiegazione'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($payload['evidenze'])): ?>
              <div class="small mt-2"><strong>Evidenze:</strong> <?= h(is_array($payload['evidenze']) ? implode(' · ', $payload['evidenze']) : (string)$payload['evidenze']) ?></div>
            <?php endif; ?>
            <?php if (!empty($spiegazione['rationale'])): ?>
              <div class="small text-muted mt-2"><strong>Motivo IA:</strong> <?= h((string)$spiegazione['rationale']) ?></div>
            <?php endif; ?>
            <div class="small text-muted mt-2">Run IA: #<?= (int)$spiegazione['run_id'] ?> · <?= h((string)$spiegazione['run_created_at']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4 border-primary">
  <div class="card-header fw-semibold bg-primary text-white">REQUISITI SPECIFICI DI PROGETTO</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Codice</th><th>Titolo</th><th>Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?></th><th>Importanza</th><th>Owner</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($requisiti_specifici as $r): ?>
          <tr>
            <td><code><?= h($r['codice']) ?></code></td>
            <td><strong><?= h($r['titolo']) ?></strong><div class="small text-muted"><?= h(short_text($r['descrizione'], 160)) ?></div></td>
            <td><?= h($r['task_jira']) ?></td>
            <td><?= h($r['importanza']) ?></td>
            <td><?= h($r['owner']) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/questionario/requisiti_specifici.php?questionario_id=<?= $id ?>&edit=<?= (int)$r['id'] ?>">Modifica</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$requisiti_specifici): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nessun requisito specifico di progetto inserito.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    <div class="d-flex flex-wrap gap-2 align-items-end">
      <a class="btn btn-sm btn-primary" href="<?= APP_BASE_URL ?>/questionario/requisiti_specifici.php?questionario_id=<?= $id ?>">Gestisci requisiti specifici</a>
      <form method="post" class="d-flex flex-wrap gap-2 align-items-end ms-lg-auto">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="link_specifico">
        <div>
          <label class="form-label small mb-1">Aggiungi specifico già presente</label>
          <select class="form-select form-select-sm" name="specifico_id" required <?= $specifici_riusabili ? '' : 'disabled' ?>>
            <option value="">Seleziona...</option>
            <?php foreach ($specifici_riusabili as $s): ?>
              <option value="<?= (int)$s['id'] ?>">
                <?= h(($s['codice'] ?: 'SPEC-' . $s['id']) . ' - ' . short_text($s['titolo'], 90)) ?>
              </option>
            <?php endforeach; ?>
            <?php if (!$specifici_riusabili): ?>
              <option value="">Nessun requisito specifico riusabile</option>
            <?php endif; ?>
          </select>
        </div>
        <button class="btn btn-sm btn-outline-primary" <?= $specifici_riusabili ? '' : 'disabled' ?>>Collega</button>
      </form>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Aggiungi requisito da catalogo</div>
  <div class="card-body">
    <form method="post" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="include_catalog">
      <div class="col-lg-10">
        <label class="form-label">Requisito a catalogo</label>
        <select class="form-select" name="requisito_id" required>
          <option value="">Seleziona...</option>
          <?php foreach ($catalogo as $r): ?>
            <option value="<?= (int)$r['id'] ?>"><?= h($r['codice']) ?> - <?= h(short_text($r['titolo'], 110)) ?><?= requirement_is_standard($r) ? ' [STD]' : '' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-2">
        <button class="btn btn-outline-primary w-100">Aggiungi</button>
      </div>
    </form>
  </div>
</div>

<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="exclude_selected">
  <div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold bg-success text-white">REQUISITI CATALOGO</div>
    <?php render_requirement_table($requisiti_catalogo, 'Nessun requisito di catalogo assegnato.'); ?>
  </div>

  <div class="card shadow-sm mb-4 border-secondary">
    <div class="card-header fw-semibold bg-light text-secondary">
      REQUISITI STANDARD
      <span class="small ms-2">Implementati di default: non devono essere presi in carico come requisiti assegnati.</span>
    </div>
    <?php render_requirement_table($requisiti_standard, 'Nessun requisito standard applicabile.', true, 'table-secondary'); ?>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
      <div class="flex-grow-1">
        <label class="form-label">Nota esclusione opzionale</label>
        <input class="form-control" name="override_note" placeholder="Motivo falso positivo / esclusione">
      </div>
      <button class="btn btn-outline-danger">Escludi requisiti selezionati</button>
    </div>
  </div>
</form>

<?php if ($requisiti_esclusi): ?>
<div class="card shadow-sm mb-4 border-warning">
  <div class="card-header fw-semibold">Requisiti esclusi manualmente</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Codice</th><th>Titolo</th><th>Nota</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($requisiti_esclusi as $r): ?>
        <tr>
          <td><code><?= h($r['codice']) ?></code></td>
          <td><?= h($r['titolo']) ?></td>
          <td><?= h($r['override_note']) ?></td>
          <td class="text-end">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="restore_catalog">
              <input type="hidden" name="requisito_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-outline-warning">Ripristina</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">SERVIZI</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>Servizio</th><th>Owner</th><th>Macro</th></tr></thead>
      <tbody>
      <?php foreach ($servizi as $s): ?>
        <tr>
          <td><strong><?= h($s['servizio_elementare']) ?></strong></td>
          <td><?= h($s['reparto_owner']) ?></td>
          <td><?= h($s['macro_service']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$servizi): ?>
        <tr><td colspan="3" class="text-center text-muted py-4">Nessun servizio suggerito calcolato.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
