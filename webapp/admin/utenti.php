<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('utenti', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)post('action', 'save');
    if ($action === 'delete') {
        require_permission('utenti', 'delete');
        delete_utente((int)post('id'));
        flash('success', 'Utente disattivato.');
    } else {
        require_permission('utenti', (int)post('id') > 0 ? 'update' : 'create');
        $result = save_utente_rbac($_POST);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
    }
    redirect(APP_BASE_URL . '/admin/utenti.php');
}

$utenti = get_utenti(false);
$edit = isset($_GET['id']) ? get_utente((int)$_GET['id']) : null;
$ruoli = get_ruoli(true);
$selectedRoleIds = $edit ? get_user_role_ids((int)$edit['id']) : [get_default_role_id('utente')];
$canCreate = has_permission('utenti', 'create');
$canUpdate = has_permission('utenti', 'update');
$canDelete = has_permission('utenti', 'delete');
$page_title = 'Utenti';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold"><?= $edit ? 'Modifica utente' : 'Nuovo utente' ?></div>
      <div class="card-body">
        <?php if (!$edit && !$canCreate): ?>
          <div class="alert alert-warning mb-0">Puoi leggere gli utenti, ma non crearne di nuovi.</div>
        <?php elseif ($edit && !$canUpdate): ?>
          <div class="alert alert-warning mb-0">Puoi leggere gli utenti, ma non modificarli.</div>
        <?php else: ?>
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="col-12">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" value="<?= h($edit['username'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input class="form-control" name="nome" value="<?= h($edit['nome'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cognome</label>
            <input class="form-control" name="cognome" value="<?= h($edit['cognome'] ?? '') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Password <?= $edit ? '(lascia vuota per non cambiarla)' : '' ?></label>
            <input class="form-control" type="password" name="password" <?= $edit ? '' : 'required' ?>>
          </div>
          <div class="col-12">
            <label class="form-label">Conferma password</label>
            <input class="form-control" type="password" name="password_confirm" <?= $edit ? '' : 'required' ?>>
          </div>
          <div class="col-12">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?= h($edit['email'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Conferma email</label>
            <input class="form-control" type="email" name="email_confirm" value="<?= h($edit['email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefono</label>
            <input class="form-control" name="telefono" value="<?= h($edit['telefono'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Reparto</label>
            <input class="form-control" name="reparto" value="<?= h($edit['reparto'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Ruolo organizzativo</label>
            <input class="form-control" name="ruolo" value="<?= h($edit['ruolo'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Ruoli applicativi RBAC</label>
            <select class="form-select" name="role_ids[]" multiple size="<?= max(4, min(8, count($ruoli))) ?>" required>
              <?php foreach ($ruoli as $ruolo): ?>
                <option value="<?= (int)$ruolo['id'] ?>" <?= in_array((int)$ruolo['id'], $selectedRoleIds, true) ? 'selected' : '' ?>>
                  <?= h($ruolo['nome']) ?> (<?= h($ruolo['codice']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Puoi assegnare più ruoli. I permessi si sommano.</div>
          </div>
          <div class="col-md-6 form-check ms-2">
            <input class="form-check-input" type="checkbox" name="attivo" id="attivo" <?= (int)($edit['attivo'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="attivo">Attivo</label>
          </div>
          <div class="col-12">
            <button class="btn btn-primary"><?= $edit ? 'Salva utente' : 'Crea utente' ?></button>
            <?php if ($edit): ?><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/utenti.php">Nuovo</a><?php endif; ?>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Anagrafica utenti</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Utente</th><th>Contatti</th><th>Ruolo/Reparto</th><th>Ruoli RBAC</th><th>Stato</th><th class="text-end">Azioni</th></tr></thead>
          <tbody>
          <?php foreach ($utenti as $u): ?>
            <tr>
              <td><strong><?= h(user_label($u)) ?></strong><br><code><?= h($u['username']) ?></code></td>
              <td class="small"><?= h($u['email'] ?? '') ?><br><?= h($u['telefono'] ?? '') ?></td>
              <td class="small"><?= h($u['ruolo'] ?? '') ?><br><?= h($u['reparto'] ?? '') ?></td>
              <td class="small"><?= h(get_user_roles_label((int)$u['id']) ?: 'Nessun ruolo') ?></td>
              <td>
                <span class="badge text-bg-<?= (int)$u['attivo'] === 1 ? 'success' : 'secondary' ?>"><?= (int)$u['attivo'] === 1 ? 'Attivo' : 'Disattivo' ?></span>
              </td>
              <td class="text-end">
                <?php if ($canUpdate): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= APP_BASE_URL ?>/admin/utenti.php?id=<?= (int)$u['id'] ?>">Modifica</a>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Disattivare questo utente?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Disattiva</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$utenti): ?><tr><td colspan="6" class="text-center text-muted py-4">Nessun utente censito.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
