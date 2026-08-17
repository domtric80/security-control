<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('ai_assistant', 'read');

$questionari = get_questionari();
$questionarioId = get_int('questionario_id', (int)($questionari[0]['id'] ?? 0));
$questionario = $questionarioId > 0 ? get_questionario($questionarioId) : false;
$analysisTypes = ai_analysis_types();
$selectedType = (string)($_GET['type'] ?? post('analysis_type', 'specific_requirements'));
if (!array_key_exists($selectedType, $analysisTypes)) {
    $selectedType = 'specific_requirements';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action');
    if ($action === 'run_analysis') {
        require_permission('ai_assistant', 'create');
        $result = run_ai_analysis((int)post('questionario_id'), (string)post('analysis_type'), (int)post('provider_id'), (string)post('model'));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        $runParam = !empty($result['run_id']) ? '&run_id=' . (int)$result['run_id'] : '';
        redirect(APP_BASE_URL . '/ai/index.php?questionario_id=' . (int)post('questionario_id') . '&type=' . urlencode((string)post('analysis_type')) . $runParam);
    }
    if (in_array($action, ['approve_suggestion', 'discard_suggestion', 'apply_suggestion'], true)) {
        require_permission('ai_assistant', 'update');
        $suggestionId = (int)post('suggestion_id');
        $note = trim((string)post('decision_note', ''));
        if ($action === 'apply_suggestion') {
            $result = ai_apply_suggestion($suggestionId, $note);
        } elseif ($action === 'discard_suggestion') {
            $result = ai_mark_suggestion($suggestionId, 'scartato', $note);
        } else {
            $result = ai_mark_suggestion($suggestionId, 'approvato', $note);
        }
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect(APP_BASE_URL . '/ai/index.php?questionario_id=' . (int)post('questionario_id') . '&type=' . urlencode((string)post('analysis_type')));
    }
}

$providers = get_ai_providers(true);
$provider = get_default_ai_provider();
$models = ai_provider_models($provider);
$runs = $questionario ? get_ai_runs($questionarioId, $selectedType) : [];
$requestedRunId = get_int('run_id', 0);
$validRunIds = array_map(fn($run) => (int)$run['id'], $runs);
$selectedRunId = $requestedRunId > 0 && in_array($requestedRunId, $validRunIds, true)
    ? $requestedRunId
    : (int)($runs[0]['id'] ?? 0);
$selectedRun = $selectedRunId > 0 ? get_ai_run($selectedRunId) : false;
$suggestions = ($questionario && $selectedRunId > 0) ? get_ai_suggestions($questionarioId, $selectedRunId) : [];
$page_title = 'Suggerimenti IA';
require __DIR__ . '/../includes/header.php';

function ai_status_badge(string $status): string {
    return match ($status) {
        'applicato' => '<span class="badge text-bg-success">Applicato</span>',
        'approvato' => '<span class="badge text-bg-primary">Approvato</span>',
        'scartato' => '<span class="badge text-bg-secondary">Scartato</span>',
        default => '<span class="badge text-bg-warning">Proposto</span>',
    };
}

function ai_apply_button_label(string $type): string {
    return match ($type) {
        'specific_requirement' => 'Crea requisito',
        'false_positive' => 'Escludi requisito',
        'explanation', 'executive_summary' => 'Pubblica',
        default => 'Applica',
    };
}

function ai_destination_label(string $type): string {
    return match ($type) {
        'specific_requirement' => 'Destinazione: requisiti specifici del questionario',
        'false_positive' => 'Destinazione: esclusioni nei risultati questionario',
        'explanation' => 'Destinazione: card “Spiegazioni IA pubblicate” in risultati.php',
        'executive_summary' => 'Destinazione: report executive in questa pagina',
        default => 'Destinazione: validazione suggerimento IA',
    };
}
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h1 class="h4 mb-1"><i class="bi bi-stars me-2"></i>Suggerimenti IA</h1>
    <div class="text-muted">Analisi assistite su questionario, requisiti, servizi e PIR con approvazione umana.</div>
  </div>
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/ai_providers.php">Configura IA</a>
</div>

