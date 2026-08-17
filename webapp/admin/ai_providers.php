<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('ai_settings', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action', 'save');
    if ($action === 'delete') {
        require_permission('ai_settings', 'delete');
        $result = delete_ai_provider((int)post('id'));
    } elseif ($action === 'test') {
        require_permission('ai_settings', 'update');
        $provider = $_POST;
        $id = (int)($provider['id'] ?? 0);
        if ($id > 0 && trim((string)($provider['api_key'] ?? '')) === '') {
            $current = get_ai_provider($id);
            if ($current) {
                $provider['api_key'] = (string)($current['api_key'] ?? '');
            }
        }
        $result = test_ai_provider($provider);
    } else {
        require_permission('ai_settings', (int)post('id') > 0 ? 'update' : 'create');
        $result = save_ai_provider($_POST);
    }
    flash($result['ok'] ? 'success' : 'error', $result['message']);
    redirect(APP_BASE_URL . '/admin/ai_providers.php' . ((int)post('id') > 0 ? '?id=' . (int)post('id') : ''));
}

$providers = get_ai_providers(false);
$edit = isset($_GET['id']) ? get_ai_provider((int)$_GET['id']) : false;
$canCreate = has_permission('ai_settings', 'create');
$canUpdate = has_permission('ai_settings', 'update');
$canDelete = has_permission('ai_settings', 'delete');
$page_title = 'Configurazione IA';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0"><i class="bi bi-cpu me-2"></i>Configurazione IA</h1>
    <div class="text-muted">Provider e modelli disponibili per la Threat Analysis.</div>
  </div>
  <?php if ($edit): ?>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/ai_providers.php">Nuovo provider</a>
  <?php endif; ?>
</div>

<div class="alert alert-info small">
  <strong>Endpoint Docker:</strong> il browser non chiama direttamente l'IA. La richiesta parte dal container PHP.
  Se Ollama espone la porta sul PC host, <code>http://host.docker.internal:11434</code> è corretto dal container.
  Se app e Ollama sono nella stessa rete Docker, puoi usare il nome servizio, ad esempio <code>http://ollama:11434</code>.
</div>

<div class="row g-4">
  <div class="col-xl-5">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold"><?= $edit ? 'Modifica provider' : 'Nuovo provider' ?></div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="col-md-8">
            <label class="form-label">Nome</label>
            <input class="form-control" name="nome" value="<?= h($edit['nome'] ?? 'Ollama locale') ?>" required <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="provider_type" <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
              <?php foreach (ai_provider_types() as $type => $label): ?>
                <option value="<?= h($type) ?>" <?= ($edit['provider_type'] ?? 'ollama') === $type ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Base URL</label>
            <input class="form-control" name="base_url" value="<?= h($edit['base_url'] ?? ollama_base_url()) ?>" placeholder="http://host.docker.internal:11434" required <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
            <div class="form-text">Ollama: URL root. OpenAI-compatible: URL root <code>/v1</code>, es. <code>https://api.openai.com/v1</code>.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Modello default</label>
            <input class="form-control" name="default_model" value="<?= h($edit['default_model'] ?? OLLAMA_MODEL) ?>" placeholder="cybersec:latest" <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-6">
            <label class="form-label">Timeout secondi</label>
            <input class="form-control" type="number" min="30" max="1800" name="timeout_seconds" value="<?= h((string)($edit['timeout_seconds'] ?? OLLAMA_TIMEOUT_SECONDS)) ?>" <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
          </div>
          <div class="col-12">
            <label class="form-label">API key</label>
            <input class="form-control" type="password" name="api_key" placeholder="<?= !empty($edit['api_key']) ? 'Lascia vuoto per mantenere il valore attuale' : 'Opzionale per Ollama locale' ?>" <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
          </div>
          <div class="col-12">
            <label class="form-label">Modelli configurati</label>
            <textarea class="form-control font-monospace" name="model_list" rows="5" placeholder="Un modello per riga, oppure separati da virgola" <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>><?= h($edit['model_list'] ?? '') ?></textarea>
            <div class="form-text">Per Ollama la lista viene integrata con i modelli letti da <code>/api/tags</code>.</div>
          </div>
          <div class="col-md-6 form-check ms-2">
            <input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1" <?= (int)($edit['enabled'] ?? 1) === 1 ? 'checked' : '' ?> <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
            <label class="form-check-label" for="enabled">Abilitato</label>
          </div>
          <div class="col-md-5 form-check ms-2">
            <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1" <?= (int)($edit['is_default'] ?? 0) === 1 ? 'checked' : '' ?> <?= ($edit ? $canUpdate : $canCreate) ? '' : 'disabled' ?>>
            <label class="form-check-label" for="is_default">Default</label>
          </div>
          <div class="col-12 d-flex gap-2">
            <?php if (($edit && $canUpdate) || (!$edit && $canCreate)): ?>
              <button class="btn btn-primary" name="action" value="save">Salva</button>
              <button class="btn btn-outline-primary" name="action" value="test">Test connessione</button>
            <?php endif; ?>
            <?php if ($edit && $canDelete): ?>
              <button class="btn btn-outline-danger ms-auto" name="action" value="delete" onclick="return confirm('Disabilitare questo provider IA?')">Disabilita</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Provider censiti</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Nome</th><th>Tipo</th><th>Base URL</th><th>Default</th><th>Stato</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($providers as $provider): ?>
            <tr>
              <td><?= h($provider['nome']) ?></td>
              <td><?= h(ai_provider_types()[$provider['provider_type']] ?? $provider['provider_type']) ?></td>
              <td><code><?= h($provider['base_url']) ?></code></td>
              <td><?= (int)$provider['is_default'] === 1 ? '<span class="badge text-bg-primary">Default</span>' : '' ?></td>
              <td><?= (int)$provider['enabled'] === 1 ? '<span class="badge text-bg-success">Abilitato</span>' : '<span class="badge text-bg-secondary">Disabilitato</span>' ?></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/admin/ai_providers.php?id=<?= (int)$provider['id'] ?>">Modifica</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$providers): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nessun provider IA censito.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
