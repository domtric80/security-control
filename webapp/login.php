<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(first_allowed_url());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $user = trim((string)post('username', ''));
    $pass = (string)post('password', '');
    if (login_throttled($user)) {
        flash('error', 'Troppi tentativi non riusciti. Riprova tra qualche minuto.');
        redirect(APP_BASE_URL . '/login.php');
    }
    if (LEGACY_ADMIN_ENABLED && ADMIN_USER !== '' && ADMIN_PASS !== '' && hash_equals(ADMIN_USER, $user) && hash_equals(ADMIN_PASS, $pass)) {
        record_login_attempt($user, true);
        secure_login_session();
        $_SESSION['is_admin'] = true;
        unset($_SESSION['user_id']);
        flash('success', 'Accesso effettuato.');
        redirect(first_allowed_url());
    }
    $dbUser = authenticate_user($user, $pass);
    if ($dbUser) {
        record_login_attempt($user, true);
        secure_login_session();
        $_SESSION['user_id'] = (int)$dbUser['id'];
        $_SESSION['is_admin'] = (int)$dbUser['is_admin'] === 1;
        touch_user_login((int)$dbUser['id']);
        flash('success', 'Accesso effettuato.');
        redirect(first_allowed_url());
    }
    $ldapUser = authenticate_ldap_user($user, $pass);
    if ($ldapUser) {
        record_login_attempt($user, true);
        secure_login_session();
        $_SESSION['user_id'] = (int)$ldapUser['id'];
        $_SESSION['is_admin'] = (int)$ldapUser['is_admin'] === 1;
        touch_user_login((int)$ldapUser['id']);
        flash('success', 'Accesso effettuato.');
        redirect(first_allowed_url());
    }
    record_login_attempt($user, false);
    flash('error', 'Credenziali non valide.');
}

$page_title = 'Login';
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/public/css/style.css">
</head>
<body class="bg-light">
  <main class="min-vh-100 d-flex align-items-center py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
          <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : h($flash['type']) ?>" role="alert">
              <?= h($flash['msg']) ?>
            </div>
          <?php endif; ?>
          <div class="card shadow-sm">
            <div class="card-header fw-semibold text-center">Login</div>
            <div class="card-body">
              <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                  <label class="form-label">Utente</label>
                  <input class="form-control" name="username" required autofocus>
                </div>
                <div class="mb-3">
                  <label class="form-label">Password</label>
                  <input class="form-control" name="password" type="password" required>
                </div>
                <button class="btn btn-primary w-100">Entra</button>
              </form>
              <?php if (auth_setting_bool('oidc_enabled')): ?>
                <div class="text-center text-muted small my-3">oppure</div>
                <a class="btn btn-outline-primary w-100" href="<?= APP_BASE_URL ?>/auth/oidc_start.php">Accedi con Keycloak</a>
              <?php endif; ?>
              <?php if (auth_setting_bool('ldap_enabled')): ?>
                <div class="form-text mt-3 text-center">Le credenziali LDAP sono accettate in questo form.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