<div class="row g-4">
  <div class="col-xl-4">
    <div class="card shadow-sm mb-4">
      <div class="card-header fw-semibold">Nuova analisi IA</div>
      <div class="card-body">
        <form method="post" class="row g-3" id="aiAnalysisForm">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="run_analysis">
          <div class="col-12">
            <label class="form-label">Questionario / progetto</label>
            <select class="form-select" name="questionario_id" onchange="location.href='<?= APP_BASE_URL ?>/ai/index.php?type=<?= h($selectedType) ?>&questionario_id=' + this.value" required>
              <?php foreach ($questionari as $q): ?>
                <option value="<?= (int)$q['id'] ?>" <?= (int)$q['id'] === $questionarioId ? 'selected' : '' ?>>
                  #<?= (int)$q['id'] ?> · <?= h($q['nome_progetto'] ?: 'Senza nome') ?><?= $q['task_jira'] ? ' · ' . h($q['task_jira']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Tipo analisi</label>
            <select class="form-select" name="analysis_type" onchange="location.href='<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= (int)$questionarioId ?>&type=' + this.value">
              <?php foreach ($analysisTypes as $key => $type): ?>
                <option value="<?= h($key) ?>" <?= $selectedType === $key ? 'selected' : '' ?>><?= h($type['label']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text"><?= h($analysisTypes[$selectedType]['description']) ?></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Provider</label>
            <select class="form-select" name="provider_id">
              <?php foreach ($providers as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)($provider['id'] ?? 0) ? 'selected' : '' ?>><?= h($p['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Modello</label>
            <?php if ($models): ?>
              <select class="form-select" name="model">
                <?php foreach ($models as $model): ?>
                  <option value="<?= h($model) ?>" <?= $model === ($provider['default_model'] ?? '') ? 'selected' : '' ?>><?= h($model) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input class="form-control" name="model" value="<?= h($provider['default_model'] ?? '') ?>" placeholder="modello">
            <?php endif; ?>
          </div>
          <div class="col-12 d-grid">
            <?php if (has_permission('ai_assistant', 'create')): ?>
              <button class="btn btn-primary" id="generateAiBtn" <?= $questionario ? '' : 'disabled' ?>><i class="bi bi-magic me-1"></i>Genera suggerimenti</button>
            <?php else: ?>
              <span class="badge text-bg-secondary">Sola lettura</span>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Storico analisi</div>
      <div class="list-group list-group-flush">
        <?php foreach ($runs as $run): ?>
          <a class="list-group-item list-group-item-action <?= (int)$run['id'] === $selectedRunId ? 'active' : '' ?>" href="<?= APP_BASE_URL ?>/ai/index.php?questionario_id=<?= (int)$questionarioId ?>&type=<?= h($selectedType) ?>&run_id=<?= (int)$run['id'] ?>">
            <div class="d-flex justify-content-between">
              <strong>#<?= (int)$run['id'] ?> · <?= h($analysisTypes[$run['analysis_type']]['label'] ?? $run['analysis_type']) ?></strong>
              <span><?= h($run['status']) ?></span>
            </div>
            <div class="small"><?= h($run['created_at']) ?> · <?= h((string)round(((int)$run['duration_ms']) / 1000, 1)) ?>s</div>
          </a>
        <?php endforeach; ?>
        <?php if (!$runs): ?>
          <div class="list-group-item text-muted">Nessuna analisi presente.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card shadow-sm mb-4 d-none" id="aiProgressCard">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Analisi IA in corso</span>
        <span class="badge text-bg-info" id="aiProgressBadge">In attesa</span>
      </div>
      <div class="card-body">
        <div class="progress mb-3" role="progressbar" aria-label="Analisi IA in corso">
          <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
        <div class="small text-muted mb-2">
          <span id="aiProgressStatus">Preparazione...</span>
          <span class="ms-2" id="aiProgressElapsed"></span>
        </div>
        <div class="small text-muted mb-2">I token vengono mostrati appena arrivano. Il salvataggio dei suggerimenti avviene alla fine.</div>
        <pre class="border rounded bg-light p-3 mb-3" id="aiLiveOutput" style="white-space: pre-wrap; max-height: 360px; overflow:auto;"></pre>
        <a class="btn btn-sm btn-outline-primary d-none" id="aiOpenResult" href="#">Apri suggerimenti salvati</a>
      </div>
    </div>

    <?php if ($questionario): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-body small">
          <div class="row g-2">
            <div class="col-md-5"><strong>Progetto:</strong> <?= h($questionario['nome_progetto'] ?? '') ?></div>
            <div class="col-md-3"><strong>Task <?= acronym_help('JIRA', 'Identificativo del task su Jira') ?>:</strong> <?= h($questionario['task_jira'] ?? '') ?></div>
            <div class="col-md-4"><strong>Business line:</strong> <?= h($questionario['business_line'] ?? '') ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($selectedRun): ?>
      <?php $parsed = json_decode((string)($selectedRun['parsed_json'] ?? '{}'), true); ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Sintesi analisi #<?= (int)$selectedRun['id'] ?></span>
          <span class="badge text-bg-<?= $selectedRun['status'] === 'ok' ? 'success' : 'danger' ?>"><?= h($selectedRun['status']) ?></span>
        </div>
        <div class="card-body">
          <?php if ($selectedRun['status'] === 'error'): ?>
            <div class="alert alert-danger"><?= h($selectedRun['error_message'] ?? 'Errore IA') ?></div>
          <?php else: ?>
            <p class="mb-0"><?= h((string)($parsed['summary'] ?? 'Analisi completata.')) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($selectedType === 'executive_report' && $selectedRun['status'] === 'ok'): ?>
        <?php
          $executiveSuggestions = array_values(array_filter($suggestions, fn($s) => in_array((string)$s['suggestion_type'], ['executive_summary', 'note'], true)));
        ?>
        <div class="card shadow-sm mb-4 border-info">
          <div class="card-header fw-semibold bg-info-subtle"><i class="bi bi-file-earmark-text me-1"></i>Dettaglio report executive</div>
          <div class="card-body">
            <?php foreach ($executiveSuggestions as $item): ?>
              <?php $payload = json_decode((string)$item['payload_json'], true) ?: []; ?>
              <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between gap-2 mb-2">
                  <strong><?= h($item['title']) ?></strong>
                  <?= ai_status_badge((string)$item['status']) ?>
                </div>
                <div style="white-space: pre-wrap;"><?= h((string)($item['body'] ?: ($payload['raw'] ?? ''))) ?></div>
                <?php if (!empty($item['rationale'])): ?>
                  <div class="small text-muted mt-2"><strong>Fonti/motivazione:</strong> <?= h((string)$item['rationale']) ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <?php if (!$executiveSuggestions): ?>
              <div class="alert alert-warning mb-0">La run Ã¨ stata salvata, ma non contiene un report executive strutturato. Rigenera il report con il prompt aggiornato.</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php elseif ($questionario): ?>
      <div class="card shadow-sm mb-4 border-info">
        <div class="card-body text-muted">
          Nessuna analisi di tipo <strong><?= h($analysisTypes[$selectedType]['label']) ?></strong> ancora generata per questo questionario.
          Usa il pulsante <strong>Genera suggerimenti</strong> per creare il primo risultato.
        </div>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Suggerimenti</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Stato</th><th>Tipo</th><th>Suggerimento</th><th>Priorità</th><th>Conf.</th><th>Azioni</th></tr></thead>
          <tbody>
          <?php foreach ($suggestions as $suggestion): ?>
            <?php $payload = json_decode((string)$suggestion['payload_json'], true) ?: []; ?>
            <tr>
              <td><?= ai_status_badge((string)$suggestion['status']) ?></td>
              <td><code><?= h($suggestion['suggestion_type']) ?></code></td>
              <td>
                <strong><?= h($suggestion['title']) ?></strong>
                <div class="small text-muted"><?= h(short_text((string)$suggestion['body'], 240)) ?></div>
                <?php if (!empty($suggestion['rationale'])): ?><div class="small"><strong>Motivo:</strong> <?= h(short_text((string)$suggestion['rationale'], 220)) ?></div><?php endif; ?>
                <div class="small text-info"><?= h(ai_destination_label((string)$suggestion['suggestion_type'])) ?></div>
                <?php if ($payload): ?><details class="small mt-1"><summary>Payload</summary><pre class="bg-light border rounded p-2 mb-0"><?= h(requisito_version_json($payload)) ?></pre></details><?php endif; ?>
              </td>
              <td><?= h($suggestion['priority']) ?></td>
              <td><?= $suggestion['confidence'] !== null ? h((string)$suggestion['confidence']) : '' ?></td>
              <td class="text-nowrap">
                <?php if ($suggestion['status'] === 'proposto' && has_permission('ai_assistant', 'update')): ?>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="questionario_id" value="<?= (int)$questionarioId ?>">
                    <input type="hidden" name="analysis_type" value="<?= h($selectedType) ?>">
                    <input type="hidden" name="suggestion_id" value="<?= (int)$suggestion['id'] ?>">
                    <button class="btn btn-sm btn-outline-success" name="action" value="apply_suggestion" title="<?= h(ai_destination_label((string)$suggestion['suggestion_type'])) ?>"><?= h(ai_apply_button_label((string)$suggestion['suggestion_type'])) ?></button>
                  </form>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="questionario_id" value="<?= (int)$questionarioId ?>">
                    <input type="hidden" name="analysis_type" value="<?= h($selectedType) ?>">
                    <input type="hidden" name="suggestion_id" value="<?= (int)$suggestion['id'] ?>">
                    <button class="btn btn-sm btn-outline-primary" name="action" value="approve_suggestion">Approva</button>
                  </form>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="questionario_id" value="<?= (int)$questionarioId ?>">
                    <input type="hidden" name="analysis_type" value="<?= h($selectedType) ?>">
                    <input type="hidden" name="suggestion_id" value="<?= (int)$suggestion['id'] ?>">
                    <button class="btn btn-sm btn-outline-secondary" name="action" value="discard_suggestion">Scarta</button>
                  </form>
                <?php else: ?>
                  <span class="text-muted small">Nessuna azione</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$suggestions): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nessun suggerimento per questa analisi.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const form = document.getElementById('aiAnalysisForm');
  if (!form || !window.fetch || !window.ReadableStream) return;

  const card = document.getElementById('aiProgressCard');
  const badge = document.getElementById('aiProgressBadge');
  const statusBox = document.getElementById('aiProgressStatus');
  const elapsedBox = document.getElementById('aiProgressElapsed');
  const output = document.getElementById('aiLiveOutput');
  const resultLink = document.getElementById('aiOpenResult');
  const button = document.getElementById('generateAiBtn');
  let elapsedTimer = null;

  function setStatus(message, tone = 'info') {
    statusBox.textContent = message;
    badge.className = 'badge text-bg-' + tone;
    badge.textContent = message;
  }

  function startElapsedTimer() {
    const startedAt = Date.now();
    clearInterval(elapsedTimer);
    elapsedTimer = setInterval(() => {
      const seconds = Math.floor((Date.now() - startedAt) / 1000);
      const minutes = Math.floor(seconds / 60);
      elapsedBox.textContent = 'Tempo trascorso: ' + minutes + ':' + String(seconds % 60).padStart(2, '0');
    }, 1000);
  }

  function stopElapsedTimer() {
    clearInterval(elapsedTimer);
    elapsedTimer = null;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    card.classList.remove('d-none');
    resultLink.classList.add('d-none');
    output.textContent = '';
    elapsedBox.textContent = '';
    button.disabled = true;
    startElapsedTimer();
    setStatus('Preparo richiesta...', 'info');

    try {
      const response = await fetch('<?= APP_BASE_URL ?>/ai/run_stream.php', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin'
      });
      if (!response.ok || !response.body) {
        throw new Error('Risposta HTTP non valida: ' + response.status);
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      while (true) {
        const { value, done } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() || '';
        for (const line of lines) {
          if (!line.trim()) continue;
          const event = JSON.parse(line);
          if (event.type === 'status') {
            setStatus(event.message || 'Analisi in corso...', 'info');
          } else if (event.type === 'chunk') {
            output.textContent += event.text || '';
            output.scrollTop = output.scrollHeight;
            setStatus('La IA sta rispondendo...', 'primary');
          } else if (event.type === 'done') {
            setStatus('Completata', 'success');
            stopElapsedTimer();
            if (event.run_id) {
              resultLink.href = '<?= APP_BASE_URL ?>/ai/index.php?questionario_id=' + encodeURIComponent(form.questionario_id.value) + '&type=' + encodeURIComponent(form.analysis_type.value) + '&run_id=' + encodeURIComponent(event.run_id);
              resultLink.classList.remove('d-none');
            }
          } else if (event.type === 'error') {
            setStatus('Errore', 'danger');
            stopElapsedTimer();
            output.textContent += '\n\n[ERRORE] ' + (event.message || 'Errore non specificato');
            if (event.run_id) {
              resultLink.href = '<?= APP_BASE_URL ?>/ai/index.php?questionario_id=' + encodeURIComponent(form.questionario_id.value) + '&type=' + encodeURIComponent(form.analysis_type.value) + '&run_id=' + encodeURIComponent(event.run_id);
              resultLink.classList.remove('d-none');
            }
          }
        }
      }
    } catch (error) {
      setStatus('Errore', 'danger');
      stopElapsedTimer();
      output.textContent += '\n\n[ERRORE] ' + error.message;
    } finally {
      button.disabled = false;
    }
  });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
