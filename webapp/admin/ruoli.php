<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('ruoli_permessi', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action', 'save');
    if ($action === 'delete') {
        require_permission('ruoli_permessi', 'delete');
        $result = delete_ruolo((int)post('id'));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    } else {
        require_permission('ruoli_permessi', (int)post('id') > 0 ? 'update' : 'create');
        $result = save_ruolo($_POST);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    }
    redirect(APP_BASE_URL . '/admin/ruoli.php');
}

$funzioni = get_rbac_funzioni();
$ruoli = get_ruoli(false);
$edit = isset($_GET['id']) ? get_ruolo((int)$_GET['id']) : null;
$permessi = $edit ? get_ruolo_permessi((int)$edit['id']) : [];
$canCreate = has_permission('ruoli_permessi', 'create');
$canUpdate = has_permission('ruoli_permessi', 'update');
$canDelete = has_permission('ruoli_permessi', 'delete');
$page_title = 'Ruoli e permessi';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <div class="col-xl-5">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold"><?= $edit ? 'Modifica ruolo' : 'Nuovo ruolo' ?></div>
      <div class="card-body">
        <?php if (!$edit && !$canCreate): ?>
          <div class="alert alert-warning mb-0">Puoi leggere i ruoli, ma non crearne di nuovi.</div>
        <?php elseif ($edit && !$canUpdate): ?>
          <div class="alert alert-warning mb-0">Puoi leggere i ruoli, ma non modificarli.</div>
        <?php else: ?>
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input class="form-control" name="nome" value="<?= h($edit['nome'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Codice</label>
            <input class="form-control" name="codice" value="<?= h($edit['codice'] ?? '') ?>" required <?= $edit && (int)$edit['sistema'] === 1 ? 'readonly' : '' ?>>
            <div class="form-text">Minuscole, numeri e underscore.</div>
          </div>
          <div class="col-12">
            <label class="form-label">Descrizione</label>
            <textarea class="form-control" name="descrizione" rows="2"><?= h($edit['descrizione'] ?? '') ?></textarea>
          </div>
          <div class="col-12 form-check ms-2">
            <input class="form-check-input" type="checkbox" name="attivo" id="attivo" <?= (int)($edit['attivo'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="attivo">Attivo</label>
          </div>
          <div class="col-12">
            <div class="table-responsive border rounded">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Funzione</th>
                    <?php foreach (crud_actions() as $actionLabel): ?>
                      <th class="text-center"><?= h($actionLabel) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($funzioni as $funzione): ?>
                    <?php $code = (string)$funzione['codice']; ?>
                    <tr>
                      <td>
                        <strong><?= h($funzione['nome']) ?></strong>
                        <div class="small text-muted"><?= h($code) ?></div>
                      </td>
                      <?php foreach (crud_actions() as $actionCode => $actionLabel): ?>
                        <td class="text-center">
                          <input class="form-check-input" type="checkbox" name="permessi[<?= h($code) ?>][<?= h($actionCode) ?>]" value="1" <?= !empty($permessi[$code][$actionCode]) ? 'checked' : '' ?>>
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-primary"><?= $edit ? 'Salva ruolo' : 'Crea ruolo' ?></button>
            <?php if ($edit): ?><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/ruoli.php">Nuovo</a><?php endif; ?>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Ruoli applicativi</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Ruolo</th><th>Tipo</th><th>Utenti</th><th>Stato</th><th class="text-end">Azioni</th></tr></thead>
          <tbody>
          <?php foreach ($ruoli as $ruolo): ?>
            <tr>
              <td>
                <strong><?= h($ruolo['nome']) ?></strong><br>
                <code><?= h($ruolo['codice']) ?></code>
                <?php if (!empty($ruolo['descrizione'])): ?><div class="small text-muted"><?= h($ruolo['descrizione']) ?></div><?php endif; ?>
              </td>
              <td><?= (int)$ruolo['sistema'] === 1 ? '<span class="badge text-bg-info">Sistema</span>' : '<span class="badge text-bg-light">Custom</span>' ?></td>
              <td><?= (int)$ruolo['utenti_count'] ?></td>
              <td><span class="badge text-bg-<?= (int)$ruolo['attivo'] === 1 ? 'success' : 'secondary' ?>"><?= (int)$ruolo['attivo'] === 1 ? 'Attivo' : 'Disattivo' ?></span></td>
              <td class="text-end text-nowrap">
                <?php if ($canUpdate): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/admin/ruoli.php?id=<?= (int)$ruolo['id'] ?>">Modifica</a>
                <?php endif; ?>
                <?php if ($canDelete && (int)$ruolo['sistema'] !== 1): ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('Disattivare questo ruolo?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$ruolo['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Disattiva</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$ruoli): ?><tr><td colspan="5" class="text-center text-muted py-4">Nessun ruolo censito.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
