<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
if (!$user) {
    $page_title = 'Profilo';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-header fw-semibold">Profilo utente</div>
          <div class="card-body">
            <div class="alert alert-info mb-0">
              Stai usando l'admin tecnico bootstrap configurato da Docker. Non è un utente censito nel database,
              quindi non ha un profilo modificabile. Crea un utente con ruolo Admin per usare un profilo personale.
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $result = save_current_user_profile((int)$user['id'], $_POST);
    flash($result['ok'] ? 'success' : 'error', $result['message']);
    redirect(APP_BASE_URL . '/profilo.php');
}

$user = current_user();
$externalUser = is_external_user($user);
$authProvider = strtoupper((string)($user['auth_provider'] ?? 'local'));
$page_title = 'Profilo';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-xl-7 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Profilo utente</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" value="<?= h($user['username'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Ruoli applicativi</label>
            <input class="form-control" value="<?= h(get_user_roles_label((int)$user['id']) ?: 'Nessun ruolo') ?>" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tipo autenticazione</label>
            <input class="form-control" value="<?= h($authProvider) ?>" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input class="form-control" name="nome" value="<?= h($user['nome'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cognome</label>
            <input class="form-control" name="cognome" value="<?= h($user['cognome'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?= h($user['email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Conferma email</label>
            <input class="form-control" type="email" name="email_confirm" value="<?= h($user['email'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Telefono</label>
            <input class="form-control" name="telefono" value="<?= h($user['telefono'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Reparto</label>
            <input class="form-control" name="reparto" value="<?= h($user['reparto'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Ruolo organizzativo</label>
            <input class="form-control" name="ruolo" value="<?= h($user['ruolo'] ?? '') ?>">
          </div>

          <div class="col-12"><hr></div>
          <div class="col-12">
            <h2 class="h6 mb-1">Cambio password</h2>
            <?php if ($externalUser): ?>
              <div class="alert alert-secondary mb-0">
                Password gestita da <?= h($authProvider) ?>: non può essere modificata da questa applicazione.
              </div>
            <?php else: ?>
              <div class="text-muted small">Compila questi campi solo se vuoi cambiare password.</div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Password attuale</label>
            <input class="form-control" type="password" name="current_password" autocomplete="current-password" <?= $externalUser ? 'disabled' : '' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label">Nuova password</label>
            <input class="form-control" type="password" name="password" autocomplete="new-password" <?= $externalUser ? 'disabled' : '' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label">Conferma nuova password</label>
            <input class="form-control" type="password" name="password_confirm" autocomplete="new-password" <?= $externalUser ? 'disabled' : '' ?>>
          </div>
          <div class="col-12">
            <button class="btn btn-primary">Salva profilo</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
