<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('auth_settings', 'read');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_permission('auth_settings', 'update');
    $action = (string)post('action', 'save');
    if ($action === 'test_ldap') {
        save_auth_settings($_POST);
        $result = test_ldap_connection();
    } elseif ($action === 'test_ldap_user') {
        save_auth_settings($_POST);
        $result = test_ldap_user_search((string)post('ldap_test_username', ''));
    } elseif ($action === 'test_oidc') {
        save_auth_settings($_POST);
        $result = test_oidc_connection();
    } else {
        $result = save_auth_settings($_POST);
    }
    flash($result['ok'] ? 'success' : 'error', $result['message']);
    redirect(APP_BASE_URL . '/admin/auth_settings.php');
}

$settings = auth_settings();
$ruoli = get_ruoli(true);
$canUpdate = has_permission('auth_settings', 'update');
$page_title = 'Configurazione autenticazione';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Configurazione autenticazione</h1>
    <div class="text-muted">Parametri per login locale, LDAP e OIDC/Keycloak.</div>
  </div>
</div>

<form method="post">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-xl-6">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">LDAP</div>
        <div class="card-body row g-3">
          <div class="col-12 form-check ms-2">
            <input class="form-check-input" type="checkbox" name="ldap_enabled" id="ldap_enabled" value="1" <?= auth_setting_bool('ldap_enabled') ? 'checked' : '' ?> <?= $canUpdate ? '' : 'disabled' ?>>
            <label class="form-check-label" for="ldap_enabled">Abilita autenticazione LDAP</label>
          </div>
          <div class="col-md-8">
            <label class="form-label">Host LDAP</label>
            <input class="form-control" name="ldap_host" value="<?= h($settings['ldap_host'] ?? '') ?>" placeholder="ldap.example.local" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label">Ruolo default</label>
            <select class="form-select" name="ldap_default_role" <?= $canUpdate ? '' : 'disabled' ?>>
              <?php foreach ($ruoli as $ruolo): ?>
                <option value="<?= h($ruolo['codice']) ?>" <?= ($settings['ldap_default_role'] ?? 'utente') === $ruolo['codice'] ? 'selected' : '' ?>><?= h($ruolo['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Porta LDAP</label>
            <input class="form-control" type="number" name="ldap_port" value="<?= h($settings['ldap_port'] ?? '') ?>" placeholder="389 / 636" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label">Cifratura</label>
            <select class="form-select" name="ldap_encryption" <?= $canUpdate ? '' : 'disabled' ?>>
              <?php foreach (['none' => 'Nessuna / LDAP', 'starttls' => 'StartTLS', 'ldaps' => 'LDAPS'] as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= ($settings['ldap_encryption'] ?? 'none') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Versione protocollo</label>
            <select class="form-select" name="ldap_protocol_version" <?= $canUpdate ? '' : 'disabled' ?>>
              <?php foreach (['3' => 'LDAP v3', '2' => 'LDAP v2'] as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= (string)($settings['ldap_protocol_version'] ?? '3') === (string)$value ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">LDAP URI avanzata opzionale</label>
            <input class="form-control" name="ldap_uri" value="<?= h($settings['ldap_uri'] ?? '') ?>" placeholder="Se valorizzata sovrascrive host/porta/cifratura" <?= $canUpdate ? '' : 'disabled' ?>>
            <div class="form-text">Normalmente lascia vuoto e usa host, porta e cifratura. Esempio avanzato: `ldaps://ldap.example.local:636`.</div>
          </div>
          <div class="col-12">
            <label class="form-label">Base DN</label>
            <input class="form-control" name="ldap_base_dn" value="<?= h($settings['ldap_base_dn'] ?? '') ?>" placeholder="DC=example,DC=local" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-6">
            <label class="form-label">Bind DN servizio</label>
            <input class="form-control" name="ldap_bind_dn" value="<?= h($settings['ldap_bind_dn'] ?? '') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-6">
            <label class="form-label">Bind password</label>
            <input class="form-control" type="password" name="ldap_bind_password" placeholder="<?= $settings['ldap_bind_password'] !== '' ? 'Lascia vuoto per mantenere il valore attuale' : '' ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-12">
            <label class="form-label">Filtro utente</label>
            <input class="form-control" name="ldap_user_filter" value="<?= h($settings['ldap_user_filter'] ?? '(sAMAccountName={username})') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
            <div class="form-text">Deve contenere `{username}`. Esempi: `(uid={username})`, `(sAMAccountName={username})`, `(&(objectClass=person)(uid={username}))`. Non usare filtri generici come `(objectClass=*)`.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Attr. username</label>
            <input class="form-control" name="ldap_attr_username" value="<?= h($settings['ldap_attr_username'] ?? 'sAMAccountName') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-3">
            <label class="form-label">Attr. email</label>
            <input class="form-control" name="ldap_attr_email" value="<?= h($settings['ldap_attr_email'] ?? 'mail') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-3">
            <label class="form-label">Attr. nome</label>
            <input class="form-control" name="ldap_attr_first_name" value="<?= h($settings['ldap_attr_first_name'] ?? 'givenName') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-3">
            <label class="form-label">Attr. cognome</label>
            <input class="form-control" name="ldap_attr_last_name" value="<?= h($settings['ldap_attr_last_name'] ?? 'sn') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-12">
            <?php if ($canUpdate): ?>
              <button class="btn btn-outline-primary" name="action" value="test_ldap">Test connessione LDAP</button>
            <?php endif; ?>
            <div class="form-text">Il test salva prima i valori correnti del form, poi verifica connessione, eventuale StartTLS, bind e Base DN.</div>
          </div>
          <div class="col-md-8">
            <label class="form-label">Username test ricerca</label>
            <input class="form-control" name="ldap_test_username" value="<?= h((string)post('ldap_test_username', '')) ?>" placeholder="es. mrossi" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <?php if ($canUpdate): ?>
              <button class="btn btn-outline-secondary w-100" name="action" value="test_ldap_user">Cerca utente LDAP</button>
            <?php endif; ?>
          </div>
          <div class="col-12">
            <div class="form-text">La ricerca salva prima i valori correnti, poi mostra filtro effettivo, DN e attributi mappati. Serve per capire perché il login LDAP fallisce.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-6">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">OIDC / Keycloak</div>
        <div class="card-body row g-3">
          <div class="col-12 form-check ms-2">
            <input class="form-check-input" type="checkbox" name="oidc_enabled" id="oidc_enabled" value="1" <?= auth_setting_bool('oidc_enabled') ? 'checked' : '' ?> <?= $canUpdate ? '' : 'disabled' ?>>
            <label class="form-check-label" for="oidc_enabled">Abilita login OIDC / Keycloak</label>
          </div>
          <div class="col-md-8">
            <label class="form-label">Issuer</label>
            <input class="form-control" name="oidc_issuer" value="<?= h($settings['oidc_issuer'] ?? '') ?>" placeholder="https://keycloak.example/realms/realm" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label">Ruolo default</label>
            <select class="form-select" name="oidc_default_role" <?= $canUpdate ? '' : 'disabled' ?>>
              <?php foreach ($ruoli as $ruolo): ?>
                <option value="<?= h($ruolo['codice']) ?>" <?= ($settings['oidc_default_role'] ?? 'utente') === $ruolo['codice'] ? 'selected' : '' ?>><?= h($ruolo['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Client ID</label>
            <input class="form-control" name="oidc_client_id" value="<?= h($settings['oidc_client_id'] ?? '') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-6">
            <label class="form-label">Client secret</label>
            <input class="form-control" type="password" name="oidc_client_secret" placeholder="<?= $settings['oidc_client_secret'] !== '' ? 'Lascia vuoto per mantenere il valore attuale' : '' ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-12">
            <label class="form-label">Redirect URI</label>
            <input class="form-control" name="oidc_redirect_uri" value="<?= h($settings['oidc_redirect_uri'] ?? '') ?>" placeholder="<?= h(oidc_redirect_uri()) ?>" <?= $canUpdate ? '' : 'disabled' ?>>
            <div class="form-text">Se vuoto viene calcolato automaticamente. Configuralo anche nel client Keycloak.</div>
          </div>
          <div class="col-12">
            <label class="form-label">Scope</label>
            <input class="form-control" name="oidc_scope" value="<?= h($settings['oidc_scope'] ?? 'openid profile email') ?>" <?= $canUpdate ? '' : 'disabled' ?>>
          </div>
          <div class="col-12">
            <?php if ($canUpdate): ?>
              <button class="btn btn-outline-primary" name="action" value="test_oidc">Test connessione OIDC</button>
            <?php endif; ?>
            <div class="form-text">Il test salva prima i valori correnti del form, poi verifica la discovery `.well-known/openid-configuration` e gli endpoint Keycloak principali.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div class="text-muted small">
            Le variabili Docker restano fallback. I valori salvati qui hanno priorità runtime.
          </div>
          <?php if ($canUpdate): ?>
            <button class="btn btn-primary" name="action" value="save">Salva configurazione</button>
          <?php else: ?>
            <span class="badge text-bg-secondary">Sola lettura</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
