<?php
require_once __DIR__ . "/db.php";

// â”€â”€ Output helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function acronym_help(string $acronym, string $meaning): string {
    return '<abbr class="acronym-help" tabindex="0" title="' . h($meaning) . '" data-meaning="' . h($meaning) . '">'
        . h($acronym)
        . '</abbr>';
}

function redirect(string $url): never {
    header("Location: " . $url);
    exit;
}

function flash(string $type, string $msg): void {
    $_SESSION["flash"] = ["type" => $type, "msg" => $msg];
}

function get_flash(): ?array {
    $f = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);
    return $f;
}

// â”€â”€ Input helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function get_int(string $key, int $default = 0): int {
    return (int)($_GET[$key] ?? $_POST[$key] ?? $default);
}

function post(string $key, mixed $default = ""): mixed {
    return $_POST[$key] ?? $default;
}

// â”€â”€ CSRF â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function csrf_token(): string {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function csrf_field(): string {
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"" . h(csrf_token()) . "\">";
}

function verify_csrf(): void {
    $token = $_POST["csrf_token"] ?? "";
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit("Token CSRF non valido.");
    }
}

// â”€â”€ Auth â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function client_ip(): string {
    return substr((string)($_SERVER["REMOTE_ADDR"] ?? "0.0.0.0"), 0, 45);
}

function login_throttled(string $username): bool {
    $username = strtolower(trim($username));
    if ($username === "") {
        return false;
    }
    try {
        $st = get_db()->prepare(
            "SELECT COUNT(*)
             FROM auth_login_attempts
             WHERE username = ? AND ip_address = ? AND success = 0
               AND created_at >= (NOW() - INTERVAL 15 MINUTE)"
        );
        $st->execute([$username, client_ip()]);
        return (int)$st->fetchColumn() >= 5;
    } catch (Throwable $e) {
        return false;
    }
}

function record_login_attempt(string $username, bool $success): void {
    $username = strtolower(trim($username));
    if ($username === "") {
        return;
    }
    try {
        $db = get_db();
        $db->prepare("DELETE FROM auth_login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)")->execute();
        if ($success) {
            $db->prepare("DELETE FROM auth_login_attempts WHERE username = ? AND ip_address = ?")->execute([$username, client_ip()]);
            return;
        }
        $db->prepare("INSERT INTO auth_login_attempts (username, ip_address, success) VALUES (?, ?, 0)")
           ->execute([$username, client_ip()]);
    } catch (Throwable $e) {
        return;
    }
}

function secure_login_session(): void {
    session_regenerate_id(true);
    unset($_SESSION["csrf_token"]);
    $_SESSION["last_activity"] = time();
}

function destroy_current_session(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $params["path"] ?? "/",
            "domain" => $params["domain"] ?? "",
            "secure" => (bool)($params["secure"] ?? false),
            "httponly" => (bool)($params["httponly"] ?? true),
            "samesite" => $params["samesite"] ?? "Lax",
        ]);
    }
    session_destroy();
}

function enforce_session_idle_timeout(): void {
    $timeout = (int)(getenv("REQ_SESSION_IDLE_SECONDS") ?: 1800);
    if ($timeout <= 0 || (empty($_SESSION["user_id"]) && empty($_SESSION["is_admin"]))) {
        return;
    }
    $lastActivity = (int)($_SESSION["last_activity"] ?? time());
    if ((time() - $lastActivity) > $timeout) {
        destroy_current_session();
        redirect(APP_BASE_URL . "/login.php");
    }
    $_SESSION["last_activity"] = time();
}

function is_admin(): bool {
    enforce_session_idle_timeout();
    return ($_SESSION["is_admin"] ?? false) === true || has_permission("ruoli_permessi", "delete");
}

function current_user(): ?array {
    enforce_session_idle_timeout();
    $id = (int)($_SESSION["user_id"] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $st = get_db()->prepare("SELECT * FROM utenti WHERE id = ? AND attivo = 1");
    $st->execute([$id]);
    $user = $st->fetch();
    return $user ?: null;
}

function user_label(?array $user): string {
    if (!$user) {
        return "";
    }
    return trim((string)($user["nome"] ?? "") . " " . (string)($user["cognome"] ?? "")) ?: (string)($user["username"] ?? "");
}

function is_external_user(?array $user): bool {
    return $user && (string)($user["auth_provider"] ?? "local") !== "local";
}

function authenticate_user(string $username, string $password): ?array {
    $st = get_db()->prepare("SELECT * FROM utenti WHERE username = ? AND attivo = 1");
    $st->execute([$username]);
    $user = $st->fetch();
    if (!$user || !password_verify($password, (string)$user["password_hash"])) {
        return null;
    }
    return $user;
}

function auth_setting_defaults(): array {
    return [
        "ldap_enabled" => LDAP_ENABLED ? "1" : "0",
        "ldap_uri" => LDAP_URI,
        "ldap_host" => LDAP_HOST,
        "ldap_port" => LDAP_PORT,
        "ldap_encryption" => LDAP_ENCRYPTION,
        "ldap_protocol_version" => LDAP_PROTOCOL_VERSION,
        "ldap_base_dn" => LDAP_BASE_DN,
        "ldap_bind_dn" => LDAP_BIND_DN,
        "ldap_bind_password" => LDAP_BIND_PASSWORD,
        "ldap_user_filter" => LDAP_USER_FILTER,
        "ldap_attr_username" => LDAP_ATTR_USERNAME,
        "ldap_attr_email" => LDAP_ATTR_EMAIL,
        "ldap_attr_first_name" => LDAP_ATTR_FIRST_NAME,
        "ldap_attr_last_name" => LDAP_ATTR_LAST_NAME,
        "ldap_default_role" => LDAP_DEFAULT_ROLE,
        "oidc_enabled" => OIDC_ENABLED ? "1" : "0",
        "oidc_issuer" => OIDC_ISSUER,
        "oidc_client_id" => OIDC_CLIENT_ID,
        "oidc_client_secret" => OIDC_CLIENT_SECRET,
        "oidc_redirect_uri" => OIDC_REDIRECT_URI,
        "oidc_scope" => OIDC_SCOPE,
        "oidc_default_role" => OIDC_DEFAULT_ROLE,
    ];
}

function auth_secret_keys(): array {
    return ["ldap_bind_password", "oidc_client_secret"];
}

function auth_settings(): array {
    $settings = auth_setting_defaults();
    try {
        $rows = get_db()->query("SELECT setting_key, setting_value FROM auth_settings")->fetchAll();
        foreach ($rows as $row) {
            $settings[(string)$row["setting_key"]] = (string)($row["setting_value"] ?? "");
        }
    } catch (Throwable $e) {
        return $settings;
    }
    return $settings;
}

function auth_setting(string $key, string $default = ""): string {
    $settings = auth_settings();
    return (string)($settings[$key] ?? $default);
}

function auth_setting_bool(string $key): bool {
    return filter_var(auth_setting($key, "0"), FILTER_VALIDATE_BOOL);
}

function save_auth_settings(array $data): array {
    $keys = array_keys(auth_setting_defaults());
    $secretKeys = auth_secret_keys();
    $db = get_db();
    $st = $db->prepare(
        "INSERT INTO auth_settings (setting_key, setting_value, is_secret)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), is_secret = VALUES(is_secret)"
    );
    foreach ($keys as $key) {
        $current = auth_setting($key, "");
        $value = (string)($data[$key] ?? "");
        if (in_array($key, $secretKeys, true) && $value === "") {
            $value = $current;
        }
        if (str_ends_with($key, "_enabled")) {
            $value = isset($data[$key]) ? "1" : "0";
        }
        $st->execute([$key, $value, in_array($key, $secretKeys, true) ? 1 : 0]);
    }
    return ["ok" => true, "message" => "Parametri autenticazione salvati."];
}

function touch_user_login(int $userId): void {
    try {
        get_db()->prepare("UPDATE utenti SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$userId]);
    } catch (Throwable $e) {
        return;
    }
}

function find_external_user(string $provider, string $externalId): array|false {
    $st = get_db()->prepare("SELECT * FROM utenti WHERE auth_provider = ? AND external_id = ? AND attivo = 1");
    $st->execute([$provider, $externalId]);
    return $st->fetch();
}

function find_active_user_by_username(string $username): array|false {
    if ($username === "") {
        return false;
    }
    $st = get_db()->prepare("SELECT * FROM utenti WHERE username = ? AND attivo = 1");
    $st->execute([$username]);
    return $st->fetch();
}

function upsert_external_user(string $provider, string $externalId, array $profile, string $defaultRoleCode): array {
    $db = get_db();
    $username = trim((string)($profile["username"] ?? ""));
    $email = trim((string)($profile["email"] ?? ""));
    $nome = trim((string)($profile["nome"] ?? ""));
    $cognome = trim((string)($profile["cognome"] ?? ""));
    if ($username === "") {
        $username = $email !== "" ? $email : $provider . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', $externalId);
    }
    if ($nome === "") {
        $nome = $username;
    }
    if ($cognome === "") {
        $cognome = "-";
    }

    $user = find_external_user($provider, $externalId);
    if ($user) {
        $usernameOwner = find_active_user_by_username($username);
        if ($usernameOwner && (int)$usernameOwner["id"] !== (int)$user["id"]) {
            $username = (string)$user["username"];
        }
        $db->prepare("UPDATE utenti SET username=?, nome=?, cognome=?, email=?, last_login_at=CURRENT_TIMESTAMP WHERE id=?")
           ->execute([$username, $nome, $cognome, $email, (int)$user["id"]]);
        $updated = get_utente((int)$user["id"]);
        return $updated ?: $user;
    }

    $localUser = find_active_user_by_username($username);
    if ($localUser) {
        $db->prepare(
            "UPDATE utenti
             SET auth_provider = ?, external_id = ?, nome = ?, cognome = ?, email = ?, last_login_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        )->execute([$provider, $externalId, $nome, $cognome, $email, (int)$localUser["id"]]);
        $updated = get_utente((int)$localUser["id"]);
        return $updated ?: $localUser;
    }

    $db->beginTransaction();
    try {
        $db->prepare(
            "INSERT INTO utenti (auth_provider, external_id, username, password_hash, nome, cognome, email, is_admin, attivo, last_login_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, CURRENT_TIMESTAMP)"
        )->execute([
            $provider,
            $externalId,
            $username,
            password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            $nome,
            $cognome,
            $email,
        ]);
        $userId = (int)$db->lastInsertId();
        $roleId = get_default_role_id($defaultRoleCode) ?: get_default_role_id("utente");
        if ($roleId > 0) {
            sync_user_roles($userId, [$roleId]);
        }
        $db->commit();
        $created = get_utente($userId);
        return $created ?: ["id" => $userId, "username" => $username, "is_admin" => 0];
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function password_policy_error(string $password): string {
    if (strlen($password) < 12) {
        return "La password deve essere lunga almeno 12 caratteri.";
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        return "La password deve contenere almeno una maiuscola, una minuscola e un numero.";
    }
    return "";
}

function ldap_escape_filter_value(string $value): string {
    if (function_exists("ldap_escape")) {
        return ldap_escape($value, "", LDAP_ESCAPE_FILTER);
    }
    return str_replace(
        ["\\", "*", "(", ")", "\x00"],
        ["\\5c", "\\2a", "\\28", "\\29", "\\00"],
        $value
    );
}

function ldap_first_attr(array $entry, string $attr): string {
    $attrLower = strtolower($attr);
    foreach ($entry as $key => $value) {
        if (is_string($key) && strtolower($key) === $attrLower && is_array($value) && isset($value[0])) {
            return trim((string)$value[0]);
        }
    }
    return "";
}

function ldap_connection_uri(): string {
    $explicitUri = trim(auth_setting("ldap_uri"));
    if ($explicitUri !== "" && preg_match('/^ldaps?:\\/\\//i', $explicitUri)) {
        return $explicitUri;
    }
    $host = trim(auth_setting("ldap_host"));
    if ($host === "") {
        return "";
    }
    $encryption = auth_setting("ldap_encryption", "none");
    $scheme = $encryption === "ldaps" ? "ldaps" : "ldap";
    $defaultPort = $scheme === "ldaps" ? 636 : 389;
    $port = (int)(auth_setting("ldap_port") ?: $defaultPort);
    return $scheme . "://" . $host . ":" . $port;
}

function ldap_filter_uses_username(string $filter): bool {
    return stripos($filter, "{username}") !== false;
}

function authenticate_ldap_user(string $username, string $password): ?array {
    if (!auth_setting_bool("ldap_enabled") || $username === "" || $password === "") {
        return null;
    }
    if (!function_exists("ldap_connect")) {
        flash("error", "LDAP non disponibile: estensione PHP ldap non installata. Ricostruisci il container.");
        return null;
    }
    $ldapUri = ldap_connection_uri();
    $ldapProtocolVersion = (int)(auth_setting("ldap_protocol_version", "3") ?: 3);
    if (!in_array($ldapProtocolVersion, [2, 3], true)) {
        $ldapProtocolVersion = 3;
    }
    $ldapEncryption = auth_setting("ldap_encryption", "none");
    $baseDn = auth_setting("ldap_base_dn");
    $bindDn = auth_setting("ldap_bind_dn");
    $bindPassword = auth_setting("ldap_bind_password");
    $userFilter = auth_setting("ldap_user_filter", "(sAMAccountName={username})");
    $attrUsername = auth_setting("ldap_attr_username", "sAMAccountName");
    $attrEmail = auth_setting("ldap_attr_email", "mail");
    $attrFirstName = auth_setting("ldap_attr_first_name", "givenName");
    $attrLastName = auth_setting("ldap_attr_last_name", "sn");
    if ($ldapUri === "" || $baseDn === "") {
        return null;
    }
    if (!ldap_filter_uses_username($userFilter)) {
        flash("error", "Filtro LDAP non valido: deve contenere il placeholder {username}. Esempio: (uid={username}).");
        return null;
    }

    $conn = @ldap_connect($ldapUri);
    if (!$conn) {
        return null;
    }
    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, $ldapProtocolVersion);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
    if ($ldapEncryption === "starttls" && !@ldap_start_tls($conn)) {
        @ldap_unbind($conn);
        return null;
    }

    if ($bindDn !== "" && !@ldap_bind($conn, $bindDn, $bindPassword)) {
        @ldap_unbind($conn);
        return null;
    }

    $filter = str_replace("{username}", ldap_escape_filter_value($username), $userFilter);
    $attrs = array_values(array_unique([
        $attrUsername,
        $attrEmail,
        $attrFirstName,
        $attrLastName,
        "dn",
    ]));
    $search = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 2);
    if (!$search) {
        @ldap_unbind($conn);
        return null;
    }
    $entries = ldap_get_entries($conn, $search);
    if (($entries["count"] ?? 0) !== 1 || empty($entries[0]["dn"])) {
        @ldap_unbind($conn);
        return null;
    }

    $entry = $entries[0];
    $userDn = (string)$entry["dn"];
    if (!@ldap_bind($conn, $userDn, $password)) {
        @ldap_unbind($conn);
        return null;
    }
    @ldap_unbind($conn);

    $externalId = $userDn;
    $profile = [
        "username" => ldap_first_attr($entry, $attrUsername) ?: $username,
        "email" => ldap_first_attr($entry, $attrEmail),
        "nome" => ldap_first_attr($entry, $attrFirstName),
        "cognome" => ldap_first_attr($entry, $attrLastName),
    ];
    return upsert_external_user("ldap", $externalId, $profile, auth_setting("ldap_default_role", "utente"));
}

function test_ldap_connection(): array {
    if (!function_exists("ldap_connect")) {
        return ["ok" => false, "message" => "LDAP non disponibile: estensione PHP ldap non installata. Ricostruisci il container."];
    }
    $ldapUri = ldap_connection_uri();
    $baseDn = auth_setting("ldap_base_dn");
    if ($ldapUri === "") {
        return ["ok" => false, "message" => "Configura host/porta LDAP oppure una LDAP URI avanzata."];
    }
    $ldapProtocolVersion = (int)(auth_setting("ldap_protocol_version", "3") ?: 3);
    if (!in_array($ldapProtocolVersion, [2, 3], true)) {
        $ldapProtocolVersion = 3;
    }
    $conn = @ldap_connect($ldapUri);
    if (!$conn) {
        return ["ok" => false, "message" => "Connessione LDAP non inizializzata verso $ldapUri."];
    }
    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, $ldapProtocolVersion);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

    if (auth_setting("ldap_encryption", "none") === "starttls" && !@ldap_start_tls($conn)) {
        @ldap_unbind($conn);
        return ["ok" => false, "message" => "Connessione LDAP raggiunta, ma StartTLS non riuscito."];
    }

    $bindDn = auth_setting("ldap_bind_dn");
    $bindPassword = auth_setting("ldap_bind_password");
    $bindOk = $bindDn !== "" ? @ldap_bind($conn, $bindDn, $bindPassword) : @ldap_bind($conn);
    if (!$bindOk) {
        $error = function_exists("ldap_error") ? ldap_error($conn) : "bind fallito";
        @ldap_unbind($conn);
        return ["ok" => false, "message" => "Connessione LDAP raggiunta, ma bind non riuscito: " . $error];
    }

    if ($baseDn !== "") {
        $search = @ldap_read($conn, $baseDn, "(objectClass=*)", ["dn"], 0, 1);
        if (!$search) {
            $error = function_exists("ldap_error") ? ldap_error($conn) : "base DN non leggibile";
            @ldap_unbind($conn);
            return ["ok" => true, "message" => "Connessione e bind LDAP OK. Attenzione: Base DN non leggibile con ldap_read: " . $error];
        }
    }

    @ldap_unbind($conn);
    return ["ok" => true, "message" => "Connessione LDAP OK verso $ldapUri con protocollo v$ldapProtocolVersion."];
}

function test_ldap_user_search(string $username): array {
    $username = trim($username);
    if ($username === "") {
        return ["ok" => false, "message" => "Inserisci uno username da cercare."];
    }
    $connectionTest = test_ldap_connection();
    if (!$connectionTest["ok"]) {
        return $connectionTest;
    }
    $ldapUri = ldap_connection_uri();
    $ldapProtocolVersion = (int)(auth_setting("ldap_protocol_version", "3") ?: 3);
    if (!in_array($ldapProtocolVersion, [2, 3], true)) {
        $ldapProtocolVersion = 3;
    }
    $conn = @ldap_connect($ldapUri);
    if (!$conn) {
        return ["ok" => false, "message" => "Connessione LDAP non inizializzata verso $ldapUri."];
    }
    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, $ldapProtocolVersion);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
    if (auth_setting("ldap_encryption", "none") === "starttls" && !@ldap_start_tls($conn)) {
        @ldap_unbind($conn);
        return ["ok" => false, "message" => "StartTLS non riuscito durante la ricerca utente."];
    }
    $bindDn = auth_setting("ldap_bind_dn");
    $bindPassword = auth_setting("ldap_bind_password");
    $bindOk = $bindDn !== "" ? @ldap_bind($conn, $bindDn, $bindPassword) : @ldap_bind($conn);
    if (!$bindOk) {
        $error = function_exists("ldap_error") ? ldap_error($conn) : "bind fallito";
        @ldap_unbind($conn);
        return ["ok" => false, "message" => "Bind LDAP non riuscito prima della ricerca: " . $error];
    }

    $baseDn = auth_setting("ldap_base_dn");
    $filterTemplate = auth_setting("ldap_user_filter", "(sAMAccountName={username})");
    if (!ldap_filter_uses_username($filterTemplate)) {
        return [
            "ok" => false,
            "message" => "Filtro utente LDAP troppo generico: deve contenere {username}. Ora Ã¨: $filterTemplate. Esempio consigliato per la tua configurazione: (uid={username}).",
        ];
    }
    $filter = str_replace("{username}", ldap_escape_filter_value($username), $filterTemplate);
    $attrUsername = auth_setting("ldap_attr_username", "sAMAccountName");
    $attrEmail = auth_setting("ldap_attr_email", "mail");
    $attrFirstName = auth_setting("ldap_attr_first_name", "givenName");
    $attrLastName = auth_setting("ldap_attr_last_name", "sn");
    $attrs = array_values(array_unique([$attrUsername, $attrEmail, $attrFirstName, $attrLastName, "dn"]));
    $search = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 10);
    if (!$search) {
        $error = function_exists("ldap_error") ? ldap_error($conn) : "ricerca fallita";
        @ldap_unbind($conn);
        return ["ok" => false, "message" => "Ricerca LDAP fallita con filtro $filter: " . $error];
    }
    $entries = ldap_get_entries($conn, $search);
    $count = (int)($entries["count"] ?? 0);
    if ($count < 1) {
        @ldap_unbind($conn);
        return ["ok" => false, "message" => "Nessun utente trovato. Base DN: $baseDn Â· Filtro effettivo: $filter"];
    }
    if ($count > 1) {
        @ldap_unbind($conn);
        return [
            "ok" => false,
            "message" => "La ricerca LDAP non Ã¨ univoca: $count risultati. Restringi il filtro utente. Base DN: $baseDn Â· Filtro effettivo: $filter",
        ];
    }
    $entry = $entries[0];
    $details = [
        "DN: " . (string)($entry["dn"] ?? ""),
        "Filtro: " . $filter,
        "Risultati: " . $count,
        "Username [" . $attrUsername . "]: " . (ldap_first_attr($entry, $attrUsername) ?: "N/D"),
        "Email [" . $attrEmail . "]: " . (ldap_first_attr($entry, $attrEmail) ?: "N/D"),
        "Nome [" . $attrFirstName . "]: " . (ldap_first_attr($entry, $attrFirstName) ?: "N/D"),
        "Cognome [" . $attrLastName . "]: " . (ldap_first_attr($entry, $attrLastName) ?: "N/D"),
    ];
    @ldap_unbind($conn);
    return ["ok" => true, "message" => "Utente LDAP trovato. " . implode(" Â· ", $details)];
}

function oidc_redirect_uri(): string {
    $redirectUri = auth_setting("oidc_redirect_uri");
    if ($redirectUri !== "") {
        return $redirectUri;
    }
    $scheme = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https")) ? "https" : "http";
    $host = (string)($_SERVER["HTTP_HOST"] ?? "localhost");
    return $scheme . "://" . $host . APP_BASE_URL . "/auth/oidc_callback.php";
}

function oidc_http_json(string $url, array $postFields = [], array $headers = []): array {
    $ch = curl_init($url);
    $curlHeaders = array_merge(["Accept: application/json"], $headers);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $curlHeaders,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if ($postFields) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        $curlHeaders[] = "Content-Type: application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if (!is_string($body) || $body === "" || $code < 200 || $code >= 300) {
        return [];
    }
    $json = json_decode($body, true);
    return is_array($json) ? $json : [];
}

function oidc_discovery(): array {
    $issuer = rtrim(auth_setting("oidc_issuer"), "/");
    if (!auth_setting_bool("oidc_enabled") || $issuer === "" || auth_setting("oidc_client_id") === "") {
        return [];
    }
    $discoveryUrl = $issuer . "/.well-known/openid-configuration";
    $config = oidc_http_json($discoveryUrl);
    if (($config["issuer"] ?? "") !== $issuer) {
        return [];
    }
    return $config;
}

function test_oidc_connection(): array {
    $issuer = rtrim(auth_setting("oidc_issuer"), "/");
    if ($issuer === "") {
        return ["ok" => false, "message" => "Configura l'Issuer OIDC/Keycloak."];
    }
    $config = oidc_http_json($issuer . "/.well-known/openid-configuration");
    if (!$config) {
        return ["ok" => false, "message" => "Discovery OIDC non raggiungibile."];
    }
    if (($config["issuer"] ?? "") !== $issuer) {
        return ["ok" => false, "message" => "Discovery raggiunta, ma issuer non coincide."];
    }
    foreach (["authorization_endpoint", "token_endpoint", "userinfo_endpoint", "jwks_uri"] as $endpointKey) {
        if (empty($config[$endpointKey])) {
            return ["ok" => false, "message" => "Discovery OIDC incompleta: manca $endpointKey."];
        }
    }
    if (auth_setting("oidc_client_id") === "") {
        return ["ok" => false, "message" => "Discovery OIDC OK, ma manca il Client ID."];
    }
    return ["ok" => true, "message" => "Discovery OIDC/Keycloak OK per issuer $issuer."];
}

function oidc_start_login(): never {
    $config = oidc_discovery();
    if (!$config || empty($config["authorization_endpoint"])) {
        flash("error", "OIDC non configurato o discovery Keycloak non raggiungibile.");
        redirect(APP_BASE_URL . "/login.php");
    }
    $state = bin2hex(random_bytes(24));
    $nonce = bin2hex(random_bytes(24));
    $_SESSION["oidc_state"] = $state;
    $_SESSION["oidc_nonce"] = $nonce;
    $query = http_build_query([
        "response_type" => "code",
        "client_id" => auth_setting("oidc_client_id"),
        "redirect_uri" => oidc_redirect_uri(),
        "scope" => auth_setting("oidc_scope", "openid profile email"),
        "state" => $state,
        "nonce" => $nonce,
    ]);
    redirect((string)$config["authorization_endpoint"] . "?" . $query);
}

function oidc_handle_callback(string $code, string $state): array {
    if (!auth_setting_bool("oidc_enabled") || $code === "" || $state === "" || !hash_equals((string)($_SESSION["oidc_state"] ?? ""), $state)) {
        return ["ok" => false, "message" => "Risposta OIDC non valida."];
    }
    unset($_SESSION["oidc_state"]);
    $config = oidc_discovery();
    if (!$config || empty($config["token_endpoint"]) || empty($config["userinfo_endpoint"])) {
        return ["ok" => false, "message" => "OIDC discovery non disponibile."];
    }
    $token = oidc_http_json((string)$config["token_endpoint"], [
        "grant_type" => "authorization_code",
        "code" => $code,
        "redirect_uri" => oidc_redirect_uri(),
        "client_id" => auth_setting("oidc_client_id"),
        "client_secret" => auth_setting("oidc_client_secret"),
    ]);
    $accessToken = (string)($token["access_token"] ?? "");
    if ($accessToken === "") {
        return ["ok" => false, "message" => "Token OIDC non ricevuto."];
    }
    $userinfo = oidc_http_json((string)$config["userinfo_endpoint"], [], [
        "Authorization: Bearer " . $accessToken,
    ]);
    $subject = (string)($userinfo["sub"] ?? "");
    if ($subject === "") {
        return ["ok" => false, "message" => "Profilo OIDC senza subject."];
    }
    $profile = [
        "username" => (string)($userinfo["preferred_username"] ?? $userinfo["email"] ?? $subject),
        "email" => (string)($userinfo["email"] ?? ""),
        "nome" => (string)($userinfo["given_name"] ?? ""),
        "cognome" => (string)($userinfo["family_name"] ?? ""),
    ];
    $user = upsert_external_user("oidc", rtrim(auth_setting("oidc_issuer"), "/") . "|" . $subject, $profile, auth_setting("oidc_default_role", "utente"));
    secure_login_session();
    $_SESSION["user_id"] = (int)$user["id"];
    $_SESSION["is_admin"] = (int)($user["is_admin"] ?? 0) === 1;
    touch_user_login((int)$user["id"]);
    unset($_SESSION["oidc_nonce"]);
    return ["ok" => true, "message" => "Accesso OIDC effettuato."];
}

function is_logged_in(): bool {
    return is_admin() || current_user() !== null;
}

function app_functions_catalog(): array {
    return [
        "dashboard" => ["nome" => "Dashboard", "descrizione" => "Accesso alla dashboard iniziale", "ordine" => 10],
        "questionari" => ["nome" => "Questionari", "descrizione" => "Creazione, compilazione e gestione questionari", "ordine" => 20],
        "risultati" => ["nome" => "Risultati requisiti", "descrizione" => "Lettura e revisione dei requisiti prodotti", "ordine" => 30],
        "pir" => ["nome" => "PIR", "descrizione" => "Post Implementation Review", "ordine" => 40],
        "domande" => ["nome" => "Domande", "descrizione" => "Anagrafica domande questionario", "ordine" => 100],
        "requisiti" => ["nome" => "Requisiti catalogo", "descrizione" => "Catalogo requisiti di sicurezza", "ordine" => 110],
        "requisiti_specifici" => ["nome" => "Requisiti specifici", "descrizione" => "Requisiti specifici di progetto", "ordine" => 120],
        "servizi" => ["nome" => "Servizi", "descrizione" => "Catalogo servizi", "ordine" => 130],
        "regole_requisiti" => ["nome" => "Regole requisiti", "descrizione" => "Regole di assegnazione requisiti", "ordine" => 140],
        "regole_servizi" => ["nome" => "Regole servizi", "descrizione" => "Regole di assegnazione servizi", "ordine" => 150],
        "business_lines" => ["nome" => "Business line", "descrizione" => "Anagrafica business line", "ordine" => 160],
        "requisito_categorie" => ["nome" => "Categorie requisiti", "descrizione" => "Tassonomia categorie requisiti", "ordine" => 170],
        "utenti" => ["nome" => "Utenti", "descrizione" => "Anagrafica utenti", "ordine" => 900],
        "auth_settings" => ["nome" => "Autenticazione", "descrizione" => "Configurazione LDAP e OIDC", "ordine" => 905],
        "ruoli_permessi" => ["nome" => "Ruoli e permessi", "descrizione" => "RBAC e permessi CRUD", "ordine" => 910],
    ];
}

function crud_actions(): array {
    return [
        "create" => "Create",
        "read" => "Read",
        "update" => "Update",
        "delete" => "Delete",
    ];
}

function has_permission(string $functionCode, string $action = "read"): bool {
    if (($_SESSION["is_admin"] ?? false) === true) {
        return true;
    }
    $userId = (int)($_SESSION["user_id"] ?? 0);
    if ($userId <= 0) {
        return false;
    }
    if (!array_key_exists($action, crud_actions())) {
        return false;
    }
    try {
        $sql = "SELECT MAX(CASE
                    WHEN ? = 'create' THEN p.can_create
                    WHEN ? = 'read' THEN p.can_read
                    WHEN ? = 'update' THEN p.can_update
                    WHEN ? = 'delete' THEN p.can_delete
                    ELSE 0
                END) AS allowed
                FROM utente_ruoli ur
                JOIN rbac_ruoli r ON r.id = ur.ruolo_id AND r.attivo = 1
                JOIN rbac_permessi p ON p.ruolo_id = r.id
                JOIN rbac_funzioni f ON f.id = p.funzione_id AND f.attiva = 1
                WHERE ur.utente_id = ? AND f.codice = ?";
        $st = get_db()->prepare($sql);
        $st->execute([$action, $action, $action, $action, $userId, $functionCode]);
        return (int)$st->fetchColumn() === 1;
    } catch (Throwable $e) {
        return false;
    }
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect(APP_BASE_URL . "/login.php");
    }
}

function require_permission(string $functionCode, string $action = "read"): void {
    if (has_permission($functionCode, $action)) {
        return;
    }
    if (!is_logged_in()) {
        redirect(APP_BASE_URL . "/login.php");
    }
    http_response_code(403);
    exit("Permesso negato.");
}

function require_admin(): void {
    require_permission("ruoli_permessi", "read");
}

function first_allowed_url(): string {
    $routes = [
        ["dashboard", "read", "/index.php"],
        ["questionari", "read", "/questionario/lista.php"],
        ["questionari", "create", "/questionario/nuovo.php"],
        ["pir", "read", "/pir/lista.php"],
        ["domande", "read", "/admin/domande.php"],
        ["utenti", "read", "/admin/utenti.php"],
        ["ruoli_permessi", "read", "/admin/ruoli.php"],
    ];
    foreach ($routes as [$functionCode, $action, $url]) {
        if (has_permission($functionCode, $action)) {
            return APP_BASE_URL . $url;
        }
    }
    return APP_BASE_URL . "/index.php";
}

function get_rbac_funzioni(): array {
    try {
        return get_db()->query("SELECT * FROM rbac_funzioni WHERE attiva = 1 ORDER BY ordine, nome")->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function get_ruoli(bool $onlyActive = false): array {
    $sql = "SELECT r.*,
                   COUNT(DISTINCT ur.utente_id) AS utenti_count
            FROM rbac_ruoli r
            LEFT JOIN utente_ruoli ur ON ur.ruolo_id = r.id";
    if ($onlyActive) {
        $sql .= " WHERE r.attivo = 1";
    }
    $sql .= " GROUP BY r.id, r.codice, r.nome, r.descrizione, r.sistema, r.attivo, r.created_at, r.updated_at
              ORDER BY r.sistema DESC, r.nome";
    return get_db()->query($sql)->fetchAll();
}

function get_ruolo(int $id): array|false {
    $st = get_db()->prepare("SELECT * FROM rbac_ruoli WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch();
}

function get_ruolo_permessi(int $roleId): array {
    $st = get_db()->prepare(
        "SELECT f.codice, p.can_create, p.can_read, p.can_update, p.can_delete
         FROM rbac_permessi p
         JOIN rbac_funzioni f ON f.id = p.funzione_id
         WHERE p.ruolo_id = ?"
    );
    $st->execute([$roleId]);
    $map = [];
    foreach ($st->fetchAll() as $row) {
        $map[$row["codice"]] = [
            "create" => (int)$row["can_create"] === 1,
            "read" => (int)$row["can_read"] === 1,
            "update" => (int)$row["can_update"] === 1,
            "delete" => (int)$row["can_delete"] === 1,
        ];
    }
    return $map;
}

function role_ids_include_admin(array $roleIds): bool {
    $roleIds = array_values(array_filter(array_map("intval", $roleIds)));
    if (!$roleIds) {
        return false;
    }
    $placeholders = implode(",", array_fill(0, count($roleIds), "?"));
    $st = get_db()->prepare("SELECT COUNT(*) FROM rbac_ruoli WHERE codice = 'admin' AND id IN ($placeholders)");
    $st->execute($roleIds);
    return (int)$st->fetchColumn() > 0;
}

function get_default_role_id(string $code = "utente"): int {
    $st = get_db()->prepare("SELECT id FROM rbac_ruoli WHERE codice = ? AND attivo = 1");
    $st->execute([$code]);
    return (int)($st->fetchColumn() ?: 0);
}

function get_user_role_ids(int $userId): array {
    $st = get_db()->prepare("SELECT ruolo_id FROM utente_ruoli WHERE utente_id = ? ORDER BY ruolo_id");
    $st->execute([$userId]);
    return array_map("intval", array_column($st->fetchAll(), "ruolo_id"));
}

function get_user_roles_label(int $userId): string {
    $st = get_db()->prepare(
        "SELECT r.nome
         FROM utente_ruoli ur
         JOIN rbac_ruoli r ON r.id = ur.ruolo_id
         WHERE ur.utente_id = ?
         ORDER BY r.nome"
    );
    $st->execute([$userId]);
    return implode(", ", array_column($st->fetchAll(), "nome"));
}

function sync_user_roles(int $userId, array $roleIds): void {
    $roleIds = array_values(array_unique(array_filter(array_map("intval", $roleIds))));
    if (!$roleIds) {
        $defaultRoleId = get_default_role_id("utente");
        if ($defaultRoleId > 0) {
            $roleIds = [$defaultRoleId];
        }
    }
    $db = get_db();
    $db->prepare("DELETE FROM utente_ruoli WHERE utente_id = ?")->execute([$userId]);
    $st = $db->prepare("INSERT IGNORE INTO utente_ruoli (utente_id, ruolo_id) VALUES (?, ?)");
    foreach ($roleIds as $roleId) {
        $st->execute([$userId, $roleId]);
    }
    $db->prepare("UPDATE utenti SET is_admin = ? WHERE id = ?")->execute([role_ids_include_admin($roleIds) ? 1 : 0, $userId]);
}

function save_ruolo(array $data): array {
    $id = (int)($data["id"] ?? 0);
    $nome = trim((string)($data["nome"] ?? ""));
    $codice = strtolower(trim((string)($data["codice"] ?? "")));
    $descrizione = trim((string)($data["descrizione"] ?? ""));
    $attivo = isset($data["attivo"]) ? 1 : 0;
    if ($nome === "" || $codice === "") {
        return ["ok" => false, "message" => "Nome e codice ruolo sono obbligatori."];
    }
    if (!preg_match('/^[a-z0-9_]+$/', $codice)) {
        return ["ok" => false, "message" => "Il codice ruolo puÃ² contenere solo lettere minuscole, numeri e underscore."];
    }

    $db = get_db();
    $db->beginTransaction();
    try {
        $existing = $id > 0 ? get_ruolo($id) : null;
        if ($id > 0) {
            $db->prepare("UPDATE rbac_ruoli SET nome = ?, codice = ?, descrizione = ?, attivo = ? WHERE id = ?")
               ->execute([$nome, $codice, $descrizione, $attivo, $id]);
            $roleId = $id;
        } else {
            $db->prepare("INSERT INTO rbac_ruoli (codice, nome, descrizione, sistema, attivo) VALUES (?, ?, ?, 0, ?)")
               ->execute([$codice, $nome, $descrizione, $attivo]);
            $roleId = (int)$db->lastInsertId();
        }
        if ($existing && (int)$existing["sistema"] === 1 && $codice !== (string)$existing["codice"]) {
            throw new RuntimeException("Non puoi modificare il codice di un ruolo di sistema.");
        }
        save_ruolo_permessi($roleId, is_array($data["permessi"] ?? null) ? $data["permessi"] : []);
        $db->commit();
        return ["ok" => true, "message" => "Ruolo salvato."];
    } catch (Throwable $e) {
        $db->rollBack();
        if ($e instanceof PDOException && (int)($e->errorInfo[1] ?? 0) === 1062) {
            return ["ok" => false, "message" => "Codice ruolo giÃ  presente."];
        }
        return ["ok" => false, "message" => $e->getMessage()];
    }
}

function save_ruolo_permessi(int $roleId, array $permissions): void {
    $db = get_db();
    $functions = get_rbac_funzioni();
    $st = $db->prepare(
        "INSERT INTO rbac_permessi (ruolo_id, funzione_id, can_create, can_read, can_update, can_delete)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             can_create = VALUES(can_create),
             can_read = VALUES(can_read),
             can_update = VALUES(can_update),
             can_delete = VALUES(can_delete)"
    );
    foreach ($functions as $function) {
        $code = (string)$function["codice"];
        $row = is_array($permissions[$code] ?? null) ? $permissions[$code] : [];
        $st->execute([
            $roleId,
            (int)$function["id"],
            isset($row["create"]) ? 1 : 0,
            isset($row["read"]) ? 1 : 0,
            isset($row["update"]) ? 1 : 0,
            isset($row["delete"]) ? 1 : 0,
        ]);
    }
}

function delete_ruolo(int $id): array {
    $role = get_ruolo($id);
    if (!$role) {
        return ["ok" => false, "message" => "Ruolo non trovato."];
    }
    if ((int)$role["sistema"] === 1) {
        return ["ok" => false, "message" => "I ruoli di sistema non possono essere eliminati."];
    }
    get_db()->prepare("UPDATE rbac_ruoli SET attivo = 0 WHERE id = ?")->execute([$id]);
    return ["ok" => true, "message" => "Ruolo disattivato."];
}

function get_utenti(bool $only_active = false): array {
    $sql = "SELECT * FROM utenti";
    if ($only_active) {
        $sql .= " WHERE attivo = 1";
    }
    $sql .= " ORDER BY cognome, nome, username";
    return get_db()->query($sql)->fetchAll();
}

function get_utente(int $id): array|false {
    $st = get_db()->prepare("SELECT * FROM utenti WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch();
}

function save_utente(array $data): array {
    $id = (int)($data["id"] ?? 0);
    $username = trim((string)($data["username"] ?? ""));
    $password = (string)($data["password"] ?? "");
    $passwordConfirm = (string)($data["password_confirm"] ?? "");
    $nome = trim((string)($data["nome"] ?? ""));
    $cognome = trim((string)($data["cognome"] ?? ""));
    $email = trim((string)($data["email"] ?? ""));
    $emailConfirm = trim((string)($data["email_confirm"] ?? ""));
    if ($username === "" || $nome === "" || $cognome === "") {
        return ["ok" => false, "message" => "Username, nome e cognome sono obbligatori."];
    }
    if ($id <= 0 && $password === "") {
        return ["ok" => false, "message" => "La password Ã¨ obbligatoria per un nuovo utente."];
    }
    if ($password !== "" && !hash_equals($password, $passwordConfirm)) {
        return ["ok" => false, "message" => "La password e la conferma password non coincidono."];
    }
    if ($password !== "" && ($passwordError = password_policy_error($password)) !== "") {
        return ["ok" => false, "message" => $passwordError];
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ["ok" => false, "message" => "Email non valida."];
    }
    if ($email !== "" && !hash_equals(strtolower($email), strtolower($emailConfirm))) {
        return ["ok" => false, "message" => "L'email e la conferma email non coincidono."];
    }
    $fields = ["username","nome","cognome","email","telefono","reparto","ruolo","is_admin","attivo"];
    $values = [
        $username,
        $nome,
        $cognome,
        $email,
        trim((string)($data["telefono"] ?? "")),
        trim((string)($data["reparto"] ?? "")),
        trim((string)($data["ruolo"] ?? "")),
        isset($data["is_admin"]) ? 1 : 0,
        isset($data["attivo"]) ? 1 : 0,
    ];
    $db = get_db();
    try {
        if ($id > 0) {
            $sets = implode(",", array_map(fn($f) => "$f=?", $fields));
            if ($password !== "") {
                $sets .= ",password_hash=?";
                $values[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $values[] = $id;
            $db->prepare("UPDATE utenti SET $sets WHERE id=?")->execute($values);
            return ["ok" => true, "message" => "Utente aggiornato."];
        }
        $db->prepare(
            "INSERT INTO utenti (username,password_hash,nome,cognome,email,telefono,reparto,ruolo,is_admin,attivo)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $nome,
            $cognome,
            $email,
            trim((string)($data["telefono"] ?? "")),
            trim((string)($data["reparto"] ?? "")),
            trim((string)($data["ruolo"] ?? "")),
            isset($data["is_admin"]) ? 1 : 0,
            isset($data["attivo"]) ? 1 : 0,
        ]);
        return ["ok" => true, "message" => "Utente creato."];
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ["ok" => false, "message" => "Username giÃ  presente."];
        }
        throw $e;
    }
}

function delete_utente(int $id): void {
    get_db()->prepare("UPDATE utenti SET attivo = 0 WHERE id = ?")->execute([$id]);
}

function save_utente_rbac(array $data): array {
    $id = (int)($data["id"] ?? 0);
    $username = trim((string)($data["username"] ?? ""));
    $password = (string)($data["password"] ?? "");
    $passwordConfirm = (string)($data["password_confirm"] ?? "");
    $nome = trim((string)($data["nome"] ?? ""));
    $cognome = trim((string)($data["cognome"] ?? ""));
    $email = trim((string)($data["email"] ?? ""));
    $emailConfirm = trim((string)($data["email_confirm"] ?? ""));
    $roleIds = is_array($data["role_ids"] ?? null) ? array_map("intval", $data["role_ids"]) : [];
    if (!$roleIds && isset($data["is_admin"])) {
        $adminRoleId = get_default_role_id("admin");
        if ($adminRoleId > 0) {
            $roleIds[] = $adminRoleId;
        }
    }
    if (!$roleIds) {
        $defaultRoleId = get_default_role_id("utente");
        if ($defaultRoleId > 0) {
            $roleIds[] = $defaultRoleId;
        }
    }
    if ($username === "" || $nome === "" || $cognome === "") {
        return ["ok" => false, "message" => "Username, nome e cognome sono obbligatori."];
    }
    if ($id <= 0 && $password === "") {
        return ["ok" => false, "message" => "La password Ã¨ obbligatoria per un nuovo utente."];
    }
    if ($password !== "" && !hash_equals($password, $passwordConfirm)) {
        return ["ok" => false, "message" => "La password e la conferma password non coincidono."];
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ["ok" => false, "message" => "Email non valida."];
    }
    if ($email !== "" && !hash_equals(strtolower($email), strtolower($emailConfirm))) {
        return ["ok" => false, "message" => "L'email e la conferma email non coincidono."];
    }
    $fields = ["username","nome","cognome","email","telefono","reparto","ruolo","is_admin","attivo"];
    $values = [
        $username,
        $nome,
        $cognome,
        $email,
        trim((string)($data["telefono"] ?? "")),
        trim((string)($data["reparto"] ?? "")),
        trim((string)($data["ruolo"] ?? "")),
        role_ids_include_admin($roleIds) ? 1 : 0,
        isset($data["attivo"]) ? 1 : 0,
    ];
    $db = get_db();
    try {
        $db->beginTransaction();
        if ($id > 0) {
            $sets = implode(",", array_map(fn($field) => "$field=?", $fields));
            if ($password !== "") {
                $sets .= ",password_hash=?";
                $values[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $values[] = $id;
            $db->prepare("UPDATE utenti SET $sets WHERE id=?")->execute($values);
            sync_user_roles($id, $roleIds);
            $db->commit();
            return ["ok" => true, "message" => "Utente aggiornato."];
        }
        $db->prepare(
            "INSERT INTO utenti (username,password_hash,nome,cognome,email,telefono,reparto,ruolo,is_admin,attivo)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $nome,
            $cognome,
            $email,
            trim((string)($data["telefono"] ?? "")),
            trim((string)($data["reparto"] ?? "")),
            trim((string)($data["ruolo"] ?? "")),
            role_ids_include_admin($roleIds) ? 1 : 0,
            isset($data["attivo"]) ? 1 : 0,
        ]);
        sync_user_roles((int)$db->lastInsertId(), $roleIds);
        $db->commit();
        return ["ok" => true, "message" => "Utente creato."];
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ["ok" => false, "message" => "Username giÃ  presente."];
        }
        throw $e;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ["ok" => false, "message" => $e->getMessage()];
    }
}

// â”€â”€ Stats â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function save_current_user_profile(int $userId, array $data): array {
    $user = get_utente($userId);
    if (!$user || (int)$user["attivo"] !== 1) {
        return ["ok" => false, "message" => "Utente non trovato o non attivo."];
    }
    $username = trim((string)($data["username"] ?? ""));
    $nome = trim((string)($data["nome"] ?? ""));
    $cognome = trim((string)($data["cognome"] ?? ""));
    $email = trim((string)($data["email"] ?? ""));
    $emailConfirm = trim((string)($data["email_confirm"] ?? ""));
    $telefono = trim((string)($data["telefono"] ?? ""));
    $reparto = trim((string)($data["reparto"] ?? ""));
    $ruolo = trim((string)($data["ruolo"] ?? ""));
    $currentPassword = (string)($data["current_password"] ?? "");
    $password = (string)($data["password"] ?? "");
    $passwordConfirm = (string)($data["password_confirm"] ?? "");
    $externalUser = is_external_user($user);

    if ($username === "" || $nome === "" || $cognome === "") {
        return ["ok" => false, "message" => "Username, nome e cognome sono obbligatori."];
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ["ok" => false, "message" => "Email non valida."];
    }
    if ($email !== "" && !hash_equals(strtolower($email), strtolower($emailConfirm))) {
        return ["ok" => false, "message" => "L'email e la conferma email non coincidono."];
    }
    if ($externalUser && ($currentPassword !== "" || $password !== "" || $passwordConfirm !== "")) {
        return ["ok" => false, "message" => "La password Ã¨ gestita dal provider esterno e non puÃ² essere modificata da questa applicazione."];
    }
    if ($password !== "") {
        if ($currentPassword === "" || !password_verify($currentPassword, (string)$user["password_hash"])) {
            return ["ok" => false, "message" => "Per cambiare password devi indicare la password attuale corretta."];
        }
        if (!hash_equals($password, $passwordConfirm)) {
            return ["ok" => false, "message" => "La password e la conferma password non coincidono."];
        }
        if (($passwordError = password_policy_error($password)) !== "") {
            return ["ok" => false, "message" => $passwordError];
        }
    }

    $db = get_db();
    try {
        $fields = "username=?, nome=?, cognome=?, email=?, telefono=?, reparto=?, ruolo=?";
        $values = [$username, $nome, $cognome, $email, $telefono, $reparto, $ruolo];
        if ($password !== "") {
            $fields .= ", password_hash=?";
            $values[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $values[] = $userId;
        $db->prepare("UPDATE utenti SET $fields WHERE id=?")->execute($values);
        return ["ok" => true, "message" => "Profilo aggiornato."];
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ["ok" => false, "message" => "Username giÃ  presente."];
        }
        throw $e;
    }
}

function stats(): array {
    $db = get_db();
    return [
        "questionari" => (int)$db->query("SELECT COUNT(*) FROM questionari")->fetchColumn(),
        "completati"  => (int)$db->query("SELECT COUNT(*) FROM questionari WHERE stato='completato'")->fetchColumn(),
        "requisiti"   => (int)$db->query("SELECT COUNT(*) FROM requisiti WHERE attivo=1")->fetchColumn(),
        "servizi"     => (int)$db->query("SELECT COUNT(*) FROM servizi WHERE attivo=1")->fetchColumn(),
        "domande"     => (int)$db->query("SELECT COUNT(*) FROM domande WHERE attiva=1")->fetchColumn(),
        "threat analysis" => (int)$db->query("SELECT COUNT(*) FROM threat_analyses")->fetchColumn(),
    ];
}

// â”€â”€ Domande â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function get_domande(bool $only_active = false): array {
    $sql = "SELECT * FROM domande";
    if ($only_active) $sql .= " WHERE attiva = 1";
    $sql .= " ORDER BY ordine, id";
    return get_db()->query($sql)->fetchAll();
}

function get_domande_grouped(bool $only_active = false): array {
    $grouped = [];
    foreach (get_domande($only_active) as $d) {
        $grouped[$d["categoria"]][] = $d;
    }
    return $grouped;
}

function get_domanda(int $id): array|false {
    $st = get_db()->prepare("SELECT * FROM domande WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch();
}

function get_opzioni(int $domanda_id): array {
    $st = get_db()->prepare("SELECT * FROM opzioni_risposta WHERE domanda_id = ? ORDER BY ordine, id");
    $st->execute([$domanda_id]);
    return $st->fetchAll();
}

// â”€â”€ Questionari â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function get_questionari(): array {
    return get_db()->query(
        "SELECT q.*,
                CONCAT_WS(' ', uq.nome, uq.cognome) AS analista_questionario_nome,
                CONCAT_WS(' ', up.nome, up.cognome) AS pir_analista_nome
         FROM questionari q
         LEFT JOIN utenti uq ON uq.id = q.analista_questionario_id
         LEFT JOIN utenti up ON up.id = q.pir_analista_id
         ORDER BY q.created_at DESC"
    )->fetchAll();
}

function get_questionario(int $id): array|false {
    if ($id <= 0) return false;
    $st = get_db()->prepare(
        "SELECT q.*,
                CONCAT_WS(' ', uq.nome, uq.cognome) AS analista_questionario_nome,
                CONCAT_WS(' ', up.nome, up.cognome) AS pir_analista_nome
         FROM questionari q
         LEFT JOIN utenti uq ON uq.id = q.analista_questionario_id
         LEFT JOIN utenti up ON up.id = q.pir_analista_id
         WHERE q.id = ?"
    );
    $st->execute([$id]);
    return $st->fetch();
}

function get_business_lines(bool $only_active = true): array {
    $sql = "SELECT * FROM business_lines";
    if ($only_active) {
        $sql .= " WHERE attiva = 1";
    }
    $sql .= " ORDER BY ordine, nome";
    return get_db()->query($sql)->fetchAll();
}

function create_questionario(array $data): int {
    $db = get_db();
    $st = $db->prepare(
        "INSERT INTO questionari
         (nome_progetto,codice_progetto,nome_servizio,business_line,pm,pm_product_manager,po,tpo,tipologia_progetto,task_jira,analista_questionario_id,descrizione,note,stato)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, 'bozza')"
    );
    $st->execute([
        trim($data["nome_progetto"]      ?? ""),
        trim($data["codice_progetto"]         ?? ""),
        trim($data["nome_servizio"]      ?? ""),
        trim($data["business_line"]      ?? ""),
        trim($data["pm"]                 ?? ""),
        trim($data["pm_product_manager"] ?? ""),
        trim($data["po"]                 ?? ""),
        trim($data["tpo"]                ?? ""),
        trim($data["tipologia_progetto"] ?? ""),
        trim($data["task_jira"]          ?? ""),
        ((int)($data["analista_questionario_id"] ?? 0)) ?: null,
        trim($data["descrizione"]        ?? ""),
        trim($data["note"]               ?? ""),
    ]);
    return (int)$db->lastInsertId();
}

function update_questionario_anagrafica(int $id, array $data): void {
    $st = get_db()->prepare(
        "UPDATE questionari
         SET nome_progetto = ?, codice_progetto = ?, nome_servizio = ?, business_line = ?,
             pm = ?, pm_product_manager = ?, po = ?, tpo = ?, tipologia_progetto = ?, task_jira = ?, analista_questionario_id = ?, descrizione = ?, note = ?
         WHERE id = ?"
    );
    $st->execute([
        trim($data["nome_progetto"]      ?? ""),
        trim($data["codice_progetto"]         ?? ""),
        trim($data["nome_servizio"]      ?? ""),
        trim($data["business_line"]      ?? ""),
        trim($data["pm"]                 ?? ""),
        trim($data["pm_product_manager"] ?? ""),
        trim($data["po"]                 ?? ""),
        trim($data["tpo"]                ?? ""),
        trim($data["tipologia_progetto"] ?? ""),
        trim($data["task_jira"]          ?? ""),
        ((int)($data["analista_questionario_id"] ?? 0)) ?: null,
        trim($data["descrizione"]        ?? ""),
        trim($data["note"]               ?? ""),
        $id,
    ]);
}

function count_requisiti_specifici_questionario(int $questionario_id): int {
    $st = get_db()->prepare(
        "SELECT COUNT(*)
         FROM questionario_requisiti_specifici_link l
         JOIN questionario_requisiti_specifici s ON s.id = l.requisito_specifico_id
         WHERE l.questionario_id = ? AND s.attivo = 1"
    );
    $st->execute([$questionario_id]);
    return (int)$st->fetchColumn();
}

function delete_questionario(int $questionario_id, bool $delete_specifici = false): void {
    $db = get_db();
    $st = $db->prepare("SELECT requisito_specifico_id FROM questionario_requisiti_specifici_link WHERE questionario_id = ?");
    $st->execute([$questionario_id]);
    $specificIds = array_map('intval', array_column($st->fetchAll(), 'requisito_specifico_id'));

    $db->beginTransaction();
    try {
        if ($delete_specifici) {
            $db->prepare("DELETE FROM questionario_requisiti_specifici_link WHERE questionario_id = ?")->execute([$questionario_id]);
            foreach ($specificIds as $specificId) {
                $linked = $db->prepare("SELECT COUNT(*) FROM questionario_requisiti_specifici_link WHERE requisito_specifico_id = ?");
                $linked->execute([$specificId]);
                if ((int)$linked->fetchColumn() === 0) {
                    $db->prepare("UPDATE questionario_requisiti_specifici SET attivo = 0 WHERE id = ?")->execute([$specificId]);
                }
            }
        }

        $db->prepare("UPDATE questionario_requisiti_specifici SET questionario_id = NULL WHERE questionario_id = ?")->execute([$questionario_id]);
        $db->prepare("DELETE FROM questionari WHERE id = ?")->execute([$questionario_id]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

// â”€â”€ Risposte â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function get_risposte(int $questionario_id): array {
    $st = get_db()->prepare(
        "SELECT qr.*, d.codice, d.testo, d.tipo
         FROM questionario_risposte qr
         JOIN domande d ON d.id = qr.domanda_id
         WHERE qr.questionario_id = ?"
    );
    $st->execute([$questionario_id]);
    $map = [];
    foreach ($st->fetchAll() as $r) $map[$r["domanda_id"]] = $r;
    return $map;
}

function save_risposte(int $questionario_id, array $answers, array $notes = []): void {
    $db = get_db();
    foreach ($answers as $domanda_id => $valore) {
        $note = $notes[$domanda_id] ?? "";
        $val  = is_array($valore) ? implode(",", $valore) : (string)$valore;
        $st = $db->prepare(
            "INSERT INTO questionario_risposte (questionario_id,domanda_id,valore,note)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE valore=VALUES(valore), note=VALUES(note)"
        );
        $st->execute([$questionario_id, (int)$domanda_id, $val, (string)$note]);
    }
}

// â”€â”€ Calcolo risultati â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function questionario_import_normalize(string $value): string {
    $value = trim($value);
    $value = strtr($value, [
        'Ã ' => 'a', 'Ã¡' => 'a', 'Ã¢' => 'a', 'Ã¤' => 'a',
        'Ã¨' => 'e', 'Ã©' => 'e', 'Ãª' => 'e', 'Ã«' => 'e',
        'Ã¬' => 'i', 'Ã­' => 'i', 'Ã®' => 'i', 'Ã¯' => 'i',
        'Ã²' => 'o', 'Ã³' => 'o', 'Ã´' => 'o', 'Ã¶' => 'o',
        'Ã¹' => 'u', 'Ãº' => 'u', 'Ã»' => 'u', 'Ã¼' => 'u',
        'Ã€' => 'a', 'Ã' => 'a', 'Ãˆ' => 'e', 'Ã‰' => 'e',
        'ÃŒ' => 'i', 'Ã’' => 'o', 'Ã“' => 'o', 'Ã™' => 'u',
    ]);
    $value = strtolower($value);
    return trim(preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value);
}

function questionario_import_bool_value(mixed $value): string {
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_numeric($value)) {
        return ((float)$value > 0) ? '1' : '0';
    }
    $normalized = questionario_import_normalize((string)$value);
    if ($normalized === ''
        || in_array($normalized, ['no', 'n a', 'na', 'non applicabile', 'non previsto', 'n d', 'nd'], true)
        || str_contains($normalized, 'profilo non previsto')
    ) {
        return '0';
    }
    if (in_array($normalized, ['si', 'yes', 'true', 'vero', 'x'], true)) {
        return '1';
    }
    return '1';
}

function questionario_import_clean_value(mixed $value): string {
    if ($value === null) {
        return '';
    }
    if (is_bool($value)) {
        return $value ? 'si' : 'no';
    }
    if (is_float($value) && floor($value) === $value) {
        return (string)(int)$value;
    }
    return trim((string)$value);
}

function questionario_import_xml_text(SimpleXMLElement $node): string {
    $dom = dom_import_simplexml($node);
    if (!$dom) {
        return '';
    }
    $parts = [];
    foreach ($dom->getElementsByTagName('t') as $textNode) {
        $parts[] = $textNode->textContent;
    }
    return implode('', $parts);
}

function questionario_import_sheet_cells(SimpleXMLElement $sheetXml, array $sharedStrings): array {
    $sheetXml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $cells = [];
    foreach ($sheetXml->xpath('//m:sheetData/m:row/m:c') ?: [] as $cell) {
        $ref = (string)($cell['r'] ?? '');
        if ($ref === '') {
            continue;
        }
        $type = (string)($cell['t'] ?? '');
        $value = '';
        if ($type === 's') {
            $idx = (int)((string)($cell->v ?? '0'));
            $value = $sharedStrings[$idx] ?? '';
        } elseif ($type === 'inlineStr') {
            $value = questionario_import_xml_text($cell);
        } elseif (isset($cell->v)) {
            $value = (string)$cell->v;
        }
        $cells[$ref] = questionario_import_clean_value($value);
    }
    return $cells;
}

function questionario_import_read_xlsx(string $path): array {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Import XLSX non disponibile: estensione PHP zip non installata. Ricostruisci il container.');
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('File XLSX non leggibile o non valido.');
    }
    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);
        if ($xml) {
            $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($xml->xpath('//m:si') ?: [] as $si) {
                $sharedStrings[] = questionario_import_xml_text($si);
            }
        }
    }
    $workbookXml = simplexml_load_string((string)$zip->getFromName('xl/workbook.xml'));
    $relsXml = simplexml_load_string((string)$zip->getFromName('xl/_rels/workbook.xml.rels'));
    if (!$workbookXml || !$relsXml) {
        $zip->close();
        throw new RuntimeException('Struttura XLSX incompleta.');
    }
    $workbookXml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $workbookXml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $relsXml->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');
    $rels = [];
    foreach ($relsXml->xpath('//rel:Relationship') ?: [] as $rel) {
        $rels[(string)$rel['Id']] = (string)$rel['Target'];
    }
    $sheets = [];
    foreach ($workbookXml->xpath('//m:sheets/m:sheet') ?: [] as $sheet) {
        $name = (string)$sheet['name'];
        $rid = (string)$sheet->attributes('r', true)['id'];
        $target = $rels[$rid] ?? '';
        if ($target === '') {
            continue;
        }
        $target = ltrim($target, '/');
        $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
        $sheetContent = $zip->getFromName($sheetPath);
        if ($sheetContent === false) {
            continue;
        }
        $sheetXml = simplexml_load_string($sheetContent);
        if ($sheetXml) {
            $sheets[$name] = questionario_import_sheet_cells($sheetXml, $sharedStrings);
        }
    }
    $zip->close();
    return $sheets;
}

function questionario_import_cell(array $sheets, string $sheet, string $cell): string {
    return questionario_import_clean_value($sheets[$sheet][$cell] ?? '');
}

function questionario_import_initial_value(array $sheets, string $sheet, string $cell): string {
    $value = questionario_import_cell($sheets, $sheet, $cell);
    return in_array(questionario_import_normalize($value), ['n a', 'na'], true) ? '' : $value;
}

function questionario_import_definition(): array {
    return [
        ['Informazioni preliminari', 'B14', 'C14', 'nuovi_infrastrutturali'],
        ['Ulteriori informazioni', 'B7', 'C7', 'fornitori_critici'],
        ['Ulteriori informazioni', 'B8', 'C8', 'fornitori_critici'],
        ['Trattamento dei dati', 'B6', 'C6', 'dati_pers_clienti'],
        ['Trattamento dei dati', 'B7', 'C7', 'dati_part_clienti'],
        ['Trattamento dei dati', 'B8', 'C8', 'dati_pers_dipendenti'],
        ['Trattamento dei dati', 'B9', 'C9', 'dati_part_dipendenti'],
        ['Trattamento dei dati', 'B10', 'C10', 'dati_pers_fornitori'],
        ['Trattamento dei dati', 'B11', 'C11', 'dati_aziendali'],
        ['Trattamento dei dati', 'B12', 'C12', 'dati_tecnici'],
        ['Dettaglio delle funzionalitÃ ', 'B5', 'C5', 'funz_applicative'],
        ['Dettaglio delle funzionalitÃ ', 'B6', 'C6', 'funz_paas'],
        ['Dettaglio delle funzionalitÃ ', 'B7', 'C7', 'funz_iaas'],
        ['Dettaglio delle funzionalitÃ ', 'B8', 'C8', 'funz_solo_admin'],
        ['Dettaglio delle funzionalitÃ ', 'B12', 'C12', 'usr_pubblico'],
        ['Dettaglio delle funzionalitÃ ', 'B13', 'C13', 'usr_clienti_priv'],
        ['Dettaglio delle funzionalitÃ ', 'B14', 'C14', 'usr_personale_soc'],
        ['Dettaglio delle funzionalitÃ ', 'B15', 'C15', 'usr_personale_pa'],
        ['Dettaglio delle funzionalitÃ ', 'B16', 'C16', 'usr_personale_for'],
        ['Dettaglio delle funzionalitÃ ', 'B17', 'C17', 'usr_interni'],
        ['Dettaglio delle funzionalitÃ ', 'B21', 'C21', 'mercato_enterprise'],
        ['Dettaglio delle funzionalitÃ ', 'B22', 'C22', 'mercato_business'],
        ['Dettaglio delle funzionalitÃ ', 'B23', 'C23', 'mercato_online'],
        ['Dettaglio delle funzionalitÃ ', 'B27', 'C27', 'serv_ecs'],
        ['Dettaglio delle funzionalitÃ ', 'B28', 'C28', 'serv_saas'],
        ['Dettaglio delle funzionalitÃ ', 'B29', 'C29', 'serv_paas'],
        ['Dettaglio delle funzionalitÃ ', 'B30', 'C30', 'serv_iaas'],
        ['Dettaglio delle funzionalitÃ ', 'B31', 'C31', 'serv_prodotto'],
        ['Dettaglio delle funzionalitÃ ', 'B32', 'C32', 'serv_it4it'],
        ['Dettaglio delle funzionalitÃ ', 'B37', 'C37', 'int_web_usr_internet'],
        ['Dettaglio delle funzionalitÃ ', 'B38', 'C38', 'int_web_usr_privata'],
        ['Dettaglio delle funzionalitÃ ', 'B39', 'C39', 'int_web_usr_interna'],
        ['Dettaglio delle funzionalitÃ ', 'B41', 'C41', 'int_web_adm_internet'],
        ['Dettaglio delle funzionalitÃ ', 'B42', 'C42', 'int_web_adm_privata'],
        ['Dettaglio delle funzionalitÃ ', 'B43', 'C43', 'int_web_adm_interna'],
        ['Dettaglio delle funzionalitÃ ', 'B45', 'C45', 'int_cli_usr_internet'],
        ['Dettaglio delle funzionalitÃ ', 'B46', 'C46', 'int_cli_usr_privata'],
        ['Dettaglio delle funzionalitÃ ', 'B47', 'C47', 'int_cli_usr_interna'],
        ['Dettaglio delle funzionalitÃ ', 'B49', 'C49', 'int_cli_adm_internet'],
        ['Dettaglio delle funzionalitÃ ', 'B50', 'C50', 'int_cli_adm_privata'],
        ['Dettaglio delle funzionalitÃ ', 'B51', 'C51', 'int_cli_adm_interna'],
        ['Dettaglio delle funzionalitÃ ', 'B53', 'C53', 'int_api_internet'],
        ['Dettaglio delle funzionalitÃ ', 'B54', 'C54', 'int_api_privata'],
        ['Dettaglio delle funzionalitÃ ', 'B55', 'C55', 'int_api_interna'],
        ['Dettaglio delle funzionalitÃ ', 'B57', 'C57', 'int_app_mobile_int'],
        ['Dettaglio delle funzionalitÃ ', 'B58', 'C58', 'int_app_mobile_pub'],
        ['Dettaglio delle funzionalitÃ ', 'B60', 'C60', 'int_app_desktop_int'],
        ['Dettaglio delle funzionalitÃ ', 'B61', 'C61', 'int_app_desktop_pub'],
        ['Dettaglio delle funzionalitÃ ', 'B65', 'C65', 'flusso_acquisizione'],
        ['Dettaglio delle funzionalitÃ ', 'B66', 'C66', 'flusso_esportazione'],
        ['Dettaglio delle funzionalitÃ ', 'B69', 'C69', 'acc_adm_azienda'],
        ['Dettaglio delle funzionalitÃ ', 'B70', 'C70', 'acc_adm_cliente'],
        ['Dettaglio delle funzionalitÃ ', 'B71', 'C71', 'acc_privilegiati'],
        ['Dettaglio delle funzionalitÃ ', 'B72', 'C72', 'acc_auditor'],
        ['Dettaglio delle funzionalitÃ ', 'B73', 'C73', 'acc_utenti_base'],
        ['Dettaglio delle funzionalitÃ ', 'B74', 'C74', 'acc_sdo'],
        ['Dettaglio delle funzionalitÃ ', 'B75', 'C75', 'acc_sdo_imp'],
        ['Dettaglio delle funzionalitÃ ', 'B76', 'C76', 'acc_tecnici'],
        ['Dettaglio delle funzionalitÃ ', 'B80', 'C80', 'auth_integrazione'],
        ['Dettaglio delle funzionalitÃ ', 'B81', 'C81', 'auth_nuovo'],
        ['Dettaglio delle funzionalitÃ ', 'B82', 'C82', 'auth_locale'],
    ];
}

function questionario_import_analyze_xlsx(string $path, string $filename = ''): array {
    $sheets = questionario_import_read_xlsx($path);
    $byCode = [];
    foreach (get_domande(false) as $domanda) {
        $byCode[(string)$domanda['codice']] = $domanda;
    }
    $tipologia = questionario_import_cell($sheets, 'Informazioni preliminari', 'B13');
    $tipologiaNorm = questionario_import_normalize($tipologia);
    $answers = [];
    $notes = [];
    $rows = [];
    $warnings = [];

    foreach (['nuova_realizzazione', 'modifica'] as $code) {
        if (!isset($byCode[$code])) {
            $warnings[] = "Domanda DB non trovata: $code";
            continue;
        }
        $value = '0';
        if ($code === 'nuova_realizzazione' && str_contains($tipologiaNorm, 'nuova')) {
            $value = '1';
        }
        if ($code === 'modifica' && (str_contains($tipologiaNorm, 'modifica') || str_contains($tipologiaNorm, 'aggiornamento'))) {
            $value = '1';
        }
        $id = (int)$byCode[$code]['id'];
        $answers[$id] = $value;
        $rows[] = ['codice' => $code, 'domanda' => $byCode[$code]['testo'], 'sorgente' => 'Informazioni preliminari!B13', 'raw' => $tipologia, 'valore' => $value, 'note' => ''];
    }

    foreach (questionario_import_definition() as [$sheet, $answerCell, $noteCell, $code]) {
        if (!isset($byCode[$code])) {
            $warnings[] = "Domanda DB non trovata: $code";
            continue;
        }
        $raw = questionario_import_cell($sheets, $sheet, $answerCell);
        $note = questionario_import_cell($sheets, $sheet, $noteCell);
        $id = (int)$byCode[$code]['id'];
        $value = questionario_import_bool_value($raw);
        if ($code === 'fornitori_critici' && isset($answers[$id]) && $answers[$id] === '1') {
            $value = '1';
        }
        $answers[$id] = $value;
        if ($note !== '') {
            $notes[$id] = trim(($notes[$id] ?? '') . (($notes[$id] ?? '') !== '' ? "\n" : '') . $note);
        } elseif ($raw !== '' && !in_array(questionario_import_normalize($raw), ['si', 'no', 'n a', 'na', '0', '1'], true)) {
            $notes[$id] = trim(($notes[$id] ?? '') . (($notes[$id] ?? '') !== '' ? "\n" : '') . $raw);
        }
        $rows[] = ['codice' => $code, 'domanda' => $byCode[$code]['testo'], 'sorgente' => $sheet . '!' . $answerCell, 'raw' => $raw, 'valore' => $value, 'note' => $notes[$id] ?? ''];
    }

    $extraNotes = [];
    foreach ([
        'Link documentazione' => questionario_import_cell($sheets, 'Informazioni preliminari', 'B12'),
        'Tecnologie introdotte' => questionario_import_cell($sheets, 'Informazioni preliminari', 'B15'),
        'Requisiti specifici/SLA/vincoli' => questionario_import_cell($sheets, 'Informazioni preliminari', 'B16'),
        'Deadline progetto' => questionario_import_cell($sheets, 'Ulteriori informazioni', 'B3'),
        'Macrostima realizzazione' => questionario_import_cell($sheets, 'Ulteriori informazioni', 'B4'),
        'RPO' => questionario_import_cell($sheets, 'Ulteriori informazioni', 'B5'),
        'RTO' => questionario_import_cell($sheets, 'Ulteriori informazioni', 'B6'),
        'Servizi/applicazioni dipendenti' => questionario_import_cell($sheets, 'Ulteriori informazioni', 'B9'),
        'Software/servizi/applicazioni utilizzati' => questionario_import_cell($sheets, 'Informazioni architetturali', 'B21'),
    ] as $label => $value) {
        if ($value !== '' && !in_array(questionario_import_normalize($value), ['n a', 'na'], true)) {
            $extraNotes[] = $label . ': ' . $value;
        }
    }

    $taskJira = '';
    if (preg_match('/(?:^|[^A-Z0-9])([A-Z][A-Z0-9]+-\d+)(?=[^A-Z0-9]|$)/', strtoupper($filename), $m)) {
        $taskJira = $m[1];
    }
    $questionario = [
        'nome_progetto' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B4') ?: pathinfo($filename, PATHINFO_FILENAME),
        'codice_progetto' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B5'),
        'nome_servizio' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B6'),
        'business_line' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B7'),
        'pm' => '',
        'pm_product_manager' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B8'),
        'po' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B9'),
        'tpo' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B10'),
        'tipologia_progetto' => $tipologia,
        'task_jira' => $taskJira,
        'descrizione' => questionario_import_initial_value($sheets, 'Informazioni preliminari', 'B11'),
        'note' => implode("\n", $extraNotes),
        'analista_questionario_id' => current_user()['id'] ?? null,
    ];

    return [
        'filename' => $filename,
        'questionario' => $questionario,
        'answers' => $answers,
        'notes' => $notes,
        'rows' => $rows,
        'warnings' => array_values(array_unique($warnings)),
        'stats' => [
            'sheets' => count($sheets),
            'answers' => count($answers),
            'positive' => count(array_filter($answers, fn($value) => (string)$value === '1')),
            'negative' => count(array_filter($answers, fn($value) => (string)$value === '0')),
        ],
    ];
}

function questionario_import_commit(array $preview, bool $calculate = true): int {
    $questionario = $preview['questionario'] ?? [];
    $answers = $preview['answers'] ?? [];
    $notes = $preview['notes'] ?? [];
    if (!$questionario || !$answers) {
        throw new RuntimeException('Preview import non valida o incompleta.');
    }
    $id = create_questionario($questionario);
    save_risposte($id, $answers, $notes);
    if ($calculate) {
        calcola_risultati($id);
    }
    return $id;
}

function questionario_risposte_map(int $questionario_id): array {
    $st = get_db()->prepare(
        "SELECT d.codice, qr.valore
         FROM questionario_risposte qr
         JOIN domande d ON d.id = qr.domanda_id
         WHERE qr.questionario_id = ?"
    );
    $st->execute([$questionario_id]);
    $risposte = [];
    foreach ($st->fetchAll() as $r) {
        $risposte[$r["codice"]] = $r["valore"];
    }
    return $risposte;
}

function requisito_latest_version_row(int $requisito_id): array|false {
    $st = get_db()->prepare(
        "SELECT *
         FROM requisito_versioni
         WHERE entity_type = 'catalogo' AND entity_id = ?
         ORDER BY version_no DESC, id DESC
         LIMIT 1"
    );
    $st->execute([$requisito_id]);
    return $st->fetch();
}

function requisito_first_version_row(int $requisito_id): array|false {
    $st = get_db()->prepare(
        "SELECT *
         FROM requisito_versioni
         WHERE entity_type = 'catalogo' AND entity_id = ?
         ORDER BY version_no ASC, id ASC
         LIMIT 1"
    );
    $st->execute([$requisito_id]);
    return $st->fetch();
}

function create_questionario_requisiti_calcolo(int $questionario_id, array $risposte, string $note = "ricalcolo"): int {
    $user = current_user();
    get_db()->prepare(
        "INSERT INTO questionario_requisiti_calcoli (questionario_id, created_by_user_id, risposte_hash, note)
         VALUES (?, ?, ?, ?)"
    )->execute([
        $questionario_id,
        $user ? (int)$user["id"] : null,
        hash("sha256", requisito_version_json($risposte)),
        $note,
    ]);
    return (int)get_db()->lastInsertId();
}

function save_questionario_requisito_snapshot(int $calcolo_id, int $questionario_id, array $requisito, string $applicabile): void {
    if ($applicabile !== "si") {
        return;
    }
    $version = requisito_latest_version_row((int)$requisito["id"]);
    $versionId = $version ? (int)$version["id"] : null;
    $versionNo = $version ? (int)$version["version_no"] : null;
    $snapshot = $version ? requisito_version_decode((string)$version["snapshot_json"]) : $requisito;
    if (!$snapshot) {
        $snapshot = $requisito;
    }
    $correlations = $version ? requisito_version_decode((string)$version["correlations_json"]) : requisito_catalogo_correlations((int)$requisito["id"]);
    $assignmentType = requirement_is_standard($snapshot) ? "default_design" : "catalogo";

    get_db()->prepare(
        "INSERT INTO questionario_requisiti_snapshot
         (calcolo_id, questionario_id, requisito_id, applicabile, assegnazione_tipo, requisito_version_id, requisito_version_no,
          requisito_versione_label, codice, titolo, categoria, snapshot_json, correlations_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           applicabile = VALUES(applicabile),
           assegnazione_tipo = VALUES(assegnazione_tipo),
           requisito_version_id = VALUES(requisito_version_id),
           requisito_version_no = VALUES(requisito_version_no),
           requisito_versione_label = VALUES(requisito_versione_label),
           codice = VALUES(codice),
           titolo = VALUES(titolo),
           categoria = VALUES(categoria),
           snapshot_json = VALUES(snapshot_json),
           correlations_json = VALUES(correlations_json)"
    )->execute([
        $calcolo_id,
        $questionario_id,
        (int)$requisito["id"],
        $applicabile,
        $assignmentType,
        $versionId,
        $versionNo,
        (string)($snapshot["versione"] ?? $requisito["versione"] ?? ""),
        (string)($snapshot["codice"] ?? $requisito["codice"] ?? ""),
        (string)($snapshot["titolo"] ?? $requisito["titolo"] ?? ""),
        (string)($snapshot["categoria"] ?? $requisito["categoria"] ?? ""),
        requisito_version_json($snapshot),
        requisito_version_json($correlations),
    ]);
}

function backfill_questionario_requisiti_snapshot(int $questionario_id): int {
    if (get_latest_questionario_requisiti_calcolo($questionario_id)) {
        return 0;
    }
    $risposte = questionario_risposte_map($questionario_id);
    $calcolo_id = create_questionario_requisiti_calcolo($questionario_id, $risposte, "baseline storico da risultati correnti");
    $st = get_db()->prepare(
        "SELECT r.*, qrr.applicabile
         FROM questionario_risultati_requisiti qrr
         JOIN requisiti r ON r.id = qrr.requisito_id
         WHERE qrr.questionario_id = ? AND qrr.applicabile = 'si'
         ORDER BY r.categoria, r.codice"
    );
    $st->execute([$questionario_id]);
    $count = 0;
    foreach ($st->fetchAll() as $requisito) {
        save_questionario_requisito_snapshot($calcolo_id, $questionario_id, $requisito, "si");
        $count++;
    }
    return $count;
}

function evaluate_requisito_for_risposte(array $requisito, array $risposte): string {
    $groups = get_regole_requisiti_gruppi((int)$requisito["id"]);
    if (empty($groups)) {
        return "si";
    }
    foreach ($groups as $group) {
        $rule_rows = get_regole_requisiti_by_gruppo((int)$group["id"]);
        if ($rule_rows && evaluate_rule_rows($rule_rows, $risposte, (string)$group["operatore_logico"])) {
            return "si";
        }
    }
    return "no";
}

function calcola_risultati(int $questionario_id): void {
    $db = get_db();
    $risposte = questionario_risposte_map($questionario_id);
    $calcolo_id = create_questionario_requisiti_calcolo($questionario_id, $risposte);

    $db->prepare("DELETE FROM questionario_risultati_requisiti WHERE questionario_id = ?")
       ->execute([$questionario_id]);

    $reqs = $db->query("SELECT * FROM requisiti WHERE attivo = 1")->fetchAll();
    $insReq = $db->prepare(
        "INSERT INTO questionario_risultati_requisiti (questionario_id, requisito_id, applicabile)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE applicabile = VALUES(applicabile)"
    );

    foreach ($reqs as $req) {
        $applicabile = evaluate_requisito_for_risposte($req, $risposte);
        $insReq->execute([$questionario_id, $req["id"], $applicabile]);
        save_questionario_requisito_snapshot($calcolo_id, $questionario_id, $req, $applicabile);
    }

    $db->prepare("DELETE FROM questionario_risultati_servizi WHERE questionario_id = ? AND manuale = 0")
       ->execute([$questionario_id]);

    $servizi = $db->query("SELECT id, regole_operatore_logico FROM servizi WHERE attivo = 1")->fetchAll();
    $insSrv = $db->prepare(
        "INSERT INTO questionario_risultati_servizi (questionario_id, servizio_id, applicabile, manuale)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE applicabile = IF(manuale = 1, 1, VALUES(applicabile))"
    );

    foreach ($servizi as $srv) {
        $groups = get_regole_servizi_gruppi((int)$srv["id"]);
        $applicabile = 0;
        foreach ($groups as $group) {
            $rule_rows = get_regole_servizi_by_gruppo((int)$group["id"]);
            if ($rule_rows && evaluate_rule_rows($rule_rows, $risposte, (string)$group["operatore_logico"])) {
                $applicabile = 1;
                break;
            }
        }
        $insSrv->execute([$questionario_id, $srv["id"], $applicabile, 0]);
    }

    $db->prepare("UPDATE questionari SET stato = 'completato' WHERE id = ?")
       ->execute([$questionario_id]);
}

function evaluate_rule_rows(array $rule_rows, array $risposte, string $operatore): bool {
    if (!$rule_rows) {
        return false;
    }
    if ($operatore === "AND") {
        foreach ($rule_rows as $rule) {
            $ans = $risposte[$rule["codice"]] ?? "0";
            if ($ans !== $rule["valore_atteso"]) {
                return false;
            }
        }
        return true;
    }
    foreach ($rule_rows as $rule) {
        $ans = $risposte[$rule["codice"]] ?? "0";
        if ($ans === $rule["valore_atteso"]) {
            return true;
        }
    }
    return false;
}

// â”€â”€ Risultati â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function get_risultati_requisiti(int $questionario_id, bool $only_applicable = false): array {
    $sql =
        "SELECT r.*, qrr.applicabile, qrr.valutazione_manuale
         FROM questionario_risultati_requisiti qrr
         JOIN requisiti r ON r.id = qrr.requisito_id
         WHERE qrr.questionario_id = ?";
    if ($only_applicable) $sql .= " AND qrr.applicabile = 'si'";
    $sql .= " ORDER BY r.categoria, r.codice";
    $st = get_db()->prepare($sql);
    $st->execute([$questionario_id]);
    return $st->fetchAll();
}

function requirement_is_standard(array $requirement): bool {
    return (int)($requirement["standard"] ?? 0) === 1
        || trim((string)($requirement["standard_dove"] ?? "")) !== ""
        || trim((string)($requirement["std"] ?? "")) !== "";
}

function truthy_checkbox(array $data, string $field): bool {
    return isset($data[$field]) && !in_array((string)$data[$field], ["", "0"], true);
}

function get_requisiti_revisionati(int $questionario_id): array {
    $st = get_db()->prepare(
        "SELECT r.*, qrr.applicabile, qro.azione AS override_azione, qro.note AS override_note
         FROM requisiti r
         LEFT JOIN questionario_risultati_requisiti qrr
           ON qrr.requisito_id = r.id AND qrr.questionario_id = ?
         LEFT JOIN questionario_requisiti_override qro
           ON qro.requisito_id = r.id AND qro.questionario_id = ?
         WHERE r.attivo = 1
           AND (
                qrr.applicabile = 'si'
                OR qro.azione = 'include'
           )
           AND (qro.azione IS NULL OR qro.azione <> 'exclude')
         ORDER BY r.categoria, r.codice"
    );
    $st->execute([$questionario_id, $questionario_id]);
    $catalogo = [];
    $standard = [];
    foreach ($st->fetchAll() as $row) {
        if (requirement_is_standard($row)) {
            $standard[] = $row;
        } else {
            $catalogo[] = $row;
        }
    }
    return [
        "catalogo" => $catalogo,
        "standard" => $standard,
        "specifici" => get_requisiti_specifici($questionario_id),
    ];
}

function get_latest_questionario_requisiti_calcolo(int $questionario_id): array|false {
    $st = get_db()->prepare(
        "SELECT *
         FROM questionario_requisiti_calcoli
         WHERE questionario_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 1"
    );
    $st->execute([$questionario_id]);
    return $st->fetch();
}

function get_questionario_requisiti_snapshot(int $questionario_id, int $calcolo_id = 0): array {
    if ($calcolo_id <= 0) {
        $latest = get_latest_questionario_requisiti_calcolo($questionario_id);
        $calcolo_id = $latest ? (int)$latest["id"] : 0;
    }
    if ($calcolo_id <= 0) {
        return [];
    }
    $st = get_db()->prepare(
        "SELECT *
         FROM questionario_requisiti_snapshot
         WHERE questionario_id = ? AND calcolo_id = ? AND applicabile = 'si'
         ORDER BY categoria, codice, id"
    );
    $st->execute([$questionario_id, $calcolo_id]);
    return $st->fetchAll();
}

function requisito_snapshot_to_requirement(array $snapshotRow): array {
    $snapshot = requisito_version_decode((string)($snapshotRow["snapshot_json"] ?? ""));
    if (!$snapshot) {
        $snapshot = [];
    }
    $snapshot["id"] = (int)$snapshotRow["requisito_id"];
    $snapshot["codice"] = (string)($snapshot["codice"] ?? $snapshotRow["codice"] ?? "");
    $snapshot["titolo"] = (string)($snapshot["titolo"] ?? $snapshotRow["titolo"] ?? "");
    $snapshot["categoria"] = (string)($snapshot["categoria"] ?? $snapshotRow["categoria"] ?? "");
    $snapshot["applicabile"] = (string)($snapshotRow["applicabile"] ?? "si");
    $snapshot["snapshot_calcolo_id"] = (int)$snapshotRow["calcolo_id"];
    $snapshot["snapshot_created_at"] = (string)($snapshotRow["created_at"] ?? "");
    $snapshot["snapshot_requisito_version_id"] = (int)($snapshotRow["requisito_version_id"] ?? 0);
    $snapshot["snapshot_requisito_version_no"] = (int)($snapshotRow["requisito_version_no"] ?? 0);
    $snapshot["snapshot_requisito_versione_label"] = (string)($snapshotRow["requisito_versione_label"] ?? "");
    $snapshot["snapshot_assegnazione_tipo"] = (string)($snapshotRow["assegnazione_tipo"] ?? "catalogo");
    return $snapshot;
}

function requisito_snapshot_compare_status(array $snapshotRequirement): array {
    $current = get_requisito((int)($snapshotRequirement["id"] ?? 0));
    $historicVersionNo = (int)($snapshotRequirement["snapshot_requisito_version_no"] ?? 0);
    $historicVersionLabel = (string)($snapshotRequirement["snapshot_requisito_versione_label"] ?? $snapshotRequirement["versione"] ?? "");
    if (!$current) {
        return [
            "tone" => "danger",
            "label" => "Requisito non piÃ¹ presente nel catalogo",
            "detail" => "Assegnato come versione storica " . ($historicVersionLabel ?: "N/D") . "; oggi non Ã¨ piÃ¹ presente o Ã¨ stato disattivato.",
        ];
    }
    $latest = requisito_latest_version_row((int)$current["id"]);
    $currentVersionNo = $latest ? (int)$latest["version_no"] : 0;
    $currentVersionLabel = (string)($current["versione"] ?? "");
    $changedFields = [];
    foreach (["codice","versione","categoria","sottocategoria","titolo","descrizione","contesto","note","importanza","std","standard","standard_dove","owner","fase","framework_function"] as $field) {
        if ((string)($snapshotRequirement[$field] ?? "") !== (string)($current[$field] ?? "")) {
            $changedFields[] = $field;
        }
    }
    if (($currentVersionNo > 0 && $historicVersionNo > 0 && $currentVersionNo !== $historicVersionNo) || $changedFields) {
        return [
            "tone" => "warning",
            "label" => "Modificato nel tempo",
            "detail" => "All'assegnazione: versione catalogo #" . ($historicVersionNo ?: "N/D")
                . " / v" . ($historicVersionLabel ?: "N/D")
                . ". Oggi: versione catalogo #" . ($currentVersionNo ?: "N/D")
                . " / v" . ($currentVersionLabel ?: "N/D")
                . ($changedFields ? ". Campi variati: " . implode(", ", array_slice($changedFields, 0, 5)) . "." : "."),
        ];
    }
    return [
        "tone" => "success",
        "label" => "GiÃ  presente all'assegnazione",
        "detail" => "Il requisito era giÃ  presente all'assegnazione ed Ã¨ ancora allineato al catalogo corrente"
            . ($historicVersionNo ? " (versione catalogo #{$historicVersionNo})" : "")
            . ".",
    ];
}

function get_current_applicable_requisiti_catalogo(int $questionario_id): array {
    $risposte = questionario_risposte_map($questionario_id);
    $rows = [];
    foreach (get_db()->query("SELECT * FROM requisiti WHERE attivo = 1 ORDER BY categoria, codice")->fetchAll() as $requisito) {
        if (evaluate_requisito_for_risposte($requisito, $risposte) === "si") {
            $rows[] = $requisito;
        }
    }
    return $rows;
}

function requisito_candidate_history_status(array $requisito, array|false $calcolo): array {
    $createdAfterAssignment = false;
    if ($calcolo) {
        $first = requisito_first_version_row((int)$requisito["id"]);
        if ($first && strtotime((string)$first["changed_at"]) > strtotime((string)$calcolo["created_at"])) {
            $createdAfterAssignment = true;
        }
    }
    if ($createdAfterAssignment) {
        return [
            "tone" => "info",
            "label" => "Nuovo requisito di catalogo",
            "detail" => "All'epoca dell'assegnazione non esisteva nel catalogo; oggi risulta funzionale al progetto.",
        ];
    }
    return [
        "tone" => "info",
        "label" => "Non presente nell'assegnazione storica",
        "detail" => "Non era tra i requisiti assegnati nella fotografia storica; oggi le regole di catalogo lo rendono funzionale al progetto.",
    ];
}

function get_requisiti_esclusi(int $questionario_id): array {
    $st = get_db()->prepare(
        "SELECT r.*, qro.note AS override_note
         FROM questionario_requisiti_override qro
         JOIN requisiti r ON r.id = qro.requisito_id
         WHERE qro.questionario_id = ?
           AND qro.azione = 'exclude'
         ORDER BY r.categoria, r.codice"
    );
    $st->execute([$questionario_id]);
    return $st->fetchAll();
}

function set_requisito_override(int $questionario_id, int $requisito_id, string $azione, string $note = ""): void {
    if (!in_array($azione, ["include", "exclude"], true)) {
        return;
    }
    get_db()->prepare(
        "INSERT INTO questionario_requisiti_override (questionario_id,requisito_id,azione,note)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE azione=VALUES(azione), note=VALUES(note)"
    )->execute([$questionario_id, $requisito_id, $azione, $note]);
}

function clear_requisito_override(int $questionario_id, int $requisito_id): void {
    get_db()->prepare(
        "DELETE FROM questionario_requisiti_override WHERE questionario_id = ? AND requisito_id = ?"
    )->execute([$questionario_id, $requisito_id]);
}

function get_requisiti_specifici(int $questionario_id, bool $only_active = true): array {
    $sql =
        "SELECT s.*
         FROM questionario_requisiti_specifici_link l
         JOIN questionario_requisiti_specifici s ON s.id = l.requisito_specifico_id
         WHERE l.questionario_id = ?";
    if ($only_active) $sql .= " AND s.attivo = 1";
    $sql .= " ORDER BY s.created_at DESC, s.id DESC";
    $st = get_db()->prepare($sql);
    $st->execute([$questionario_id]);
    return $st->fetchAll();
}

function get_requisito_specifico(int $id): array|false {
    $st = get_db()->prepare(
        "SELECT s.*, q.nome_progetto, q.codice_progetto
         FROM questionario_requisiti_specifici s
         LEFT JOIN questionari q ON q.id = s.questionario_id
         WHERE s.id = ?"
    );
    $st->execute([$id]);
    return $st->fetch();
}

function get_all_requisiti_specifici(bool $only_active = true): array {
    $sql =
        "SELECT s.*, q.nome_progetto, q.codice_progetto,
                COUNT(l.questionario_id) AS questionari_collegati
         FROM questionario_requisiti_specifici s
         LEFT JOIN questionari q ON q.id = s.questionario_id
         LEFT JOIN questionario_requisiti_specifici_link l ON l.requisito_specifico_id = s.id";
    if ($only_active) {
        $sql .= " WHERE s.attivo = 1";
    }
    $sql .= " GROUP BY s.id ORDER BY s.created_at DESC, s.id DESC";
    return get_db()->query($sql)->fetchAll();
}

function get_requisiti_specifici_riusabili(int $questionario_id): array {
    $st = get_db()->prepare(
        "SELECT s.*, q.nome_progetto, q.codice_progetto
         FROM questionario_requisiti_specifici s
         LEFT JOIN questionari q ON q.id = s.questionario_id
         LEFT JOIN questionario_requisiti_specifici_link l
           ON l.requisito_specifico_id = s.id AND l.questionario_id = ?
         WHERE s.attivo = 1
           AND l.requisito_specifico_id IS NULL
         ORDER BY s.titolo, s.codice"
    );
    $st->execute([$questionario_id]);
    return $st->fetchAll();
}

function get_questionari_for_requisito_specifico(int $specifico_id): array {
    $st = get_db()->prepare(
        "SELECT q.*
         FROM questionario_requisiti_specifici_link l
         JOIN questionari q ON q.id = l.questionario_id
         WHERE l.requisito_specifico_id = ?
         ORDER BY q.created_at DESC, q.id DESC"
    );
    $st->execute([$specifico_id]);
    return $st->fetchAll();
}

function link_requisito_specifico_to_questionario(int $questionario_id, int $specifico_id): void {
    get_db()->prepare(
        "INSERT IGNORE INTO questionario_requisiti_specifici_link (questionario_id,requisito_specifico_id)
         VALUES (?,?)"
    )->execute([$questionario_id, $specifico_id]);
}

function save_requisito_specifico(array $data): int {
    $db = get_db();
    $id = (int)($data["id"] ?? 0);
    $categoria_id = (int)($data["categoria_id"] ?? 0);
    if ($categoria_id > 0) {
        $data["categoria"] = get_requisito_categoria_nome($categoria_id);
    }
    if (!truthy_checkbox($data, "standard")) {
        $data["std"] = "";
        $data["standard_dove"] = "";
    } elseif (trim((string)($data["std"] ?? "")) === "") {
        $data["std"] = trim((string)($data["standard_dove"] ?? ""));
    }
    if (!isset($data["regole_operatore_logico"])) {
        $existing = $id > 0 ? get_requisito_specifico($id) : null;
        $data["regole_operatore_logico"] = $existing["regole_operatore_logico"] ?? "OR";
    }
    $data["regole_operatore_logico"] = normalize_rule_operator((string)$data["regole_operatore_logico"]);
    $fields = [
        "questionario_id","task_jira","codice","versione","categoria","sottocategoria","titolo","descrizione","contesto","note",
        "importanza","std","standard","standard_dove","owner","fase","framework_function","funzionale_tecnologico","data_protection","rif_iso","rif_fncs","software_selection",
        "riferimento_hld","pubblicato_lga","rif_std_config_dc","standardizzazione_controllo_task","rif_procedura_controllo","ultimo_update"
    ];
    $vals = [];
    foreach ($fields as $field) {
        if ($field === "questionario_id") {
            $vals[] = (int)($data[$field] ?? 0);
        } elseif ($field === "standard") {
            $vals[] = truthy_checkbox($data, $field) ? 1 : 0;
        } else {
            $vals[] = trim((string)($data[$field] ?? ""));
        }
    }
    $attivo = isset($data["attivo"]) ? 1 : 0;
    if ($id > 0) {
        $sets = implode(",", array_map(fn($f) => "$f=?", $fields));
        $db->prepare("UPDATE questionario_requisiti_specifici SET $sets,attivo=? WHERE id=?")
           ->execute([...$vals, $attivo, $id]);
        sync_requisito_specifico_categoria($id, $categoria_id, (string)($data["categoria"] ?? ""));
        if ((int)($data["questionario_id"] ?? 0) > 0) {
            link_requisito_specifico_to_questionario((int)$data["questionario_id"], $id);
        }
        capture_requisito_version("specifico", $id, "update");
        return $id;
    }
    $cols = implode(",", $fields);
    $phs = implode(",", array_fill(0, count($fields), "?"));
    $db->prepare("INSERT INTO questionario_requisiti_specifici ($cols,attivo) VALUES ($phs,?)")
       ->execute([...$vals, $attivo]);
    $newId = (int)$db->lastInsertId();
    sync_requisito_specifico_categoria($newId, $categoria_id, (string)($data["categoria"] ?? ""));
    if ((int)($data["questionario_id"] ?? 0) > 0) {
        link_requisito_specifico_to_questionario((int)$data["questionario_id"], $newId);
    }
    capture_requisito_version("specifico", $newId, "create");
    return $newId;
}

function delete_requisito_specifico(int $id, int $questionario_id): void {
    $db = get_db();
    $db->prepare(
        "DELETE FROM questionario_requisiti_specifici_link WHERE requisito_specifico_id = ? AND questionario_id = ?"
    )->execute([$id, $questionario_id]);
    $st = $db->prepare("SELECT COUNT(*) FROM questionario_requisiti_specifici_link WHERE requisito_specifico_id = ?");
    $st->execute([$id]);
    if ((int)$st->fetchColumn() === 0) {
        $db->prepare("UPDATE questionario_requisiti_specifici SET attivo = 0 WHERE id = ?")->execute([$id]);
        capture_requisito_version("specifico", $id, "deactivate");
    } else {
        capture_requisito_version("specifico", $id, "unlink_questionario");
    }
}

function deactivate_requisito_specifico(int $id): void {
    get_db()->prepare("UPDATE questionario_requisiti_specifici SET attivo = 0 WHERE id = ?")->execute([$id]);
    capture_requisito_version("specifico", $id, "deactivate");
}

function promote_requisito_specifico_to_catalogo(int $id): int {
    $specifico = get_requisito_specifico($id);
    if (!$specifico) {
        return 0;
    }
    $codice = trim((string)($specifico["codice"] ?? ""));
    if ($codice === "") {
        $codice = "PRJ-SPEC-" . $id;
    }
    if (get_requisito_by_codice($codice)) {
        $codice .= "-CAT-" . $id;
    }
    $data = $specifico;
    $data["id"] = 0;
    $data["codice"] = $codice;
    $data["catalogo_source"] = "Requisito specifico #" . $id . " - Questionario #" . (int)$specifico["questionario_id"];
    $data["attivo"] = 1;
    $categoria_id = get_requisito_specifico_categoria_id($id);
    if ($categoria_id > 0) {
        $data["categoria_id"] = $categoria_id;
    }
    $newId = save_requisito($data);
    get_db()->prepare(
        "UPDATE questionario_requisiti_specifici
         SET attivo = 0, requisito_catalogo_id = ?, promosso_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    )->execute([$newId, $id]);
    capture_requisito_version("specifico", $id, "promote_to_catalogo");
    return $newId;
}

function get_risultati_servizi(int $questionario_id, bool $only_applicable = false): array {
    $sql =
        "SELECT s.*, qrs.applicabile, qrs.manuale, qrs.note AS risultato_note
         FROM questionario_risultati_servizi qrs
         JOIN servizi s ON s.id = qrs.servizio_id
         WHERE qrs.questionario_id = ?";
    if ($only_applicable) $sql .= " AND qrs.applicabile = 1";
    $sql .= " ORDER BY s.reparto_owner, s.macro_service, s.servizio_elementare";
    $st = get_db()->prepare($sql);
    $st->execute([$questionario_id]);
    return $st->fetchAll();
}

// â”€â”€ Requisiti â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function get_requisiti(bool $only_active = false): array {
    $sql = "SELECT * FROM requisiti";
    if ($only_active) $sql .= " WHERE attivo = 1";
    $sql .= " ORDER BY categoria, codice";
    return get_db()->query($sql)->fetchAll();
}

function get_requisito(int $id): array|false {
    $st = get_db()->prepare("SELECT * FROM requisiti WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch();
}

function get_requisito_by_codice(string $codice): array|false {
    $st = get_db()->prepare("SELECT * FROM requisiti WHERE codice = ?");
    $st->execute([$codice]);
    return $st->fetch();
}

function get_requisito_categorie(bool $only_active = true): array {
    $sql = "SELECT * FROM requisito_categorie";
    if ($only_active) {
        $sql .= " WHERE attiva = 1";
    }
    $sql .= " ORDER BY nome";
    return get_db()->query($sql)->fetchAll();
}

function get_requisito_categoria_nome(int $id): string {
    if ($id <= 0) {
        return "";
    }
    $st = get_db()->prepare("SELECT nome FROM requisito_categorie WHERE id = ?");
    $st->execute([$id]);
    return (string)$st->fetchColumn();
}

function ensure_requisito_categoria(string $nome, string $framework_function = "", string $rif_fncs = ""): int {
    $nome = trim($nome);
    if ($nome === "") {
        return 0;
    }
    $db = get_db();
    $db->prepare(
        "INSERT INTO requisito_categorie (nome, framework_function, rif_fncs)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
           framework_function = IF(COALESCE(VALUES(framework_function), '') <> '', VALUES(framework_function), framework_function),
           rif_fncs = IF(COALESCE(VALUES(rif_fncs), '') <> '', VALUES(rif_fncs), rif_fncs)"
    )->execute([$nome, $framework_function, $rif_fncs]);
    $st = $db->prepare("SELECT id FROM requisito_categorie WHERE nome = ?");
    $st->execute([$nome]);
    return (int)$st->fetchColumn();
}

function get_requisito_categoria_id(int $requisito_id): int {
    $st = get_db()->prepare("SELECT categoria_id FROM requisito_categoria WHERE requisito_id = ? LIMIT 1");
    $st->execute([$requisito_id]);
    return (int)$st->fetchColumn();
}

function get_requisito_specifico_categoria_id(int $specifico_id): int {
    $st = get_db()->prepare("SELECT categoria_id FROM requisito_specifico_categoria WHERE requisito_specifico_id = ? LIMIT 1");
    $st->execute([$specifico_id]);
    return (int)$st->fetchColumn();
}

function sync_requisito_categoria(int $requisito_id, int $categoria_id, string $categoria_nome = ""): void {
    if ($categoria_id <= 0) {
        $categoria_id = ensure_requisito_categoria($categoria_nome);
    }
    get_db()->prepare("DELETE FROM requisito_categoria WHERE requisito_id = ?")->execute([$requisito_id]);
    if ($categoria_id > 0) {
        get_db()->prepare("INSERT IGNORE INTO requisito_categoria (requisito_id,categoria_id) VALUES (?,?)")
            ->execute([$requisito_id, $categoria_id]);
    }
}

function sync_requisito_specifico_categoria(int $specifico_id, int $categoria_id, string $categoria_nome = ""): void {
    if ($categoria_id <= 0) {
        $categoria_id = ensure_requisito_categoria($categoria_nome);
    }
    get_db()->prepare("DELETE FROM requisito_specifico_categoria WHERE requisito_specifico_id = ?")->execute([$specifico_id]);
    if ($categoria_id > 0) {
        get_db()->prepare("INSERT IGNORE INTO requisito_specifico_categoria (requisito_specifico_id,categoria_id) VALUES (?,?)")
            ->execute([$specifico_id, $categoria_id]);
    }
}

// â”€â”€ Servizi â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€



function default_threat_analysis_prompt(): string {
    return <<<'PROMPT'
Sei un senior cyber security analyst. Devi produrre una THREAT ANALYSIS in italiano, con stile operativo, concreto e adatto a una revisione di sicurezza di progetto.

Usa SOLO le informazioni fornite nel contesto del questionario. Se un dato manca, dichiaralo come assunzione o punto da verificare; non inventare evidenze.

Produci l'analisi con questa struttura:

# THREAT ANALYSIS - ANALISI OPERATIVA

## 1. SINTESI DEL CONTESTO
- Descrivi progetto, servizio, business line, task JIRA, descrizione e note.
- Riassumi cosa emerge dalle risposte del questionario.
- Evidenzia i rischi principali giÃ  intuibili.

## 2. ASSUNZIONI
Elenca assunzioni esplicite e verificabili.

## 3. MINACCE TRACCIATE
Crea una tabella con colonne: ID, Minaccia, Scenario, Impatto, Rischio, Requisito chiave.
Usa ID T-01, T-02, ecc.

## 4. REQUISITI DI SICUREZZA E IMPLEMENTAZIONE
Crea una tabella con colonne: ID operativo, Requisito, Dettaglio operativo, PrioritÃ , Riferimenti interni / note.
Integra requisiti catalogo, requisiti standard e requisiti specifici di progetto.

## 5. SERVIZI / CONTROLLI COINVOLTI
Elenca i servizi applicabili e spiega perchÃ© sono rilevanti.

## 6. GAP, RISCHI RESIDUI E CONTROLLI COMPENSATIVI
Crea una tabella con: Gap, Rischio residuo, Controllo compensativo, Evidenza richiesta.

## 7. RACCOMANDAZIONE
Scrivi una raccomandazione pratica e motivata, distinguendo MUST, SHOULD e MAY.

## 8. CRITERI MINIMI DI ACCETTAZIONE / POC
Crea una tabella con colonne: Test, Esito atteso.

## 9. ESITO
Concludi con decisione proposta, condizioni di approvazione e prossimi passi.

Regole di output:
- Usa Markdown pulito.
- Non usare frasi generiche: collega ogni rischio a dati del questionario o requisiti.
- Evidenzia in modo netto rischi alti, dipendenze esterne, dati personali, esposizione Internet, accessi privilegiati, segregazione, logging, MFA, SIEM, backup, vulnerability management e terze parti se emergono dal contesto.
PROMPT;
}

function compact_ai_value(mixed $value, int $maxLength = 500): mixed {
    if (!is_scalar($value)) {
        return $value;
    }
    $text = trim((string)$value);
    if ($maxLength > 0) {
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($length > $maxLength) {
            $cut = function_exists('mb_substr') ? mb_substr($text, 0, $maxLength, 'UTF-8') : substr($text, 0, $maxLength);
            return $cut . 'â€¦';
        }
    }
    return $text;
}

function compact_ai_row(array $row, array $fields, int $maxLength = 500): array {
    $out = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $row)) {
            $value = $row[$field];
            if ($value !== null && trim((string)$value) !== '') {
                $out[$field] = compact_ai_value($value, $maxLength);
            }
        }
    }
    return $out;
}

function build_questionario_ai_context(int $questionario_id): array {
    $questionario = get_questionario($questionario_id);
    if (!$questionario) {
        return [];
    }
    $risposte = [];
    foreach (get_risposte($questionario_id) as $risposta) {
        $risposte[] = compact_ai_row($risposta, ['codice', 'testo', 'tipo', 'valore', 'note'], 260);
    }

    $requisitiCatalogo = [];
    $requisitiStandard = [];
    foreach (get_risultati_requisiti($questionario_id, true) as $requisito) {
        $row = compact_ai_row($requisito, ['codice', 'titolo', 'categoria', 'descrizione', 'importanza', 'standard', 'standard_dove', 'owner', 'fase', 'valutazione_manuale'], 220);
        if (requirement_is_standard($requisito)) {
            $requisitiStandard[] = compact_ai_row($requisito, ['codice', 'titolo', 'standard_dove', 'importanza'], 120);
        } else {
            $requisitiCatalogo[] = $row;
        }
    }

    $specifici = [];
    foreach (get_requisiti_specifici($questionario_id, true) as $specifico) {
        $specifici[] = compact_ai_row($specifico, ['codice', 'titolo', 'categoria', 'sottocategoria', 'descrizione', 'contesto', 'note', 'importanza', 'task_jira', 'owner', 'fase'], 360);
    }

    $servizi = [];
    foreach (get_risultati_servizi($questionario_id, true) as $servizio) {
        $servizi[] = compact_ai_row($servizio, ['reparto_owner', 'portfolio_category', 'macro_service', 'categoria', 'servizio_elementare', 'descrizione', 'tipo_attivita', 'misurabilita', 'commessa', 'check_component', 'asset_primario', 'software', 'orario_servizio', 'note'], 320);
    }

    return [
        'questionario' => compact_ai_row($questionario, ['id', 'nome_progetto', 'codice_progetto', 'nome_servizio', 'business_line', 'pm', 'pm_product_manager', 'po', 'tpo', 'tipologia_progetto', 'task_jira', 'analista_questionario_nome', 'descrizione', 'note', 'stato', 'created_at'], 500),
        'risposte' => $risposte,
        'requisiti_catalogo_applicabili' => $requisitiCatalogo,
        'requisiti_standard' => $requisitiStandard,
        'requisiti_specifici_progetto' => $specifici,
        'servizi_applicabili' => $servizi,
    ];
}

function threat_analysis_full_prompt(string $userPrompt, array $context): string {
    return trim($userPrompt) . "\n\n--- CONTESTO QUESTIONARIO / DATI APPLICATIVI ---\n" . requisito_version_json($context);
}

function ai_provider_types(): array {
    return [
        'ollama' => 'Ollama',
        'openai_compatible' => 'OpenAI compatible',
    ];
}

function ai_provider_default_row(): array {
    return [
        'id' => 0,
        'nome' => 'Ollama locale',
        'provider_type' => 'ollama',
        'base_url' => ollama_base_url(),
        'api_key' => '',
        'default_model' => OLLAMA_MODEL,
        'model_list' => '',
        'timeout_seconds' => OLLAMA_TIMEOUT_SECONDS,
        'enabled' => 1,
        'is_default' => 1,
    ];
}

function ai_provider_base_url(?array $provider = null): string {
    $provider = $provider ?: get_default_ai_provider();
    $baseUrl = trim((string)($provider['base_url'] ?? ''));
    return rtrim($baseUrl !== '' ? $baseUrl : ollama_base_url(), '/');
}

function get_ai_providers(bool $onlyEnabled = false): array {
    try {
        $sql = "SELECT * FROM ai_providers";
        if ($onlyEnabled) {
            $sql .= " WHERE enabled = 1";
        }
        $sql .= " ORDER BY is_default DESC, nome";
        $rows = get_db()->query($sql)->fetchAll();
        return $rows ?: [ai_provider_default_row()];
    } catch (Throwable $e) {
        return [ai_provider_default_row()];
    }
}

function get_ai_provider(int $id): array|false {
    if ($id <= 0) {
        return get_default_ai_provider();
    }
    try {
        $st = get_db()->prepare("SELECT * FROM ai_providers WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: false;
    } catch (Throwable $e) {
        return false;
    }
}

function get_default_ai_provider(): array {
    try {
        $row = get_db()->query("SELECT * FROM ai_providers WHERE enabled = 1 ORDER BY is_default DESC, id LIMIT 1")->fetch();
        return $row ?: ai_provider_default_row();
    } catch (Throwable $e) {
        return ai_provider_default_row();
    }
}

function ai_provider_model_list(?array $provider): array {
    $raw = (string)($provider['model_list'] ?? '');
    $models = [];
    foreach (preg_split('/[\r\n,;]+/', $raw) ?: [] as $model) {
        $model = trim($model);
        if ($model !== '') {
            $models[$model] = $model;
        }
    }
    $defaultModel = trim((string)($provider['default_model'] ?? ''));
    if ($defaultModel !== '') {
        $models[$defaultModel] = $defaultModel;
    }
    ksort($models);
    return array_values($models);
}

function ai_provider_models(?array $provider = null): array {
    $provider = $provider ?: get_default_ai_provider();
    $configured = ai_provider_model_list($provider);
    if (($provider['provider_type'] ?? 'ollama') !== 'ollama') {
        return $configured;
    }
    $detected = ollama_models($provider);
    return array_values(array_unique(array_merge($detected, $configured)));
}

function ai_runtime_status(int $ttlSeconds = 60): array {
    $cacheKey = 'ai_runtime_status';
    $now = time();
    if (isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey])) {
        $cached = $_SESSION[$cacheKey];
        if (($now - (int)($cached['checked_at'] ?? 0)) < $ttlSeconds) {
            return $cached;
        }
    }

    $provider = get_default_ai_provider();
    $baseUrl = ai_provider_base_url($provider);
    $models = [];
    $ok = false;
    $message = 'Provider IA non raggiungibile';
    try {
        $models = ai_provider_models($provider);
        $ok = count($models) > 0;
        $message = $ok
            ? 'IA attiva: ' . (string)($provider['nome'] ?? 'Provider') . ' Â· ' . count($models) . ' modelli'
            : 'IA configurata ma nessun modello disponibile';
    } catch (Throwable $e) {
        $message = 'Errore verifica IA';
    }

    $status = [
        'ok' => $ok,
        'tone' => $ok ? 'success' : 'danger',
        'label' => $ok ? 'IA attiva' : 'IA non attiva',
        'message' => $message,
        'provider' => (string)($provider['nome'] ?? ''),
        'base_url' => $baseUrl,
        'models_count' => count($models),
        'checked_at' => $now,
    ];
    $_SESSION[$cacheKey] = $status;
    return $status;
}

function save_ai_provider(array $data): array {
    $id = (int)($data['id'] ?? 0);
    $nome = trim((string)($data['nome'] ?? ''));
    $providerType = (string)($data['provider_type'] ?? 'ollama');
    $baseUrl = rtrim(trim((string)($data['base_url'] ?? '')), '/');
    $apiKey = (string)($data['api_key'] ?? '');
    $defaultModel = trim((string)($data['default_model'] ?? ''));
    $modelList = trim((string)($data['model_list'] ?? ''));
    $timeoutSeconds = max(30, min(1800, (int)($data['timeout_seconds'] ?? 300)));
    $enabled = isset($data['enabled']) ? 1 : 0;
    $isDefault = isset($data['is_default']) ? 1 : 0;

    if ($nome === '' || $baseUrl === '') {
        return ['ok' => false, 'message' => 'Nome provider e Base URL sono obbligatori.'];
    }
    if (!array_key_exists($providerType, ai_provider_types())) {
        return ['ok' => false, 'message' => 'Tipo provider IA non valido.'];
    }
    if (!preg_match('/^https?:\/\//i', $baseUrl)) {
        return ['ok' => false, 'message' => 'La Base URL deve iniziare con http:// o https://.'];
    }

    $db = get_db();
    $db->beginTransaction();
    try {
        if ($isDefault === 1) {
            $db->exec("UPDATE ai_providers SET is_default = 0");
        }
        if ($id > 0) {
            $current = get_ai_provider($id);
            if (!$current) {
                throw new RuntimeException('Provider IA non trovato.');
            }
            if ($apiKey === '') {
                $apiKey = (string)($current['api_key'] ?? '');
            }
            $db->prepare(
                "UPDATE ai_providers
                 SET nome=?, provider_type=?, base_url=?, api_key=?, default_model=?, model_list=?, timeout_seconds=?, enabled=?, is_default=?
                 WHERE id=?"
            )->execute([$nome, $providerType, $baseUrl, $apiKey, $defaultModel, $modelList, $timeoutSeconds, $enabled, $isDefault, $id]);
        } else {
            $db->prepare(
                "INSERT INTO ai_providers (nome, provider_type, base_url, api_key, default_model, model_list, timeout_seconds, enabled, is_default)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([$nome, $providerType, $baseUrl, $apiKey, $defaultModel, $modelList, $timeoutSeconds, $enabled, $isDefault]);
        }
        if ($isDefault === 0 && (int)$db->query("SELECT COUNT(*) FROM ai_providers WHERE enabled = 1 AND is_default = 1")->fetchColumn() === 0) {
            $db->exec("UPDATE ai_providers SET is_default = 1 WHERE enabled = 1 ORDER BY id LIMIT 1");
        }
        $db->commit();
        return ['ok' => true, 'message' => 'Provider IA salvato.'];
    } catch (Throwable $e) {
        $db->rollBack();
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function delete_ai_provider(int $id): array {
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Provider IA non valido.'];
    }
    get_db()->prepare("UPDATE ai_providers SET enabled = 0, is_default = 0 WHERE id = ?")->execute([$id]);
    return ['ok' => true, 'message' => 'Provider IA disabilitato.'];
}

function test_ai_provider(array $provider): array {
    $type = (string)($provider['provider_type'] ?? 'ollama');
    if ($type === 'ollama') {
        $models = ollama_models($provider);
        if (!$models) {
            return ['ok' => false, 'message' => 'Connessione Ollama raggiunta ma nessun modello letto, oppure endpoint non disponibile.'];
        }
        $probeModel = trim((string)($provider['default_model'] ?? '')) ?: (string)$models[0];
        $probe = ollama_generation_probe($provider, $probeModel);
        if (!$probe['ok']) {
            return [
                'ok' => false,
                'message' => 'Ollama raggiungibile e modelli letti, ma generazione non riuscita con ' . $probeModel . ': ' . $probe['message'],
            ];
        }
        return [
            'ok' => true,
            'message' => 'Ollama raggiungibile. Generazione breve OK con ' . $probeModel . ' in ' . $probe['seconds'] . 's. Modelli: ' . implode(', ', $models),
        ];
    }

    $url = ai_provider_base_url($provider) . '/models';
    $headers = "Accept: application/json\r\n";
    $apiKey = trim((string)($provider['api_key'] ?? ''));
    if ($apiKey !== '') {
        $headers .= "Authorization: Bearer $apiKey\r\n";
    }
    $options = ['http' => ['method' => 'GET', 'header' => $headers, 'timeout' => 15, 'ignore_errors' => true]];
    $body = @file_get_contents($url, false, stream_context_create($options));
    if ($body === false) {
        return ['ok' => false, 'message' => "Endpoint OpenAI-compatible non raggiungibile verso $url."];
    }
    return ['ok' => true, 'message' => 'Endpoint OpenAI-compatible raggiungibile.'];
}

function ollama_generation_probe(array $provider, string $model): array {
    $url = ai_provider_base_url($provider) . '/api/generate';
    $payload = [
        'model' => $model,
        'prompt' => 'Rispondi solo con OK.',
        'stream' => false,
        'options' => [
            'temperature' => 0,
            'num_predict' => 1,
            'num_ctx' => 1024,
        ],
    ];
    $started = microtime(true);
    $body = @file_get_contents($url, false, stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content' => requisito_version_json($payload),
        'timeout' => 45,
        'ignore_errors' => true,
    ]]));
    if ($body === false) {
        return ['ok' => false, 'message' => "nessuna risposta da $url", 'seconds' => round(microtime(true) - $started, 2)];
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'message' => 'risposta non JSON', 'seconds' => round(microtime(true) - $started, 2)];
    }
    if (!empty($json['error'])) {
        return ['ok' => false, 'message' => (string)$json['error'], 'seconds' => round(microtime(true) - $started, 2)];
    }
    return ['ok' => true, 'message' => (string)($json['response'] ?? ''), 'seconds' => round(microtime(true) - $started, 2)];
}

function ollama_base_url(): string {
    return OLLAMA_BASE_URL ?: 'http://host.docker.internal:11434';
}

function ollama_http_request(string $method, string $path, ?array $payload = null, int $timeout = 30, ?array $provider = null): array {
    $url = ai_provider_base_url($provider ?: ai_provider_default_row()) . $path;
    $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
    $options = [
        'http' => [
            'method' => $method,
            'header' => $headers,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ];
    if ($payload !== null) {
        $options['http']['content'] = requisito_version_json($payload);
    }
    $body = @file_get_contents($url, false, stream_context_create($options));
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $matches);
    $status = (int)($matches[1] ?? 0);
    if ($body === false) {
        return ['ok' => false, 'status' => $status, 'error' => "Connessione a Ollama non riuscita verso $url"];
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'status' => $status, 'error' => 'Risposta Ollama non JSON.', 'raw' => $body];
    }
    if ($status >= 400 || $status === 0) {
        return ['ok' => false, 'status' => $status, 'error' => (string)($json['error'] ?? 'Errore Ollama'), 'raw' => $body];
    }
    return ['ok' => true, 'status' => $status, 'json' => $json];
}

function ollama_models(?array $provider = null): array {
    $response = ollama_http_request('GET', '/api/tags', null, 10, $provider);
    if (!$response['ok']) {
        return [];
    }
    $models = [];
    foreach (($response['json']['models'] ?? []) as $model) {
        if (!empty($model['name'])) {
            $models[] = (string)$model['name'];
        }
    }
    sort($models);
    return $models;
}

function ollama_generate(string $model, string $prompt, ?array $provider = null): array {
    $provider = $provider ?: get_default_ai_provider();
    $model = trim($model) ?: (string)($provider['default_model'] ?? OLLAMA_MODEL);
    if ($model === '') {
        $availableModels = ollama_models($provider);
        $model = (string)($availableModels[0] ?? '');
    }
    if ($model === '') {
        return ['ok' => false, 'error' => 'Seleziona o inserisci un modello Ollama.'];
    }
    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.2,
            'num_ctx' => 16384,
            'num_predict' => 2500,
        ]
    ];
    $response = ollama_http_request('POST', '/api/generate', $payload, max(30, (int)($provider['timeout_seconds'] ?? OLLAMA_TIMEOUT_SECONDS)), $provider);
    if (!$response['ok']) {
        return ['ok' => false, 'error' => $response['error'] ?? 'Errore Ollama.'];
    }
    return ['ok' => true, 'response' => (string)($response['json']['response'] ?? ''), 'model' => $model];
}

function ollama_generate_stream(string $model, string $prompt, callable $onChunk, ?array $provider = null): array {
    $provider = $provider ?: get_default_ai_provider();
    $model = trim($model) ?: (string)($provider['default_model'] ?? OLLAMA_MODEL);
    if ($model === '') {
        $availableModels = ollama_models($provider);
        $model = (string)($availableModels[0] ?? '');
    }
    if ($model === '') {
        return ['ok' => false, 'error' => 'Seleziona o inserisci un modello Ollama.', 'response' => '', 'model' => $model];
    }
    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => true,
        'options' => [
            'temperature' => 0.2,
            'num_ctx' => 16384,
            'num_predict' => 2500,
        ]
    ];
    $url = ai_provider_base_url($provider) . '/api/generate';
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/x-ndjson\r\n",
            'content' => requisito_version_json($payload),
            'timeout' => max(30, (int)($provider['timeout_seconds'] ?? OLLAMA_TIMEOUT_SECONDS)),
            'ignore_errors' => true,
        ],
    ];
    $handle = @fopen($url, 'r', false, stream_context_create($options));
    if (!$handle) {
        return ['ok' => false, 'error' => "Connessione a Ollama non riuscita verso $url", 'response' => '', 'model' => $model];
    }
    stream_set_timeout($handle, max(30, (int)($provider['timeout_seconds'] ?? OLLAMA_TIMEOUT_SECONDS)));
    $fullResponse = '';
    $error = '';
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line === false) {
            $meta = stream_get_meta_data($handle);
            if (!empty($meta['timed_out'])) {
                $error = 'Timeout durante la risposta di Ollama.';
                break;
            }
            continue;
        }
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $json = json_decode($line, true);
        if (!is_array($json)) {
            continue;
        }
        if (!empty($json['error'])) {
            $error = (string)$json['error'];
            break;
        }
        $chunk = (string)($json['response'] ?? '');
        if ($chunk !== '') {
            $fullResponse .= $chunk;
            $onChunk($chunk);
        }
        if (!empty($json['done'])) {
            break;
        }
    }
    fclose($handle);
    if ($error !== '') {
        return ['ok' => false, 'error' => $error, 'response' => $fullResponse, 'model' => $model];
    }
    return ['ok' => true, 'response' => $fullResponse, 'model' => $model];
}

function openai_compatible_generate(string $model, string $prompt, array $provider): array {
    $model = trim($model) ?: (string)($provider['default_model'] ?? '');
    if ($model === '') {
        return ['ok' => false, 'error' => 'Seleziona o inserisci un modello IA.'];
    }
    $url = ai_provider_base_url($provider) . '/chat/completions';
    $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
    $apiKey = trim((string)($provider['api_key'] ?? ''));
    if ($apiKey !== '') {
        $headers .= "Authorization: Bearer $apiKey\r\n";
    }
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.2,
        'stream' => false,
    ];
    $options = ['http' => [
        'method' => 'POST',
        'header' => $headers,
        'content' => requisito_version_json($payload),
        'timeout' => max(30, (int)($provider['timeout_seconds'] ?? 300)),
        'ignore_errors' => true,
    ]];
    $body = @file_get_contents($url, false, stream_context_create($options));
    if ($body === false) {
        return ['ok' => false, 'error' => "Connessione IA non riuscita verso $url."];
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'error' => 'Risposta IA non JSON.'];
    }
    if (!empty($json['error'])) {
        $message = is_array($json['error']) ? (string)($json['error']['message'] ?? 'Errore IA') : (string)$json['error'];
        return ['ok' => false, 'error' => $message];
    }
    return ['ok' => true, 'response' => (string)($json['choices'][0]['message']['content'] ?? ''), 'model' => $model];
}

function openai_compatible_generate_stream(string $model, string $prompt, callable $onChunk, array $provider): array {
    $model = trim($model) ?: (string)($provider['default_model'] ?? '');
    if ($model === '') {
        return ['ok' => false, 'error' => 'Seleziona o inserisci un modello IA.', 'response' => '', 'model' => $model];
    }
    $url = ai_provider_base_url($provider) . '/chat/completions';
    $headers = "Content-Type: application/json\r\nAccept: text/event-stream\r\n";
    $apiKey = trim((string)($provider['api_key'] ?? ''));
    if ($apiKey !== '') {
        $headers .= "Authorization: Bearer $apiKey\r\n";
    }
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.2,
        'stream' => true,
    ];
    $handle = @fopen($url, 'r', false, stream_context_create(['http' => [
        'method' => 'POST',
        'header' => $headers,
        'content' => requisito_version_json($payload),
        'timeout' => max(30, (int)($provider['timeout_seconds'] ?? 300)),
        'ignore_errors' => true,
    ]]));
    if (!$handle) {
        return ['ok' => false, 'error' => "Connessione IA non riuscita verso $url.", 'response' => '', 'model' => $model];
    }
    stream_set_timeout($handle, max(30, (int)($provider['timeout_seconds'] ?? 300)));
    $fullResponse = '';
    $error = '';
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line === false) {
            $meta = stream_get_meta_data($handle);
            if (!empty($meta['timed_out'])) {
                $error = 'Timeout durante la risposta IA.';
                break;
            }
            continue;
        }
        $line = trim($line);
        if ($line === '' || !str_starts_with($line, 'data:')) {
            continue;
        }
        $data = trim(substr($line, 5));
        if ($data === '[DONE]') {
            break;
        }
        $json = json_decode($data, true);
        if (!is_array($json)) {
            continue;
        }
        if (!empty($json['error'])) {
            $error = is_array($json['error']) ? (string)($json['error']['message'] ?? 'Errore IA') : (string)$json['error'];
            break;
        }
        $chunk = (string)($json['choices'][0]['delta']['content'] ?? '');
        if ($chunk !== '') {
            $fullResponse .= $chunk;
            $onChunk($chunk);
        }
    }
    fclose($handle);
    if ($error !== '') {
        return ['ok' => false, 'error' => $error, 'response' => $fullResponse, 'model' => $model];
    }
    return ['ok' => true, 'response' => $fullResponse, 'model' => $model];
}

function ai_generate(array $provider, string $model, string $prompt): array {
    if (($provider['provider_type'] ?? 'ollama') === 'openai_compatible') {
        return openai_compatible_generate($model, $prompt, $provider);
    }
    return ollama_generate($model, $prompt, $provider);
}

function ai_generate_stream(array $provider, string $model, string $prompt, callable $onChunk): array {
    if (($provider['provider_type'] ?? 'ollama') === 'openai_compatible') {
        return openai_compatible_generate_stream($model, $prompt, $onChunk, $provider);
    }
    return ollama_generate_stream($model, $prompt, $onChunk, $provider);
}

function ai_analysis_types(): array {
    return [
        'specific_requirements' => [
            'label' => 'Suggerimento requisiti specifici',
            'description' => 'Propone requisiti specifici di progetto non giÃ  presenti a catalogo.',
            'suggestion_type' => 'specific_requirement',
        ],
        'false_positives' => [
            'label' => 'Rilevazione falsi positivi',
            'description' => 'Individua requisiti assegnati che potrebbero non essere realmente applicabili.',
            'suggestion_type' => 'false_positive',
        ],
        'result_explanations' => [
            'label' => 'Spiegazione risultati',
            'description' => 'Spiega perchÃ© i requisiti risultano assegnati e quali risposte li giustificano.',
            'suggestion_type' => 'explanation',
        ],
        'question_quality' => [
            'label' => 'QualitÃ  questionario',
            'description' => 'Segnala risposte incoerenti, mancanti, ambigue o da approfondire.',
            'suggestion_type' => 'quality_issue',
        ],
        'service_mapping' => [
            'label' => 'Mapping servizi',
            'description' => 'Valuta servizi associati e possibili servizi mancanti.',
            'suggestion_type' => 'service_mapping',
        ],
        'pir_support' => [
            'label' => 'Supporto PIR',
            'description' => 'Analizza lo stato PIR e suggerisce rischi residui, evidenze mancanti e rientri.',
            'suggestion_type' => 'pir_support',
        ],
        'executive_report' => [
            'label' => 'Report executive',
            'description' => 'Produce una sintesi manageriale dei rischi, decisioni e prossimi passi.',
            'suggestion_type' => 'executive_summary',
        ],
        'normalization' => [
            'label' => 'Normalizzazione requisiti specifici',
            'description' => 'Individua duplicati o requisiti specifici simili giÃ  presenti.',
            'suggestion_type' => 'normalization',
        ],
    ];
}

function ai_analysis_type(string $type): array {
    $types = ai_analysis_types();
    return $types[$type] ?? $types['specific_requirements'];
}

function latest_threat_analysis_for_questionario(int $questionarioId): array|false {
    $st = get_db()->prepare(
        "SELECT * FROM threat_analyses
         WHERE questionario_id = ? AND status = 'ok'
         ORDER BY created_at DESC, id DESC
         LIMIT 1"
    );
    $st->execute([$questionarioId]);
    return $st->fetch();
}

function build_ai_assistant_context(int $questionarioId, string $type): array {
    $context = build_questionario_ai_context($questionarioId);
    $context['analysis_type'] = $type;
    $context['threat_analysis_latest'] = compact_ai_row(latest_threat_analysis_for_questionario($questionarioId) ?: [], ['id', 'model_name', 'response_text', 'created_at'], 3500);

    if (in_array($type, ['pir_support', 'executive_report'], true)) {
        $pirRequirements = [];
        $reviews = get_pir_reviews_map($questionarioId);
        foreach (pir_project_requirements($questionarioId) as $req) {
            $key = $req['pir_tipo'] . ':' . $req['pir_ref_id'];
            $review = $reviews[$key] ?? [];
            $pirRequirements[] = [
                'tipo' => $req['pir_tipo_label'] ?? $req['pir_tipo'],
                'codice' => $req['codice'] ?? '',
                'titolo' => $req['titolo'] ?? '',
                'categoria' => $req['categoria'] ?? '',
                'stato_pir' => $review['stato'] ?? 'da_valutare',
                'note' => compact_ai_value($review['note'] ?? '', 300),
                'applicazione' => compact_ai_value($review['applicazione'] ?? '', 300),
                'rientro_eccezione' => compact_ai_value($review['rientro_eccezione'] ?? '', 300),
            ];
        }
        $meetings = [];
        foreach (get_pir_meetings($questionarioId) as $meeting) {
            $meetings[] = compact_ai_row($meeting, ['data_riunione', 'note'], 600);
        }
        $context['pir'] = [
            'stato' => get_questionario($questionarioId)['pir_stato'] ?? '',
            'requisiti' => $pirRequirements,
            'riunioni' => $meetings,
        ];
    }

    if ($type === 'executive_report') {
        $context['executive_context_note'] = 'Contesto compattato per sintesi manageriale: dettagli tecnici completi restano nelle viste requisiti, PIR e Threat Analysis.';
        $context['risposte'] = array_slice($context['risposte'] ?? [], 0, 60);
        $context['requisiti_catalogo_applicabili'] = array_slice(array_map(
            fn($row) => compact_ai_row($row, ['codice', 'titolo', 'categoria', 'importanza', 'owner'], 150),
            $context['requisiti_catalogo_applicabili'] ?? []
        ), 0, 45);
        $context['requisiti_standard'] = array_slice($context['requisiti_standard'] ?? [], 0, 30);
        $context['requisiti_specifici_progetto'] = array_slice(array_map(
            fn($row) => compact_ai_row($row, ['codice', 'titolo', 'categoria', 'importanza', 'owner', 'task_jira'], 150),
            $context['requisiti_specifici_progetto'] ?? []
        ), 0, 35);
        $context['servizi_applicabili'] = array_slice(array_map(
            fn($row) => compact_ai_row($row, ['reparto_owner', 'macro_service', 'servizio_elementare', 'tipo_attivita'], 150),
            $context['servizi_applicabili'] ?? []
        ), 0, 30);
        if (!empty($context['threat_analysis_latest']['response_text'])) {
            $context['threat_analysis_latest']['response_text'] = compact_ai_value($context['threat_analysis_latest']['response_text'], 1800);
        }
        if (!empty($context['pir']['requisiti']) && is_array($context['pir']['requisiti'])) {
            $koOrOpen = array_values(array_filter($context['pir']['requisiti'], fn($row) => !in_array((string)($row['stato_pir'] ?? ''), ['OK', 'non_applicabile'], true)));
            $context['pir']['requisiti'] = array_slice($koOrOpen ?: $context['pir']['requisiti'], 0, 50);
        }
    }

    if ($type === 'normalization') {
        $existing = [];
        foreach (get_all_requisiti_specifici(true) as $specifico) {
            $existing[] = compact_ai_row($specifico, ['id', 'codice', 'titolo', 'descrizione', 'categoria', 'questionari_collegati'], 260);
        }
        $context['requisiti_specifici_esistenti'] = array_slice($existing, 0, 120);
    }

    return $context;
}

function ai_assistant_prompt(string $type, array $context): string {
    $meta = ai_analysis_type($type);
    $instructions = [
        'specific_requirements' => 'Proponi solo requisiti specifici realmente mancanti, non duplicati rispetto al catalogo. Ogni proposta deve essere attuabile e verificabile.',
        'false_positives' => 'Individua requisiti catalogo o standard che sembrano falsi positivi. Non proporre esclusioni se la motivazione Ã¨ debole.',
        'result_explanations' => 'Spiega in modo sintetico e tracciabile i requisiti giÃƒÂ  assegnati, collegandoli a risposte, contesto o Threat Analysis. Non proporre nuovi requisiti e non cambiare il perimetro.',
        'question_quality' => 'Trova risposte mancanti, incoerenti, vaghe o rischiose e proponi domande di chiarimento.',
        'service_mapping' => 'Valuta i servizi associati e suggerisci servizi mancanti o associazioni dubbie, motivando con risposte e requisiti.',
        'pir_support' => 'Analizza la PIR, evidenzia KO/parziali, note insufficienti, rischi residui, evidenze mancanti e possibili rientri/eccezioni.',
        'executive_report' => 'Genera una sintesi executive chiara per manager: rischi principali, decisioni richieste, impatti e prossimi passi.',
        'normalization' => 'Individua requisiti specifici simili o duplicati, suggerendo riuso, merge o mantenimento separato.',
    ];
    $suggestionType = $meta['suggestion_type'];
    if ($type === 'executive_report') {
        return "Sei un assistente IA per Security by Design. Tipo analisi: {$meta['label']}.\n"
            . "Devi produrre un report executive per manager in italiano, concreto e leggibile.\n"
            . "Rispondi SOLO con JSON valido, senza Markdown fuori dal JSON e senza blocchi ```.\n"
            . "Non iniziare mai la risposta con ###. Il campo body deve contenere testo semplice strutturato con righe e punti elenco testuali.\n\n"
            . "Struttura obbligatoria:\n"
            . "{\n"
            . "  \"summary\": \"sintesi breve del report executive\",\n"
            . "  \"suggestions\": [\n"
            . "    {\n"
            . "      \"type\": \"executive_summary\",\n"
            . "      \"title\": \"Report executive\",\n"
            . "      \"body\": \"Executive summary completo con: contesto, rischi principali, requisiti critici, servizi coinvolti, decisioni richieste, prossimi passi\",\n"
            . "      \"priority\": \"INFO\",\n"
            . "      \"confidence\": 0.8,\n"
            . "      \"rationale\": \"fonti usate dal questionario/PIR/Threat Analysis\",\n"
            . "      \"payload\": {\n"
            . "        \"rischi_principali\": [],\n"
            . "        \"decisioni_richieste\": [],\n"
            . "        \"prossimi_passi\": []\n"
            . "      }\n"
            . "    }\n"
            . "  ]\n"
            . "}\n\n"
            . "--- CONTESTO JSON ---\n"
            . requisito_version_json($context);
    }
    return "Sei un assistente IA per Security by Design. Tipo analisi: {$meta['label']}.\n"
        . ($instructions[$type] ?? '') . "\n\n"
        . "Rispondi SOLO con JSON valido, senza Markdown, con questa struttura:\n"
        . "{\n"
        . "  \"summary\": \"sintesi breve dell'analisi\",\n"
        . "  \"suggestions\": [\n"
        . "    {\n"
        . "      \"type\": \"$suggestionType\",\n"
        . "      \"title\": \"titolo sintetico\",\n"
        . "      \"body\": \"descrizione operativa\",\n"
        . "      \"priority\": \"MUST|SHOULD|MAY|INFO\",\n"
        . "      \"confidence\": 0.0,\n"
        . "      \"rationale\": \"motivazione tracciabile\",\n"
        . "      \"payload\": {}\n"
        . "    }\n"
        . "  ]\n"
        . "}\n\n"
        . "Regole payload per type specific_requirement: titolo, descrizione, categoria, importanza, owner, fase, note.\n"
        . "Regole payload per type false_positive: requisito_id se disponibile, codice, motivo_esclusione.\n"
        . "Regole payload per type explanation: codice, requisito_id se disponibile, spiegazione, evidenze. Usa type=explanation per ogni spiegazione.\n"
        . "Regole payload per type quality_issue: codice_domanda, problema, chiarimento_suggerito.\n"
        . "Regole payload per type service_mapping: servizio, azione, motivazione.\n"
        . "Regole payload per type pir_support: codice_requisito, stato_suggerito, evidenza_mancante, rischio_residuo.\n"
        . "Regole payload per type executive_summary: testo, rischi_principali, decisioni_richieste, prossimi_passi.\n"
        . "Regole payload per type normalization: requisito_specifico_id, possibile_duplicato_id, azione, motivazione.\n\n"
        . "--- CONTESTO JSON ---\n"
        . requisito_version_json($context);
}

function ai_extract_json(string $text): array {
    $trimmed = trim($text);
    if (str_starts_with($trimmed, '```')) {
        $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
        $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
    }
    $json = json_decode($trimmed, true);
    if (is_array($json)) {
        return $json;
    }
    $start = strpos($trimmed, '{');
    $end = strrpos($trimmed, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $candidate = substr($trimmed, $start, $end - $start + 1);
        $json = json_decode($candidate, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return [
        'summary' => 'Risposta IA non strutturata.',
        'suggestions' => [[
            'type' => 'note',
            'title' => 'Risposta non strutturata',
            'body' => $text,
            'priority' => 'INFO',
            'confidence' => null,
            'rationale' => 'La IA non ha restituito JSON valido.',
            'payload' => ['raw' => $text],
        ]],
    ];
}

function save_ai_analysis_run(int $questionarioId, ?int $providerId, string $analysisType, string $model, string $prompt, array $context, string $response, array $parsed, string $status, string $error, int $durationMs): int {
    $user = current_user();
    $db = get_db();
    $db->prepare(
        "INSERT INTO ai_analysis_runs
         (questionario_id, provider_id, created_by_user_id, analysis_type, model_name, prompt_text, context_json, response_text, parsed_json, status, error_message, duration_ms)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $questionarioId,
        $providerId ?: null,
        $user ? (int)$user['id'] : null,
        $analysisType,
        $model,
        $prompt,
        requisito_version_json($context),
        $response,
        requisito_version_json($parsed),
        $status === 'error' ? 'error' : 'ok',
        $error,
        $durationMs,
    ]);
    $runId = (int)$db->lastInsertId();
    if ($status !== 'error') {
        save_ai_suggestions_from_parsed($runId, $questionarioId, $analysisType, $parsed);
    }
    return $runId;
}

function save_ai_suggestions_from_parsed(int $runId, int $questionarioId, string $analysisType, array $parsed): void {
    $suggestions = is_array($parsed['suggestions'] ?? null) ? $parsed['suggestions'] : [];
    $defaultType = ai_analysis_type($analysisType)['suggestion_type'] ?? $analysisType;
    $st = get_db()->prepare(
        "INSERT INTO ai_suggestions
         (run_id, questionario_id, suggestion_type, title, body, priority, confidence, rationale, payload_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach (array_slice($suggestions, 0, 30) as $suggestion) {
        if (!is_array($suggestion)) {
            continue;
        }
        $payload = is_array($suggestion['payload'] ?? null) ? $suggestion['payload'] : [];
        $st->execute([
            $runId,
            $questionarioId,
            trim((string)($suggestion['type'] ?? $defaultType)) ?: $defaultType,
            short_text(trim((string)($suggestion['title'] ?? 'Suggerimento IA')), 500),
            trim((string)($suggestion['body'] ?? '')),
            short_text(trim((string)($suggestion['priority'] ?? 'INFO')), 30),
            is_numeric($suggestion['confidence'] ?? null) ? (float)$suggestion['confidence'] : null,
            trim((string)($suggestion['rationale'] ?? '')),
            requisito_version_json($payload),
        ]);
    }
}

function run_ai_analysis(int $questionarioId, string $analysisType, int $providerId = 0, string $model = ''): array {
    $questionario = get_questionario($questionarioId);
    if (!$questionario) {
        return ['ok' => false, 'message' => 'Questionario non trovato.'];
    }
    if (!array_key_exists($analysisType, ai_analysis_types())) {
        return ['ok' => false, 'message' => 'Tipo analisi IA non valido.'];
    }
    $provider = get_ai_provider($providerId) ?: get_default_ai_provider();
    $context = build_ai_assistant_context($questionarioId, $analysisType);
    $prompt = ai_assistant_prompt($analysisType, $context);
    $started = microtime(true);
    $result = ai_generate($provider, $model, $prompt);
    $durationMs = (int)round((microtime(true) - $started) * 1000);
    $response = (string)($result['response'] ?? '');
    $parsed = $result['ok'] ? ai_extract_json($response) : ['summary' => '', 'suggestions' => []];
    $runId = save_ai_analysis_run(
        $questionarioId,
        (int)($provider['id'] ?? 0),
        $analysisType,
        (string)($result['model'] ?? $model),
        $prompt,
        $context,
        $response,
        $parsed,
        $result['ok'] ? 'ok' : 'error',
        (string)($result['error'] ?? ''),
        $durationMs
    );
    if (!$result['ok']) {
        return ['ok' => false, 'message' => 'Errore IA: ' . (string)($result['error'] ?? 'errore non specificato'), 'run_id' => $runId];
    }
    return ['ok' => true, 'message' => 'Analisi IA completata in ' . round($durationMs / 1000, 1) . 's.', 'run_id' => $runId];
}

function get_ai_runs(int $questionarioId = 0, string $type = ''): array {
    $sql = "SELECT r.*, q.nome_progetto, q.task_jira, CONCAT_WS(' ', u.nome, u.cognome) AS creato_da, p.nome AS provider_nome
            FROM ai_analysis_runs r
            JOIN questionari q ON q.id = r.questionario_id
            LEFT JOIN utenti u ON u.id = r.created_by_user_id
            LEFT JOIN ai_providers p ON p.id = r.provider_id";
    $where = [];
    $params = [];
    if ($questionarioId > 0) {
        $where[] = "r.questionario_id = ?";
        $params[] = $questionarioId;
    }
    if ($type !== '') {
        $where[] = "r.analysis_type = ?";
        $params[] = $type;
    }
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY r.created_at DESC, r.id DESC LIMIT 100";
    $st = get_db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function get_ai_run(int $runId): array|false {
    $st = get_db()->prepare("SELECT * FROM ai_analysis_runs WHERE id = ?");
    $st->execute([$runId]);
    return $st->fetch();
}

function get_ai_suggestions(int $questionarioId = 0, int $runId = 0): array {
    $sql = "SELECT s.*, r.analysis_type, r.created_at AS run_created_at
            FROM ai_suggestions s
            JOIN ai_analysis_runs r ON r.id = s.run_id";
    $where = [];
    $params = [];
    if ($questionarioId > 0) {
        $where[] = "s.questionario_id = ?";
        $params[] = $questionarioId;
    }
    if ($runId > 0) {
        $where[] = "s.run_id = ?";
        $params[] = $runId;
    }
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY FIELD(s.status,'proposto','approvato','applicato','scartato'), s.created_at DESC, s.id DESC";
    $st = get_db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function get_ai_suggestion(int $suggestionId): array|false {
    $st = get_db()->prepare("SELECT * FROM ai_suggestions WHERE id = ?");
    $st->execute([$suggestionId]);
    return $st->fetch();
}

function get_ai_suggestions_by_type(int $questionarioId, string $suggestionType, array $statuses = []): array {
    $sql = "SELECT s.*, r.analysis_type, r.created_at AS run_created_at
            FROM ai_suggestions s
            JOIN ai_analysis_runs r ON r.id = s.run_id
            WHERE s.questionario_id = ? AND s.suggestion_type = ?";
    $params = [$questionarioId, $suggestionType];
    if ($statuses) {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql .= " AND s.status IN ($placeholders)";
        foreach ($statuses as $status) {
            $params[] = (string)$status;
        }
    }
    $sql .= " ORDER BY FIELD(s.status,'applicato','approvato','proposto','scartato'), s.created_at DESC, s.id DESC";
    $st = get_db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function ai_mark_suggestion(int $suggestionId, string $status, string $note = ''): array {
    $allowed = ['approvato', 'scartato', 'applicato'];
    if (!in_array($status, $allowed, true)) {
        return ['ok' => false, 'message' => 'Stato suggerimento non valido.'];
    }
    $user = current_user();
    get_db()->prepare(
        "UPDATE ai_suggestions
         SET status = ?, decision_note = ?, decided_by_user_id = ?, decided_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    )->execute([$status, $note, $user ? (int)$user['id'] : null, $suggestionId]);
    return ['ok' => true, 'message' => 'Suggerimento aggiornato.'];
}

function ai_apply_suggestion(int $suggestionId, string $note = ''): array {
    $suggestion = get_ai_suggestion($suggestionId);
    if (!$suggestion) {
        return ['ok' => false, 'message' => 'Suggerimento non trovato.'];
    }
    $payload = json_decode((string)$suggestion['payload_json'], true);
    $payload = is_array($payload) ? $payload : [];
    $questionarioId = (int)$suggestion['questionario_id'];
    $type = (string)$suggestion['suggestion_type'];

    if ($type === 'specific_requirement') {
        $questionario = get_questionario($questionarioId);
        $newId = save_requisito_specifico([
            'questionario_id' => $questionarioId,
            'task_jira' => (string)($questionario['task_jira'] ?? ''),
            'codice' => '',
            'versione' => '1.0',
            'categoria' => (string)($payload['categoria'] ?? ''),
            'titolo' => (string)($payload['titolo'] ?? $suggestion['title']),
            'descrizione' => (string)($payload['descrizione'] ?? $suggestion['body']),
            'contesto' => (string)($payload['contesto'] ?? 'Suggerito da analisi IA'),
            'note' => trim((string)($payload['note'] ?? '') . "\n" . (string)$suggestion['rationale']),
            'importanza' => (string)($payload['importanza'] ?? $suggestion['priority'] ?? 'SHOULD'),
            'owner' => (string)($payload['owner'] ?? 'Security'),
            'fase' => (string)($payload['fase'] ?? ''),
            'attivo' => 1,
        ]);
        get_db()->prepare("UPDATE questionario_requisiti_specifici SET codice = ? WHERE id = ? AND (codice IS NULL OR codice = '')")
            ->execute(['AI-SPEC-' . $newId, $newId]);
        ai_mark_suggestion($suggestionId, 'applicato', $note);
        return ['ok' => true, 'message' => 'Requisito specifico creato e collegato al questionario.'];
    }

    if ($type === 'false_positive') {
        $requisitoId = (int)($payload['requisito_id'] ?? 0);
        if ($requisitoId <= 0 && !empty($payload['codice'])) {
            $st = get_db()->prepare("SELECT id FROM requisiti WHERE codice = ?");
            $st->execute([(string)$payload['codice']]);
            $requisitoId = (int)($st->fetchColumn() ?: 0);
        }
        if ($requisitoId <= 0) {
            return ['ok' => false, 'message' => 'Impossibile applicare: requisito non identificato.'];
        }
        set_requisito_override($questionarioId, $requisitoId, 'exclude', (string)($payload['motivo_esclusione'] ?? $suggestion['rationale']));
        ai_mark_suggestion($suggestionId, 'applicato', $note);
        return ['ok' => true, 'message' => 'Requisito escluso come potenziale falso positivo.'];
    }

    if ($type === 'explanation') {
        ai_mark_suggestion($suggestionId, 'applicato', $note);
        return ['ok' => true, 'message' => 'Spiegazione pubblicata nella pagina risultati del questionario.'];
    }

    if ($type === 'executive_summary') {
        ai_mark_suggestion($suggestionId, 'applicato', $note);
        return ['ok' => true, 'message' => 'Report executive pubblicato nella pagina Suggerimenti IA.'];
    }

    return ai_mark_suggestion($suggestionId, 'approvato', $note);
}

function save_threat_analysis(int $questionarioId, string $model, string $baseUrl, string $prompt, array $context, string $responseText, string $status = 'ok', string $error = ''): int {
    $user = current_user();
    $db = get_db();
    $db->prepare(
        "INSERT INTO threat_analyses
         (questionario_id, created_by_user_id, model_name, ollama_base_url, user_prompt, request_context_json, response_text, status, error_message)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $questionarioId,
        $user ? (int)$user['id'] : null,
        $model,
        $baseUrl,
        $prompt,
        requisito_version_json($context),
        $responseText,
        $status === 'error' ? 'error' : 'ok',
        $error,
    ]);
    return (int)$db->lastInsertId();
}

function get_threat_analysis(int $id): array|false {
    $st = get_db()->prepare(
        "SELECT ta.*, q.nome_progetto, q.codice_progetto, CONCAT_WS(' ', u.nome, u.cognome) AS creato_da
         FROM threat_analyses ta
         JOIN questionari q ON q.id = ta.questionario_id
         LEFT JOIN utenti u ON u.id = ta.created_by_user_id
         WHERE ta.id = ?"
    );
    $st->execute([$id]);
    return $st->fetch();
}

function get_threat_analyses(int $questionarioId = 0): array {
    $sql = "SELECT ta.*, q.nome_progetto, q.codice_progetto, CONCAT_WS(' ', u.nome, u.cognome) AS creato_da
            FROM threat_analyses ta
            JOIN questionari q ON q.id = ta.questionario_id
            LEFT JOIN utenti u ON u.id = ta.created_by_user_id";
    $params = [];
    if ($questionarioId > 0) {
        $sql .= " WHERE ta.questionario_id = ?";
        $params[] = $questionarioId;
    }
    $sql .= " ORDER BY ta.created_at DESC, ta.id DESC LIMIT 100";
    $st = get_db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function threat_analysis_clean_title(string $title): string {
    $title = trim(preg_replace('/^[#*\s-]+|[#*\s-]+$/u', '', $title) ?? $title);
    $title = preg_replace('/^\d{1,2}\s*[.)-]\s*/u', '', $title) ?? $title;
    return trim($title);
}

function threat_analysis_strlen(string $text): int {
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

function threat_analysis_lower(string $text): string {
    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
}

function threat_analysis_upper(string $text): string {
    return function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text);
}

function threat_analysis_parse_sections(string $text): array {
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') {
        return [];
    }
    $sections = [];
    $current = null;
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
        $raw = rtrim($line);
        $number = '';
        $title = '';
        $isSectionHeading = false;
        if (preg_match('/^\s{0,3}(#{1,4})\s*(?:(\d{1,2})\s*[.)-]\s*)?(.+?)\s*#*\s*$/u', $raw, $m)) {
            $level = strlen((string)$m[1]);
            $number = (string)($m[2] ?? '');
            $title = threat_analysis_clean_title((string)$m[3]);
            if ($level === 1 && !$sections && $current === null && preg_match('/threat\s+analysis|analisi\s+operativa/iu', $title)) {
                continue;
            }
            $isSectionHeading = $level <= 2;
        } elseif (preg_match('/^\s*(?:\*\*)?(\d{1,2})\s*[.)-]\s+(.+?)(?:\*\*)?\s*$/u', $raw, $m)) {
            $candidateTitle = threat_analysis_clean_title((string)$m[2]);
            $looksLikeHeading = threat_analysis_strlen($candidateTitle) <= 160
                && (threat_analysis_upper($candidateTitle) === $candidateTitle || preg_match('/\b(requisiti|servizi|analisi|minacce|rischi|controlli|raccomandazioni|executive|sintesi)\b/iu', $candidateTitle));
            if ($looksLikeHeading) {
                $number = (string)$m[1];
                $title = $candidateTitle;
                $isSectionHeading = true;
            }
        }
        if ($title !== '' && $isSectionHeading) {
            if (!$sections && $current === null && preg_match('/threat\s+analysis|analisi\s+operativa/iu', $title)) {
                continue;
            }
            if ($current !== null) {
                $sections[] = $current;
            }
            $current = [
                'section_number' => $number,
                'title' => $title,
                'content_text' => '',
                'content_html' => '',
            ];
            continue;
        }
        if ($current === null && trim($raw) === '') {
            continue;
        }
        if ($current === null) {
            $current = [
                'section_number' => '',
                'title' => 'Analisi generata',
                'content_text' => '',
                'content_html' => '',
            ];
        }
        $current['content_text'] .= ($current['content_text'] === '' ? '' : "\n") . $raw;
    }
    if ($current !== null) {
        $sections[] = $current;
    }
    foreach ($sections as $index => &$section) {
        if (trim((string)$section['title']) === '') {
            $section['title'] = 'Punto ' . ($index + 1);
        }
        $section['content_text'] = trim((string)$section['content_text']);
        $section['content_html'] = threat_analysis_markdown_to_html((string)$section['content_text']);
    }
    unset($section);
    return $sections;
}

function threat_analysis_inline_markdown(string $text): string {
    $html = h($text);
    $html = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $html) ?? $html;
    $html = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/su', '<em>$1</em>', $html) ?? $html;
    $html = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $html) ?? $html;
    return $html;
}

function threat_analysis_is_table_separator(string $line): bool {
    $line = trim($line);
    if (!str_contains($line, '|')) {
        return false;
    }
    $line = trim($line, '| ');
    if ($line === '') {
        return false;
    }
    foreach (explode('|', $line) as $cell) {
        if (!preg_match('/^\s*:?-{3,}:?\s*$/', $cell)) {
            return false;
        }
    }
    return true;
}

function threat_analysis_split_table_row(string $line): array {
    $line = trim($line);
    $line = trim($line, '|');
    return array_map('trim', explode('|', $line));
}

function threat_analysis_markdown_tables(string $markdown): array {
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
    $tables = [];
    $count = count($lines);
    for ($i = 0; $i < $count - 1; $i++) {
        if (!str_contains($lines[$i], '|') || !threat_analysis_is_table_separator($lines[$i + 1])) {
            continue;
        }
        $headers = threat_analysis_split_table_row($lines[$i]);
        $rows = [];
        $i += 2;
        while ($i < $count && str_contains($lines[$i], '|') && trim($lines[$i]) !== '') {
            if (!threat_analysis_is_table_separator($lines[$i])) {
                $cells = threat_analysis_split_table_row($lines[$i]);
                if (array_filter($cells, fn($cell) => trim($cell) !== '')) {
                    $rows[] = $cells;
                }
            }
            $i++;
        }
        $tables[] = ['headers' => $headers, 'rows' => $rows];
    }
    return $tables;
}

function threat_analysis_markdown_to_html(string $markdown): string {
    $markdown = trim(str_replace(["\r\n", "\r"], "\n", $markdown));
    if ($markdown === '') {
        return '<p></p>';
    }
    $html = '';
    $listType = '';
    $lines = explode("\n", $markdown);
    $count = count($lines);
    for ($i = 0; $i < $count; $i++) {
        $line = $lines[$i];
        $trimmed = trim($line);
        if ($trimmed === '') {
            if ($listType !== '') {
                $html .= '</' . $listType . '>';
                $listType = '';
            }
            continue;
        }
        if ($i < $count - 1 && str_contains($line, '|') && threat_analysis_is_table_separator($lines[$i + 1])) {
            if ($listType !== '') {
                $html .= '</' . $listType . '>';
                $listType = '';
            }
            $headers = threat_analysis_split_table_row($line);
            $html .= '<div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . threat_analysis_inline_markdown($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            $i += 2;
            while ($i < $count && str_contains($lines[$i], '|') && trim($lines[$i]) !== '') {
                if (!threat_analysis_is_table_separator($lines[$i])) {
                    $cells = threat_analysis_split_table_row($lines[$i]);
                    $html .= '<tr>';
                    foreach ($headers as $cellIndex => $_header) {
                        $html .= '<td>' . threat_analysis_inline_markdown((string)($cells[$cellIndex] ?? '')) . '</td>';
                    }
                    $html .= '</tr>';
                }
                $i++;
            }
            $i--;
            $html .= '</tbody></table></div>';
            continue;
        }
        if (preg_match('/^\s*[-*]\s+(.+)$/u', $line, $m)) {
            if ($listType !== 'ul') {
                if ($listType !== '') {
                    $html .= '</' . $listType . '>';
                }
                $html .= '<ul>';
                $listType = 'ul';
            }
            $html .= '<li>' . threat_analysis_inline_markdown((string)$m[1]) . '</li>';
            continue;
        }
        if (preg_match('/^\s*\d+\.\s+(.+)$/u', $line, $m)) {
            if ($listType !== 'ol') {
                if ($listType !== '') {
                    $html .= '</' . $listType . '>';
                }
                $html .= '<ol>';
                $listType = 'ol';
            }
            $html .= '<li>' . threat_analysis_inline_markdown((string)$m[1]) . '</li>';
            continue;
        }
        if ($listType !== '') {
            $html .= '</' . $listType . '>';
            $listType = '';
        }
        if (preg_match('/^\s{0,3}#{3,5}\s+(.+)$/u', $line, $m)) {
            $html .= '<h4>' . threat_analysis_inline_markdown(threat_analysis_clean_title((string)$m[1])) . '</h4>';
            continue;
        }
        $html .= '<p>' . threat_analysis_inline_markdown($trimmed) . '</p>';
    }
    if ($listType !== '') {
        $html .= '</' . $listType . '>';
    }
    return $html;
}

function threat_analysis_sanitize_html(string $html): string {
    $html = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><code><pre><table><thead><tbody><tr><th><td><a>');
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
    $html = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/iu', 'href="#"', $html) ?? $html;
    return trim($html);
}

function threat_analysis_html_to_text(string $html): string {
    $html = preg_replace('/<\s*br\s*\/?>/iu', "\n", $html) ?? $html;
    $html = preg_replace('/<\s*\/(p|li|h2|h3|h4|tr)\s*>/iu', "\n", $html) ?? $html;
    return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function get_threat_analysis_sections(int $analysisId): array {
    $st = get_db()->prepare("SELECT * FROM threat_analysis_sections WHERE analysis_id = ? ORDER BY section_order, id");
    $st->execute([$analysisId]);
    return $st->fetchAll();
}

function ensure_threat_analysis_sections(int $analysisId): array {
    $sections = get_threat_analysis_sections($analysisId);
    if ($sections) {
        return $sections;
    }
    $analysis = get_threat_analysis($analysisId);
    if (!$analysis || (string)$analysis['status'] !== 'ok') {
        return [];
    }
    $parsed = threat_analysis_parse_sections((string)$analysis['response_text']);
    $db = get_db();
    $st = $db->prepare(
        "INSERT INTO threat_analysis_sections (analysis_id, section_order, section_number, title, content_html, content_text)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    foreach ($parsed as $index => $section) {
        $st->execute([
            $analysisId,
            $index + 1,
            (string)($section['section_number'] ?? ''),
            (string)$section['title'],
            threat_analysis_sanitize_html((string)$section['content_html']),
            (string)$section['content_text'],
        ]);
    }
    return get_threat_analysis_sections($analysisId);
}

function reparse_threat_analysis_sections(int $analysisId): array {
    $analysis = get_threat_analysis($analysisId);
    if (!$analysis || (string)$analysis['status'] !== 'ok') {
        return [];
    }
    get_db()->prepare("DELETE FROM threat_analysis_sections WHERE analysis_id = ?")->execute([$analysisId]);
    return ensure_threat_analysis_sections($analysisId);
}

function save_threat_analysis_sections(int $analysisId, array $sections): void {
    $db = get_db();
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM threat_analysis_sections WHERE analysis_id = ?")->execute([$analysisId]);
        $st = $db->prepare(
            "INSERT INTO threat_analysis_sections (analysis_id, section_order, section_number, title, content_html, content_text)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $order = 1;
        foreach ($sections as $section) {
            $title = trim((string)($section['title'] ?? ''));
            $html = threat_analysis_sanitize_html((string)($section['content_html'] ?? ''));
            if ($title === '' && $html === '') {
                continue;
            }
            $st->execute([
                $analysisId,
                $order++,
                trim((string)($section['section_number'] ?? '')),
                $title !== '' ? $title : ('Punto ' . $order),
                $html !== '' ? $html : '<p></p>',
                threat_analysis_html_to_text($html),
            ]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

function threat_analysis_section_kind(array $section): string {
    $number = trim((string)($section['section_number'] ?? ''));
    $title = threat_analysis_lower((string)($section['title'] ?? ''));
    if ($number === '4' || preg_match('/requisit/i', $title)) {
        return 'requirements';
    }
    if ($number === '5' || preg_match('/serviz/i', $title) || preg_match('/controlli?\s+coinvolt/i', $title)) {
        return 'services';
    }
    return 'generic';
}

function threat_analysis_is_table_noise_line(string $line): bool {
    $line = trim(strip_tags($line));
    if ($line === '' || threat_analysis_is_table_separator($line)) {
        return true;
    }
    if (!str_contains($line, '|')) {
        return false;
    }
    $cells = threat_analysis_split_table_row($line);
    $nonEmptyCells = array_values(array_filter($cells, fn($cell) => trim((string)$cell) !== ''));
    if (!$nonEmptyCells) {
        return true;
    }
    $headerWords = 0;
    foreach ($nonEmptyCells as $cell) {
        if (preg_match('/^(id|codice|servizio|controllo|requisito|categoria|gap|rischio|controllo compensativo|owner|referente|note|descrizione|azione|stato)$/iu', trim((string)$cell))) {
            $headerWords++;
        }
    }
    return $headerWords >= max(2, count($nonEmptyCells) - 1);
}

function threat_analysis_extract_bullets(string $text): array {
    $rows = [];
    foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $line) {
        $line = trim(strip_tags($line));
        if (threat_analysis_is_table_noise_line($line)) {
            continue;
        }
        $line = preg_replace('/^\s*[-*â€¢]\s*/u', '', $line) ?? $line;
        $line = preg_replace('/^\s*\d{1,2}\s*[.)]\s*/u', '', $line) ?? $line;
        $line = trim($line);
        if (threat_analysis_strlen($line) >= 12 && !preg_match('/^(requisiti|servizi|controlli)\b/iu', $line)) {
            $rows[] = $line;
        }
    }
    return array_values(array_unique($rows));
}

function threat_analysis_table_key(string $header): string {
    $header = threat_analysis_lower(strip_tags($header));
    $header = strtr($header, ['Ã ' => 'a', 'Ã¨' => 'e', 'Ã©' => 'e', 'Ã¬' => 'i', 'Ã²' => 'o', 'Ã¹' => 'u']);
    return preg_replace('/[^a-z0-9]+/', '_', trim($header)) ?? '';
}

function threat_analysis_table_value(array $row, array $keys, array $patterns): string {
    foreach ($patterns as $pattern) {
        foreach ($keys as $index => $key) {
            if (preg_match($pattern, $key)) {
                return trim((string)($row[$index] ?? ''));
            }
        }
    }
    return '';
}

function threat_analysis_requirement_candidates(array $section): array {
    $items = [];
    foreach (threat_analysis_markdown_tables((string)($section['content_text'] ?? '')) as $table) {
        $headers = $table['headers'] ?? [];
        $keys = array_map('threat_analysis_table_key', $headers);
        foreach (($table['rows'] ?? []) as $row) {
            $row = array_pad($row, count($headers), '');
            $title = threat_analysis_table_value($row, $keys, ['/requisit|titolo|controllo|misura|azione/u']);
            $description = threat_analysis_table_value($row, $keys, ['/dettaglio|descrizion|implementazion|evidenza/u']);
            $category = threat_analysis_table_value($row, $keys, ['/categoria|ambito|area|dominio/u']);
            $priority = threat_analysis_table_value($row, $keys, ['/prior|importanza|criticita|severity|must|should/u']);
            $owner = threat_analysis_table_value($row, $keys, ['/owner|responsabile|referente/u']);
            if ($title === '') {
                foreach ($row as $index => $cell) {
                    $key = $keys[$index] ?? '';
                    if (!preg_match('/^id|codice/u', $key) && trim((string)$cell) !== '') {
                        $title = trim((string)$cell);
                        break;
                    }
                }
            }
            if ($description === '') {
                $description = trim(implode(' | ', array_filter($row, fn($cell) => trim((string)$cell) !== '')));
            }
            if ($title !== '' && !preg_match('/^-+$/', $title)) {
                $items[] = [
                    'title' => short_text($title, 180),
                    'description' => $description,
                    'categoria' => $category,
                    'importanza' => $priority,
                    'owner' => $owner,
                ];
            }
        }
    }
    if ($items) {
        return $items;
    }
    foreach (threat_analysis_extract_bullets((string)($section['content_text'] ?? '')) as $line) {
        if (str_contains($line, '|') && preg_match('/^\|?\s*-+\s*(\|\s*-+\s*)+\|?$/', $line)) {
            continue;
        }
        $title = $line;
        $description = $line;
        if (str_contains($line, ':')) {
            [$title, $description] = array_map('trim', explode(':', $line, 2));
        } elseif (str_contains($line, ' - ')) {
            [$title, $description] = array_map('trim', explode(' - ', $line, 2));
        }
        $items[] = [
            'title' => short_text($title, 180),
            'description' => $description !== '' ? $description : $line,
            'categoria' => '',
            'importanza' => '',
            'owner' => '',
        ];
    }
    return $items;
}

function threat_analysis_match_servizio(string $text): int {
    $needle = threat_analysis_lower($text);
    $bestId = 0;
    $bestScore = 0;
    foreach (get_servizi(true) as $servizio) {
        $name = threat_analysis_lower((string)($servizio['servizio_elementare'] ?? ''));
        $macro = threat_analysis_lower((string)($servizio['macro_service'] ?? ''));
        $categoria = threat_analysis_lower((string)($servizio['categoria'] ?? ''));
        $score = 0;
        if ($name !== '' && str_contains($needle, $name)) {
            $score += 100 + threat_analysis_strlen($name);
        }
        if ($macro !== '' && str_contains($needle, $macro)) {
            $score += 30 + threat_analysis_strlen($macro);
        }
        if ($categoria !== '' && str_contains($needle, $categoria)) {
            $score += 10;
        }
        similar_text($needle, trim($name . ' ' . $macro), $percent);
        $score += (int)$percent;
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestId = (int)$servizio['id'];
        }
    }
    return $bestScore >= 35 ? $bestId : 0;
}

function threat_analysis_service_candidates(array $section): array {
    if (threat_analysis_section_kind($section) !== 'services') {
        return [];
    }
    $items = [];
    foreach (threat_analysis_extract_bullets((string)($section['content_text'] ?? '')) as $line) {
        $items[] = [
            'text' => $line,
            'servizio_id' => threat_analysis_match_servizio($line),
        ];
    }
    return $items;
}

function include_servizio_manuale(int $questionarioId, int $servizioId): void {
    get_db()->prepare(
        "INSERT INTO questionario_risultati_servizi (questionario_id, servizio_id, applicabile, manuale, note)
         VALUES (?, ?, 1, 1, 'Aggiunto da Threat Analysis')
         ON DUPLICATE KEY UPDATE applicabile = 1, manuale = 1, note = VALUES(note)"
    )->execute([$questionarioId, $servizioId]);
}

function requisito_version_actor(): array {
    $user = current_user();
    if ($user) {
        return [
            "id" => (int)$user["id"],
            "label" => user_label($user) . " (" . (string)$user["username"] . ")",
        ];
    }
    return [
        "id" => null,
        "label" => is_admin() ? "Admin tecnico" : "Sistema",
    ];
}

function requisito_version_json(mixed $value): string {
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function requisito_catalogo_version_columns(): array {
    return [
        "id","codice","versione","categoria","sottocategoria","titolo","descrizione","contesto","note","importanza","std","standard","standard_dove","owner",
        "fase","framework_function","funzionale_tecnologico","data_protection","rif_iso","rif_fncs","software_selection","riferimento_hld","pubblicato_lga",
        "rif_std_config_dc","standardizzazione_controllo_task","rif_procedura_controllo","ultimo_update","catalogo_source",
        "appl_dc_ingegneria","appl_dc_change","appl_dc_run","appl_sviluppo","regole_operatore_logico","attivo"
    ];
}

function requisito_specifico_version_columns(): array {
    return [
        "id","questionario_id","task_jira","codice","versione","categoria","sottocategoria","titolo","descrizione","contesto","note","importanza","std","standard","standard_dove","owner",
        "fase","framework_function","funzionale_tecnologico","data_protection","rif_iso","rif_fncs","software_selection","riferimento_hld","pubblicato_lga",
        "rif_std_config_dc","standardizzazione_controllo_task","rif_procedura_controllo","ultimo_update","attivo","requisito_catalogo_id","promosso_at"
    ];
}

function requisito_version_next_no(string $entityType, int $entityId): int {
    $st = get_db()->prepare("SELECT COALESCE(MAX(version_no), 0) + 1 FROM requisito_versioni WHERE entity_type = ? AND entity_id = ?");
    $st->execute([$entityType, $entityId]);
    return (int)$st->fetchColumn();
}

function requisito_catalogo_correlations(int $requisito_id): array {
    $db = get_db();
    $st = $db->prepare(
        "SELECT rc.categoria_id, c.nome
         FROM requisito_categoria rc
         LEFT JOIN requisito_categorie c ON c.id = rc.categoria_id
         WHERE rc.requisito_id = ?
         ORDER BY rc.categoria_id"
    );
    $st->execute([$requisito_id]);
    $categorie = $st->fetchAll();

    $st = $db->prepare(
        "SELECT *
         FROM regole_requisiti_gruppi
         WHERE requisito_id = ?
         ORDER BY ordine, id"
    );
    $st->execute([$requisito_id]);
    $gruppi = [];
    foreach ($st->fetchAll() as $gruppo) {
        $rules = $db->prepare(
            "SELECT id, domanda_id, valore_atteso, operatore_logico, requisito_id
             FROM regole_requisiti
             WHERE gruppo_id = ? AND requisito_id = ?
             ORDER BY id"
        );
        $rules->execute([(int)$gruppo["id"], $requisito_id]);
        $gruppo["regole"] = $rules->fetchAll();
        $gruppi[] = $gruppo;
    }

    return [
        "categorie" => $categorie,
        "regole_gruppi" => $gruppi,
    ];
}

function requisito_specifico_correlations(int $specifico_id): array {
    $db = get_db();
    $st = $db->prepare(
        "SELECT sc.categoria_id, c.nome
         FROM requisito_specifico_categoria sc
         LEFT JOIN requisito_categorie c ON c.id = sc.categoria_id
         WHERE sc.requisito_specifico_id = ?
         ORDER BY sc.categoria_id"
    );
    $st->execute([$specifico_id]);
    $categorie = $st->fetchAll();

    $st = $db->prepare(
        "SELECT questionario_id
         FROM questionario_requisiti_specifici_link
         WHERE requisito_specifico_id = ?
         ORDER BY questionario_id"
    );
    $st->execute([$specifico_id]);

    return [
        "categorie" => $categorie,
        "questionari" => $st->fetchAll(),
    ];
}

function capture_requisito_version(string $entityType, int $entityId, string $action = "snapshot"): int {
    if (!in_array($entityType, ["catalogo", "specifico"], true) || $entityId <= 0) {
        return 0;
    }
    $db = get_db();
    if ($entityType === "catalogo") {
        $snapshot = get_requisito($entityId);
        $correlations = requisito_catalogo_correlations($entityId);
    } else {
        $snapshot = get_requisito_specifico($entityId);
        $correlations = requisito_specifico_correlations($entityId);
    }
    if (!$snapshot) {
        return 0;
    }
    $actor = requisito_version_actor();
    $versionNo = requisito_version_next_no($entityType, $entityId);
    $db->prepare(
        "INSERT INTO requisito_versioni
         (entity_type, entity_id, version_no, action, snapshot_json, correlations_json, changed_by_user_id, changed_by_label)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $entityType,
        $entityId,
        $versionNo,
        $action,
        requisito_version_json($snapshot),
        requisito_version_json($correlations),
        $actor["id"],
        $actor["label"],
    ]);
    return (int)$db->lastInsertId();
}

function get_requisito_versioni(string $entityType, int $entityId): array {
    $st = get_db()->prepare(
        "SELECT *
         FROM requisito_versioni
         WHERE entity_type = ? AND entity_id = ?
         ORDER BY version_no DESC"
    );
    $st->execute([$entityType, $entityId]);
    return $st->fetchAll();
}

function get_requisito_versione(int $versionId): array|false {
    $st = get_db()->prepare("SELECT * FROM requisito_versioni WHERE id = ?");
    $st->execute([$versionId]);
    return $st->fetch();
}

function requisito_version_decode(?string $json): array {
    if (!$json) {
        return [];
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function row_exists_by_id(string $table, int $id): bool {
    if (!in_array($table, ["requisiti", "questionario_requisiti_specifici", "questionari", "domande", "requisito_categorie"], true)) {
        return false;
    }
    $st = get_db()->prepare("SELECT COUNT(*) FROM $table WHERE id = ?");
    $st->execute([$id]);
    return (int)$st->fetchColumn() > 0;
}

function restore_requisito_row(string $table, array $snapshot, array $columns): void {
    $db = get_db();
    $id = (int)($snapshot["id"] ?? 0);
    if ($id <= 0) {
        throw new RuntimeException("Snapshot requisito non valido: ID assente.");
    }
    $values = [];
    foreach ($columns as $column) {
        $values[$column] = $snapshot[$column] ?? null;
    }
    if (row_exists_by_id($table, $id)) {
        $updateColumns = array_values(array_filter($columns, fn($column) => $column !== "id"));
        $sets = implode(",", array_map(fn($column) => "$column = ?", $updateColumns));
        $params = array_map(fn($column) => $values[$column], $updateColumns);
        $params[] = $id;
        $db->prepare("UPDATE $table SET $sets WHERE id = ?")->execute($params);
        return;
    }
    $cols = implode(",", $columns);
    $phs = implode(",", array_fill(0, count($columns), "?"));
    $params = array_map(fn($column) => $values[$column], $columns);
    $db->prepare("INSERT INTO $table ($cols) VALUES ($phs)")->execute($params);
}

function restore_requisito_catalogo_correlations(int $requisito_id, array $correlations): void {
    $db = get_db();
    $db->prepare("DELETE FROM requisito_categoria WHERE requisito_id = ?")->execute([$requisito_id]);
    foreach (($correlations["categorie"] ?? []) as $categoria) {
        $categoriaId = (int)($categoria["categoria_id"] ?? 0);
        if ($categoriaId <= 0 || !row_exists_by_id("requisito_categorie", $categoriaId)) {
            $categoriaId = ensure_requisito_categoria((string)($categoria["nome"] ?? ""));
        }
        if ($categoriaId > 0) {
            $db->prepare("INSERT IGNORE INTO requisito_categoria (requisito_id,categoria_id) VALUES (?,?)")
                ->execute([$requisito_id, $categoriaId]);
        }
    }

    $db->prepare("DELETE FROM regole_requisiti WHERE requisito_id = ?")->execute([$requisito_id]);
    $db->prepare("DELETE FROM regole_requisiti_gruppi WHERE requisito_id = ?")->execute([$requisito_id]);
    foreach (($correlations["regole_gruppi"] ?? []) as $gruppo) {
        $nome = trim((string)($gruppo["nome"] ?? "Gruppo regole")) ?: "Gruppo regole";
        $operatore = normalize_rule_operator((string)($gruppo["operatore_logico"] ?? "OR"));
        $gruppoLogicoId = ensure_regole_gruppo_anagrafica($nome);
        $db->prepare(
            "INSERT INTO regole_requisiti_gruppi (gruppo_logico_id,requisito_id,nome,operatore_logico,ordine,attivo)
             VALUES (?,?,?,?,?,?)"
        )->execute([
            $gruppoLogicoId,
            $requisito_id,
            $nome,
            $operatore,
            (int)($gruppo["ordine"] ?? 0),
            (int)($gruppo["attivo"] ?? 1),
        ]);
        $newGroupId = (int)$db->lastInsertId();
        foreach (($gruppo["regole"] ?? []) as $regola) {
            $domandaId = (int)($regola["domanda_id"] ?? 0);
            if ($domandaId <= 0 || !row_exists_by_id("domande", $domandaId)) {
                continue;
            }
            $db->prepare(
                "INSERT IGNORE INTO regole_requisiti (gruppo_id,domanda_id,valore_atteso,operatore_logico,requisito_id)
                 VALUES (?,?,?,?,?)"
            )->execute([
                $newGroupId,
                $domandaId,
                (string)($regola["valore_atteso"] ?? "1"),
                normalize_rule_operator((string)($regola["operatore_logico"] ?? $operatore)),
                $requisito_id,
            ]);
        }
    }
}

function restore_requisito_specifico_correlations(int $specifico_id, array $correlations): void {
    $db = get_db();
    $db->prepare("DELETE FROM requisito_specifico_categoria WHERE requisito_specifico_id = ?")->execute([$specifico_id]);
    foreach (($correlations["categorie"] ?? []) as $categoria) {
        $categoriaId = (int)($categoria["categoria_id"] ?? 0);
        if ($categoriaId <= 0 || !row_exists_by_id("requisito_categorie", $categoriaId)) {
            $categoriaId = ensure_requisito_categoria((string)($categoria["nome"] ?? ""));
        }
        if ($categoriaId > 0) {
            $db->prepare("INSERT IGNORE INTO requisito_specifico_categoria (requisito_specifico_id,categoria_id) VALUES (?,?)")
                ->execute([$specifico_id, $categoriaId]);
        }
    }

    $db->prepare("DELETE FROM questionario_requisiti_specifici_link WHERE requisito_specifico_id = ?")->execute([$specifico_id]);
    foreach (($correlations["questionari"] ?? []) as $link) {
        $questionarioId = (int)($link["questionario_id"] ?? 0);
        if ($questionarioId > 0 && row_exists_by_id("questionari", $questionarioId)) {
            $db->prepare(
                "INSERT IGNORE INTO questionario_requisiti_specifici_link (questionario_id,requisito_specifico_id)
                 VALUES (?,?)"
            )->execute([$questionarioId, $specifico_id]);
        }
    }
}

function restore_requisito_version(int $versionId): array {
    $version = get_requisito_versione($versionId);
    if (!$version) {
        return ["ok" => false, "message" => "Versione non trovata."];
    }
    $entityType = (string)$version["entity_type"];
    $snapshot = requisito_version_decode((string)$version["snapshot_json"]);
    $correlations = requisito_version_decode((string)$version["correlations_json"]);
    $entityId = (int)($snapshot["id"] ?? $version["entity_id"]);
    if ($entityId <= 0) {
        return ["ok" => false, "message" => "Snapshot non valido."];
    }

    $db = get_db();
    try {
        $db->beginTransaction();
        if ($entityType === "catalogo") {
            $codice = (string)($snapshot["codice"] ?? "");
            if ($codice !== "") {
                $conflict = $db->prepare("SELECT id FROM requisiti WHERE codice = ? AND id <> ?");
                $conflict->execute([$codice, $entityId]);
                if ($conflict->fetchColumn()) {
                    throw new RuntimeException("Ripristino non possibile: codice requisito gi? usato da un altro record.");
                }
            }
            restore_requisito_row("requisiti", $snapshot, requisito_catalogo_version_columns());
            restore_requisito_catalogo_correlations($entityId, $correlations);
            capture_requisito_version("catalogo", $entityId, "restore_v" . (int)$version["version_no"]);
        } elseif ($entityType === "specifico") {
            $questionarioId = (int)($snapshot["questionario_id"] ?? 0);
            if ($questionarioId > 0 && !row_exists_by_id("questionari", $questionarioId)) {
                $snapshot["questionario_id"] = null;
            }
            restore_requisito_row("questionario_requisiti_specifici", $snapshot, requisito_specifico_version_columns());
            restore_requisito_specifico_correlations($entityId, $correlations);
            capture_requisito_version("specifico", $entityId, "restore_v" . (int)$version["version_no"]);
        } else {
            throw new RuntimeException("Tipo requisito non gestito.");
        }
        $db->commit();
        return ["ok" => true, "message" => "Versione ripristinata correttamente."];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ["ok" => false, "message" => $e->getMessage()];
    }
}

function get_servizi(bool $only_active = false): array {
    $sql = "SELECT * FROM servizi";
    if ($only_active) $sql .= " WHERE attivo = 1";
    $sql .= " ORDER BY reparto_owner, macro_service, servizio_elementare";
    return get_db()->query($sql)->fetchAll();
}

function get_servizio(int $id): array|false {
    $st = get_db()->prepare("SELECT * FROM servizi WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch();
}
// â”€â”€ Admin CRUD: Domande â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function save_domanda(array $data): void {
    $db = get_db();
    $id = (int)($data["id"] ?? 0);
    $codice      = trim($data["codice"]      ?? "");
    $categoria   = trim($data["categoria"]   ?? "");
    $testo       = trim($data["testo"]       ?? "");
    $tipo        = in_array($data["tipo"] ?? "", ["bool","text","select","multi"]) ? $data["tipo"] : "bool";
    $ordine      = (int)($data["ordine"]      ?? 0);
    $obbligatoria = isset($data["obbligatoria"]) ? 1 : 0;
    $attiva       = isset($data["attiva"])       ? 1 : 0;

    if ($id > 0) {
        $db->prepare(
            "UPDATE domande SET codice=?,categoria=?,testo=?,tipo=?,ordine=?,obbligatoria=?,attiva=? WHERE id=?"
        )->execute([$codice,$categoria,$testo,$tipo,$ordine,$obbligatoria,$attiva,$id]);
    } else {
        $db->prepare(
            "INSERT INTO domande (codice,categoria,testo,tipo,ordine,obbligatoria,attiva) VALUES (?,?,?,?,?,?,?)"
        )->execute([$codice,$categoria,$testo,$tipo,$ordine,$obbligatoria,$attiva]);
    }
}

function delete_domanda(int $id): void {
    get_db()->prepare("DELETE FROM domande WHERE id = ?")->execute([$id]);
}

// â”€â”€ Admin CRUD: Requisiti â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function save_requisito(array $data): int {
    $db = get_db();
    $id = (int)($data["id"] ?? 0);
    $categoria_id = (int)($data["categoria_id"] ?? 0);
    if ($categoria_id > 0) {
        $data["categoria"] = get_requisito_categoria_nome($categoria_id);
    }
    if (!truthy_checkbox($data, "standard")) {
        $data["std"] = "";
        $data["standard_dove"] = "";
    } elseif (trim((string)($data["std"] ?? "")) === "") {
        $data["std"] = trim((string)($data["standard_dove"] ?? ""));
    }
    if (!isset($data["regole_operatore_logico"]) || trim((string)$data["regole_operatore_logico"]) === "") {
        $existing = $id > 0 ? get_requisito($id) : null;
        $data["regole_operatore_logico"] = $existing["regole_operatore_logico"] ?? "OR";
    }
    $data["regole_operatore_logico"] = normalize_rule_operator((string)$data["regole_operatore_logico"]);
    $fields = [
        "codice","versione","categoria","sottocategoria","titolo","descrizione","contesto","note","importanza","std","standard","standard_dove","owner",
        "fase","framework_function","funzionale_tecnologico","data_protection","rif_iso","rif_fncs","software_selection","riferimento_hld","pubblicato_lga",
        "rif_std_config_dc","standardizzazione_controllo_task","rif_procedura_controllo","ultimo_update","catalogo_source",
        "appl_dc_ingegneria","appl_dc_change","appl_dc_run","appl_sviluppo","regole_operatore_logico"
    ];
    $vals = array_map(fn($f) => $f === "standard" ? (truthy_checkbox($data, $f) ? 1 : 0) : trim((string)($data[$f] ?? "")), $fields);
    $attivo = isset($data["attivo"]) ? 1 : 0;
    if ($id > 0) {
        $sets = implode(",", array_map(fn($f) => "$f=?", $fields));
        $db->prepare("UPDATE requisiti SET $sets,attivo=? WHERE id=?")
           ->execute([...$vals, $attivo, $id]);
        sync_requisito_categoria($id, $categoria_id, (string)($data["categoria"] ?? ""));
        capture_requisito_version("catalogo", $id, "update");
        return $id;
    } else {
        $cols = implode(",", $fields);
        $phs  = implode(",", array_fill(0, count($fields), "?"));
        $db->prepare("INSERT INTO requisiti ($cols,attivo) VALUES ($phs,?)")
           ->execute([...$vals, $attivo]);
        $newId = (int)$db->lastInsertId();
        sync_requisito_categoria($newId, $categoria_id, (string)($data["categoria"] ?? ""));
        capture_requisito_version("catalogo", $newId, "create");
        return $newId;
    }
}

function delete_requisito(int $id): void {
    capture_requisito_version("catalogo", $id, "delete");
    get_db()->prepare("DELETE FROM requisiti WHERE id = ?")->execute([$id]);
}

// â”€â”€ Admin CRUD: Servizi â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function save_servizio(array $data): int {
    $db = get_db();
    $id = (int)($data["id"] ?? 0);
    if (!isset($data["regole_operatore_logico"])) {
        $existing = $id > 0 ? get_servizio($id) : null;
        $data["regole_operatore_logico"] = $existing["regole_operatore_logico"] ?? "OR";
    }
    $data["regole_operatore_logico"] = normalize_rule_operator((string)$data["regole_operatore_logico"]);
    $fields = ["reparto_owner","tipo_canone_ci","portfolio_category","macro_service","categoria","servizio_elementare","descrizione","tipo_attivita","misurabilita","commessa","check_component","asset_primario","software","orario_servizio","note","regole_operatore_logico"];
    $vals = array_map(fn($f) => trim($data[$f] ?? ""), $fields);
    $attivo = isset($data["attivo"]) ? 1 : 0;
    if ($id > 0) {
        $sets = implode(",", array_map(fn($f) => "$f=?", $fields));
        $db->prepare("UPDATE servizi SET $sets,attivo=? WHERE id=?")
           ->execute([...$vals, $attivo, $id]);
        return $id;
    } else {
        $cols = implode(",", $fields);
        $phs  = implode(",", array_fill(0, count($fields), "?"));
        $db->prepare("INSERT INTO servizi ($cols,attivo) VALUES ($phs,?)")
           ->execute([...$vals, $attivo]);
        return (int)$db->lastInsertId();
    }
}

function delete_servizio(int $id): void {
    get_db()->prepare("UPDATE servizi SET attivo = 0 WHERE id = ?")->execute([$id]);
}

function set_servizio_active(int $id, bool $active): void {
    get_db()->prepare("UPDATE servizi SET attivo = ? WHERE id = ?")->execute([$active ? 1 : 0, $id]);
}

function normalize_rule_operator(string $operatore_logico): string {
    return strtoupper($operatore_logico) === "AND" ? "AND" : "OR";
}

function ensure_regole_gruppo_anagrafica(string $nome): int {
    $nome = trim($nome) ?: "Gruppo regole";
    $db = get_db();
    $db->prepare("INSERT IGNORE INTO regole_gruppi (nome) VALUES (?)")->execute([$nome]);
    $st = $db->prepare("SELECT id FROM regole_gruppi WHERE nome = ?");
    $st->execute([$nome]);
    return (int)$st->fetchColumn();
}

function set_requisito_regole_operatore(int $requisito_id, string $operatore_logico): void {
    $operatore_logico = normalize_rule_operator($operatore_logico);
    $db = get_db();
    $db->prepare("UPDATE requisiti SET regole_operatore_logico = ? WHERE id = ?")
        ->execute([$operatore_logico, $requisito_id]);
    $db->prepare("UPDATE regole_requisiti SET operatore_logico = ? WHERE requisito_id = ?")
        ->execute([$operatore_logico, $requisito_id]);
    capture_requisito_version("catalogo", $requisito_id, "rules_operator");
}

function get_regole_requisiti_gruppi(int $requisito_id): array {
    $st = get_db()->prepare(
        "SELECT g.*, r.codice AS req_codice, r.titolo AS req_titolo
         FROM regole_requisiti_gruppi g
         JOIN requisiti r ON r.id = g.requisito_id
         WHERE g.requisito_id = ? AND g.attivo = 1
         ORDER BY g.ordine, g.id"
    );
    $st->execute([$requisito_id]);
    return $st->fetchAll();
}

function get_all_regole_requisiti_gruppi(string $nome_gruppo = ""): array {
    $params = [];
    $where = "WHERE g.attivo = 1";
    if (trim($nome_gruppo) !== "") {
        $where .= " AND g.nome LIKE ?";
        $params[] = "%" . trim($nome_gruppo) . "%";
    }
    $st = get_db()->prepare(
        "SELECT g.*, r.codice AS req_codice, r.titolo AS req_titolo,
                COUNT(rr.id) AS regole_count
         FROM regole_requisiti_gruppi g
         JOIN requisiti r ON r.id = g.requisito_id
         LEFT JOIN regole_requisiti rr ON rr.gruppo_id = g.id
         {$where}
         GROUP BY g.id
         ORDER BY r.codice, g.ordine, g.id"
    );
    $st->execute($params);
    return $st->fetchAll();
}

function get_regole_requisiti_by_gruppo(int $gruppo_id): array {
    $st = get_db()->prepare(
        "SELECT rr.*, d.codice, d.testo
         FROM regole_requisiti rr
         JOIN domande d ON d.id = rr.domanda_id
         WHERE rr.gruppo_id = ?
         ORDER BY d.codice, rr.valore_atteso"
    );
    $st->execute([$gruppo_id]);
    return $st->fetchAll();
}

function save_regole_requisiti_gruppo(int $requisito_id, string $nome, string $operatore_logico, int $ordine = 0, int $id = 0): int {
    $operatore_logico = normalize_rule_operator($operatore_logico);
    $nome = trim($nome) ?: "Gruppo regole";
    $gruppo_logico_id = ensure_regole_gruppo_anagrafica($nome);
    if ($id > 0) {
        get_db()->prepare(
            "UPDATE regole_requisiti_gruppi
             SET gruppo_logico_id = ?, nome = ?, operatore_logico = ?, ordine = ?
             WHERE id = ? AND requisito_id = ?"
        )->execute([$gruppo_logico_id, $nome, $operatore_logico, $ordine, $id, $requisito_id]);
        get_db()->prepare("UPDATE regole_requisiti SET operatore_logico = ? WHERE gruppo_id = ?")
            ->execute([$operatore_logico, $id]);
        capture_requisito_version("catalogo", $requisito_id, "rules_group_update");
        return $id;
    }
    get_db()->prepare(
        "INSERT INTO regole_requisiti_gruppi (gruppo_logico_id,requisito_id,nome,operatore_logico,ordine)
         VALUES (?,?,?,?,?)"
    )->execute([$gruppo_logico_id, $requisito_id, $nome, $operatore_logico, $ordine]);
    $newId = (int)get_db()->lastInsertId();
    capture_requisito_version("catalogo", $requisito_id, "rules_group_create");
    return $newId;
}

function delete_regole_requisiti_gruppo(int $id, int $requisito_id): void {
    $db = get_db();
    $db->prepare("DELETE FROM regole_requisiti WHERE gruppo_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM regole_requisiti_gruppi WHERE id = ? AND requisito_id = ?")->execute([$id, $requisito_id]);
    capture_requisito_version("catalogo", $requisito_id, "rules_group_delete");
}

function set_servizio_regole_operatore(int $servizio_id, string $operatore_logico): void {
    $operatore_logico = normalize_rule_operator($operatore_logico);
    $db = get_db();
    $db->prepare("UPDATE servizi SET regole_operatore_logico = ? WHERE id = ?")
        ->execute([$operatore_logico, $servizio_id]);
    $db->prepare("UPDATE regole_servizi SET operatore_logico = ? WHERE servizio_id = ?")
        ->execute([$operatore_logico, $servizio_id]);
}

function get_regole_servizi_gruppi(int $servizio_id): array {
    $st = get_db()->prepare(
        "SELECT g.*, s.servizio_elementare
         FROM regole_servizi_gruppi g
         JOIN servizi s ON s.id = g.servizio_id
         WHERE g.servizio_id = ? AND g.attivo = 1
         ORDER BY g.ordine, g.id"
    );
    $st->execute([$servizio_id]);
    return $st->fetchAll();
}

function get_all_regole_servizi_gruppi(string $nome_gruppo = ""): array {
    $params = [];
    $where = "WHERE g.attivo = 1";
    if (trim($nome_gruppo) !== "") {
        $where .= " AND g.nome LIKE ?";
        $params[] = "%" . trim($nome_gruppo) . "%";
    }
    $st = get_db()->prepare(
        "SELECT g.*, s.servizio_elementare,
                COUNT(rs.id) AS regole_count
         FROM regole_servizi_gruppi g
         JOIN servizi s ON s.id = g.servizio_id
         LEFT JOIN regole_servizi rs ON rs.gruppo_id = g.id
         {$where}
         GROUP BY g.id
         ORDER BY s.servizio_elementare, g.ordine, g.id"
    );
    $st->execute($params);
    return $st->fetchAll();
}

function get_regole_servizi_by_gruppo(int $gruppo_id): array {
    $st = get_db()->prepare(
        "SELECT rs.*, d.codice, d.testo
         FROM regole_servizi rs
         JOIN domande d ON d.id = rs.domanda_id
         WHERE rs.gruppo_id = ?
         ORDER BY d.codice, rs.valore_atteso"
    );
    $st->execute([$gruppo_id]);
    return $st->fetchAll();
}

function save_regole_servizi_gruppo(int $servizio_id, string $nome, string $operatore_logico, int $ordine = 0, int $id = 0): int {
    $operatore_logico = normalize_rule_operator($operatore_logico);
    $nome = trim($nome) ?: "Gruppo regole";
    $gruppo_logico_id = ensure_regole_gruppo_anagrafica($nome);
    if ($id > 0) {
        get_db()->prepare(
            "UPDATE regole_servizi_gruppi
             SET gruppo_logico_id = ?, nome = ?, operatore_logico = ?, ordine = ?
             WHERE id = ? AND servizio_id = ?"
        )->execute([$gruppo_logico_id, $nome, $operatore_logico, $ordine, $id, $servizio_id]);
        get_db()->prepare("UPDATE regole_servizi SET operatore_logico = ? WHERE gruppo_id = ?")
            ->execute([$operatore_logico, $id]);
        return $id;
    }
    get_db()->prepare(
        "INSERT INTO regole_servizi_gruppi (gruppo_logico_id,servizio_id,nome,operatore_logico,ordine)
         VALUES (?,?,?,?,?)"
    )->execute([$gruppo_logico_id, $servizio_id, $nome, $operatore_logico, $ordine]);
    return (int)get_db()->lastInsertId();
}

function delete_regole_servizi_gruppo(int $id, int $servizio_id): void {
    $db = get_db();
    $db->prepare("DELETE FROM regole_servizi WHERE gruppo_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM regole_servizi_gruppi WHERE id = ? AND servizio_id = ?")->execute([$id, $servizio_id]);
}

function short_text(?string $text, int $max = 80): string {
    $text = trim((string)$text);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 3) . '...' : $text;
    }
    return strlen($text) > $max ? substr($text, 0, $max - 3) . '...' : $text;
}

// â”€â”€ Admin CRUD: Regole â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function get_regole_requisiti(?int $domanda_id = null, ?int $requisito_id = null): array {
    $sql = "SELECT rr.id, rr.gruppo_id, rr.valore_atteso, rr.operatore_logico, d.codice as dom_codice, d.testo as dom_testo,
                   g.nome AS gruppo_nome, g.operatore_logico AS gruppo_operatore,
                   r.codice as req_codice, r.titolo as req_titolo, r.id as requisito_id, r.regole_operatore_logico AS group_operatore_logico, d.id as domanda_id
            FROM regole_requisiti rr
            JOIN domande d ON d.id = rr.domanda_id
            JOIN requisiti r ON r.id = rr.requisito_id
            LEFT JOIN regole_requisiti_gruppi g ON g.id = rr.gruppo_id
            WHERE 1=1";
    $params = [];
    if ($domanda_id) { $sql .= " AND rr.domanda_id=?"; $params[] = $domanda_id; }
    if ($requisito_id) { $sql .= " AND rr.requisito_id=?"; $params[] = $requisito_id; }
    $sql .= " ORDER BY d.codice, r.codice";
    $st = get_db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function save_regola_requisito(int $domanda_id, string $valore_atteso, int $requisito_id, string $operatore_logico = "OR", int $gruppo_id = 0): void {
    $operatore_logico = normalize_rule_operator($operatore_logico);
    if ($gruppo_id <= 0) {
        $groups = get_regole_requisiti_gruppi($requisito_id);
        $gruppo_id = $groups ? (int)$groups[0]["id"] : save_regole_requisiti_gruppo($requisito_id, "Default", $operatore_logico);
    }
    $group = get_db()->prepare("SELECT operatore_logico FROM regole_requisiti_gruppi WHERE id = ? AND requisito_id = ?");
    $group->execute([$gruppo_id, $requisito_id]);
    $operatore_logico = normalize_rule_operator((string)($group->fetchColumn() ?: $operatore_logico));
    $db = get_db();
    $db->prepare(
        "INSERT INTO regole_requisiti (gruppo_id,domanda_id,valore_atteso,operatore_logico,requisito_id) VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE operatore_logico=VALUES(operatore_logico)"
    )->execute([$gruppo_id, $domanda_id, $valore_atteso, $operatore_logico, $requisito_id]);
    capture_requisito_version("catalogo", $requisito_id, "rules_add");
}

function delete_regola_requisito(int $id): void {
    $st = get_db()->prepare("SELECT requisito_id FROM regole_requisiti WHERE id = ?");
    $st->execute([$id]);
    $requisitoId = (int)$st->fetchColumn();
    get_db()->prepare("DELETE FROM regole_requisiti WHERE id = ?")->execute([$id]);
    if ($requisitoId > 0) {
        capture_requisito_version("catalogo", $requisitoId, "rules_delete");
    }
}

function update_regola_requisito(int $id, int $gruppo_id, int $domanda_id, string $valore_atteso): void {
    $st = get_db()->prepare(
        "SELECT g.operatore_logico, g.requisito_id
         FROM regole_requisiti rr
         JOIN regole_requisiti_gruppi g ON g.id = ?
         WHERE rr.id = ?"
    );
    $st->execute([$gruppo_id, $id]);
    $row = $st->fetch();
    if (!$row || $domanda_id <= 0 || $gruppo_id <= 0) {
        return;
    }
    get_db()->prepare(
        "UPDATE regole_requisiti
         SET gruppo_id = ?, domanda_id = ?, valore_atteso = ?, operatore_logico = ?, requisito_id = ?
         WHERE id = ?"
    )->execute([$gruppo_id, $domanda_id, trim($valore_atteso), $row["operatore_logico"], (int)$row["requisito_id"], $id]);
    capture_requisito_version("catalogo", (int)$row["requisito_id"], "rules_update");
}

function get_regole_servizi(?int $domanda_id = null, ?int $servizio_id = null): array {
    $sql = "SELECT rs.id, rs.gruppo_id, rs.valore_atteso, rs.operatore_logico, d.codice as dom_codice, d.testo as dom_testo,
                   g.nome AS gruppo_nome, g.operatore_logico AS gruppo_operatore,
                   s.servizio_elementare, s.id as servizio_id, s.regole_operatore_logico AS group_operatore_logico, d.id as domanda_id
            FROM regole_servizi rs
            JOIN domande d ON d.id = rs.domanda_id
            JOIN servizi s ON s.id = rs.servizio_id
            LEFT JOIN regole_servizi_gruppi g ON g.id = rs.gruppo_id
            WHERE 1=1";
    $params = [];
    if ($domanda_id) { $sql .= " AND rs.domanda_id=?"; $params[] = $domanda_id; }
    if ($servizio_id) { $sql .= " AND rs.servizio_id=?"; $params[] = $servizio_id; }
    $sql .= " ORDER BY d.codice, s.servizio_elementare";
    $st = get_db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function save_regola_servizio(int $domanda_id, string $valore_atteso, int $servizio_id, string $operatore_logico = "OR", int $gruppo_id = 0): void {
    $operatore_logico = normalize_rule_operator($operatore_logico);
    if ($gruppo_id <= 0) {
        $groups = get_regole_servizi_gruppi($servizio_id);
        $gruppo_id = $groups ? (int)$groups[0]["id"] : save_regole_servizi_gruppo($servizio_id, "Default", $operatore_logico);
    }
    $group = get_db()->prepare("SELECT operatore_logico FROM regole_servizi_gruppi WHERE id = ? AND servizio_id = ?");
    $group->execute([$gruppo_id, $servizio_id]);
    $operatore_logico = normalize_rule_operator((string)($group->fetchColumn() ?: $operatore_logico));
    get_db()->prepare(
        "INSERT INTO regole_servizi (gruppo_id,domanda_id,valore_atteso,operatore_logico,servizio_id) VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE operatore_logico=VALUES(operatore_logico)"
    )->execute([$gruppo_id, $domanda_id, $valore_atteso, $operatore_logico, $servizio_id]);
}

function delete_regola_servizio(int $id): void {
    get_db()->prepare("DELETE FROM regole_servizi WHERE id = ?")->execute([$id]);
}

function update_regola_servizio(int $id, int $gruppo_id, int $domanda_id, string $valore_atteso): void {
    $st = get_db()->prepare(
        "SELECT g.operatore_logico, g.servizio_id
         FROM regole_servizi rs
         JOIN regole_servizi_gruppi g ON g.id = ?
         WHERE rs.id = ?"
    );
    $st->execute([$gruppo_id, $id]);
    $row = $st->fetch();
    if (!$row || $domanda_id <= 0 || $gruppo_id <= 0) {
        return;
    }
    get_db()->prepare(
        "UPDATE regole_servizi
         SET gruppo_id = ?, domanda_id = ?, valore_atteso = ?, operatore_logico = ?, servizio_id = ?
         WHERE id = ?"
    )->execute([$gruppo_id, $domanda_id, trim($valore_atteso), $row["operatore_logico"], (int)$row["servizio_id"], $id]);
}

function pir_project_requirements(int $questionario_id): array {
    $rows = [];

    foreach (get_requisiti_specifici($questionario_id) as $requisito) {
        $requisito["pir_tipo"] = "specifico";
        $requisito["pir_tipo_label"] = "Specifico";
        $requisito["pir_ref_id"] = (int)$requisito["id"];
        $requisito["pir_catalog_status"] = [
            "tone" => "primary",
            "label" => "Requisito specifico di progetto",
            "detail" => "Gestito fuori dal catalogo requisiti standard.",
        ];
        $rows[] = $requisito;
    }

    $latestCalcolo = get_latest_questionario_requisiti_calcolo($questionario_id);
    $snapshotRows = get_questionario_requisiti_snapshot($questionario_id, $latestCalcolo ? (int)$latestCalcolo["id"] : 0);
    $snapshotIds = [];
    foreach ($snapshotRows as $snapshotRow) {
        $requisito = requisito_snapshot_to_requirement($snapshotRow);
        $snapshotIds[(int)$requisito["id"]] = true;
        $isStandard = (string)($snapshotRow["assegnazione_tipo"] ?? "") === "default_design" || requirement_is_standard($requisito);
        $requisito["pir_tipo"] = $isStandard ? "default_design" : "catalogo";
        $requisito["pir_tipo_label"] = $isStandard ? "Default design" : "Catalogo";
        $requisito["pir_ref_id"] = (int)$requisito["id"];
        $requisito["pir_catalog_status"] = requisito_snapshot_compare_status($requisito);
        $rows[] = $requisito;
    }

    if (!$snapshotRows) {
        $gruppi = get_requisiti_revisionati($questionario_id);
        foreach ($gruppi["catalogo"] ?? [] as $requisito) {
            $requisito["pir_tipo"] = "catalogo";
            $requisito["pir_tipo_label"] = "Catalogo";
            $requisito["pir_ref_id"] = (int)$requisito["id"];
            $requisito["pir_catalog_status"] = [
                "tone" => "secondary",
                "label" => "Storico non disponibile",
                "detail" => "Il questionario non ha ancora una fotografia storica dei requisiti assegnati. Riesegui il calcolo risultati per crearla.",
            ];
            $rows[] = $requisito;
            $snapshotIds[(int)$requisito["id"]] = true;
        }
        foreach ($gruppi["standard"] ?? [] as $requisito) {
            $requisito["pir_tipo"] = "default_design";
            $requisito["pir_tipo_label"] = "Default design";
            $requisito["pir_ref_id"] = (int)$requisito["id"];
            $requisito["pir_catalog_status"] = [
                "tone" => "secondary",
                "label" => "Storico non disponibile",
                "detail" => "Il questionario non ha ancora una fotografia storica dei requisiti assegnati. Riesegui il calcolo risultati per crearla.",
            ];
            $rows[] = $requisito;
            $snapshotIds[(int)$requisito["id"]] = true;
        }
    }

    foreach (get_current_applicable_requisiti_catalogo($questionario_id) as $requisito) {
        if (isset($snapshotIds[(int)$requisito["id"]])) {
            continue;
        }
        $isStandard = requirement_is_standard($requisito);
        $requisito["pir_tipo"] = $isStandard ? "default_design" : "catalogo";
        $requisito["pir_tipo_label"] = $isStandard ? "Default design potenziale" : "Catalogo potenziale";
        $requisito["pir_ref_id"] = (int)$requisito["id"];
        $requisito["pir_new_candidate"] = true;
        $requisito["pir_catalog_status"] = requisito_candidate_history_status($requisito, $latestCalcolo);
        $rows[] = $requisito;
    }
    return $rows;
}

function get_pir_reviews_map(int $questionario_id): array {
    $st = get_db()->prepare("SELECT * FROM pir_requirement_reviews WHERE questionario_id = ?");
    $st->execute([$questionario_id]);
    $map = [];
    foreach ($st->fetchAll() as $row) {
        $map[$row["requisito_tipo"] . ":" . $row["requisito_ref_id"]] = $row;
    }
    return $map;
}

function get_pir_all_participants(int $questionario_id): array {
    $st = get_db()->prepare(
        "SELECT p.*, m.data_riunione
         FROM pir_meeting_participants p
         JOIN pir_meetings m ON m.id = p.meeting_id
         WHERE m.questionario_id = ?
         ORDER BY m.data_riunione DESC, p.nome"
    );
    $st->execute([$questionario_id]);
    return $st->fetchAll();
}

function pir_pending_requirements_count(int $questionario_id): int {
    $requirements = pir_project_requirements($questionario_id);
    $reviews = get_pir_reviews_map($questionario_id);
    $pending = 0;
    foreach ($requirements as $req) {
        $key = $req["pir_tipo"] . ":" . $req["pir_ref_id"];
        if (trim((string)($reviews[$key]["stato"] ?? "")) === "") {
            $pending++;
        }
    }
    return $pending;
}

function update_pir_settings(int $questionario_id, int $analista_id, string $pir_stato): array {
    $pir_stato = $pir_stato === "completata" ? "completata" : "in_corso";
    if ($pir_stato === "completata") {
        $pending = pir_pending_requirements_count($questionario_id);
        if ($pending > 0) {
            return ["ok" => false, "message" => "Non puoi completare la PIR: ci sono ancora {$pending} requisiti da valutare."];
        }
    }
    get_db()->prepare("UPDATE questionari SET pir_analista_id = ?, pir_stato = ? WHERE id = ?")
        ->execute([$analista_id > 0 ? $analista_id : null, $pir_stato, $questionario_id]);
    return ["ok" => true, "message" => "Impostazioni PIR aggiornate."];
}

function save_pir_requirement_review(int $questionario_id, string $tipo, int $ref_id, array $data): array {
    if (!in_array($tipo, ["catalogo", "default_design", "specifico"], true) || $ref_id <= 0) {
        return ["ok" => false, "message" => "Requisito PIR non valido."];
    }
    $stato = trim((string)($data["stato"] ?? ""));
    $validStates = ["", "OK", "KO", "non_applicabile", "parziale"];
    if (!in_array($stato, $validStates, true)) {
        return ["ok" => false, "message" => "Stato PIR non valido."];
    }
    $note = trim((string)($data["note"] ?? ""));
    $applicazione = trim((string)($data["applicazione"] ?? ""));
    $rientro = trim((string)($data["rientro_eccezione"] ?? ""));
    if ($stato !== "" && $applicazione === "") {
        return ["ok" => false, "message" => "Compila il campo obbligatorio su applicazione / motivazione prima di salvare la riga."];
    }
    if ($stato === "KO" && $rientro === "") {
        return ["ok" => false, "message" => "Per uno stato KO devi indicare rientro o eccezione."];
    }
    $referente_tipo = null;
    $referente_user_id = null;
    $referente_participant_id = null;
    $referente_nome = "";
    if ($stato !== "") {
        $referente_tipo = (string)($data["referente_tipo"] ?? "");
        if ($referente_tipo === "analista") {
            $questionario = get_questionario($questionario_id);
            $referente_user_id = (int)($questionario["pir_analista_id"] ?? 0);
            $referente_nome = trim((string)($questionario["pir_analista_nome"] ?? ""));
            if ($referente_user_id <= 0 || $referente_nome === "") {
                return ["ok" => false, "message" => "Seleziona prima l'analista sicurezza della PIR."];
            }
        } elseif ($referente_tipo === "partecipante") {
            $referente_participant_id = (int)($data["referente_participant_id"] ?? 0);
            $st = get_db()->prepare(
                "SELECT p.nome, p.ruolo, m.data_riunione
                 FROM pir_meeting_participants p
                 JOIN pir_meetings m ON m.id = p.meeting_id
                 WHERE p.id = ? AND m.questionario_id = ?"
            );
            $st->execute([$referente_participant_id, $questionario_id]);
            $participant = $st->fetch();
            if (!$participant) {
                return ["ok" => false, "message" => "Seleziona un partecipante valido come referente del cambio stato."];
            }
            $referente_nome = trim($participant["nome"] . (($participant["ruolo"] ?? "") !== "" ? " - " . $participant["ruolo"] : ""));
        } else {
            return ["ok" => false, "message" => "Indica chi Ã¨ il referente del cambio stato del requisito."];
        }
    }
    $db = get_db();
    $db->prepare(
        "INSERT INTO pir_requirement_reviews
         (questionario_id,requisito_tipo,requisito_ref_id,stato,note,applicazione,rientro_eccezione,referente_tipo,referente_user_id,referente_participant_id,referente_nome)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
           stato=VALUES(stato),
           note=VALUES(note),
           applicazione=VALUES(applicazione),
           rientro_eccezione=VALUES(rientro_eccezione),
           referente_tipo=VALUES(referente_tipo),
           referente_user_id=VALUES(referente_user_id),
           referente_participant_id=VALUES(referente_participant_id),
           referente_nome=VALUES(referente_nome)"
    )->execute([
        $questionario_id,
        $tipo,
        $ref_id,
        $stato === "" ? null : $stato,
        $note,
        $applicazione,
        $rientro,
        $referente_tipo,
        $referente_user_id ?: null,
        $referente_participant_id ?: null,
        $referente_nome,
    ]);
    return ["ok" => true, "message" => "Riga PIR salvata."];
}

function security_exception_valid_date(string $date): string {
    $date = trim($date);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
}

function security_exception_status_label(string $status): string {
    return match ($status) {
        'rientrata' => 'Rientrata',
        'annullata' => 'Annullata',
        default => 'Aperta',
    };
}

function security_exception_status_tone(string $status, ?string $dataRientro = null): string {
    if ($status === 'rientrata') return 'success';
    if ($status === 'annullata') return 'secondary';
    if ($dataRientro && strtotime($dataRientro) < strtotime(date('Y-m-d'))) return 'danger';
    if ($dataRientro && strtotime($dataRientro) <= strtotime('+30 days')) return 'warning';
    return 'primary';
}

function get_security_exception(int $id): array|false {
    $st = get_db()->prepare(
        "SELECT se.*, q.nome_progetto, q.codice_progetto, q.task_jira, q.business_line
         FROM security_exceptions se
         JOIN questionari q ON q.id = se.questionario_id
         WHERE se.id = ?"
    );
    $st->execute([$id]);
    return $st->fetch();
}

function get_security_exceptions_saved(): array {
    return get_db()->query(
        "SELECT se.*, q.nome_progetto, q.codice_progetto, q.task_jira, q.business_line,
                CONCAT_WS(' ', u.nome, u.cognome) AS created_by_nome
         FROM security_exceptions se
         JOIN questionari q ON q.id = se.questionario_id
         LEFT JOIN utenti u ON u.id = se.created_by_user_id
         ORDER BY q.nome_progetto, se.data_rientro IS NULL, se.data_rientro, se.id"
    )->fetchAll();
}

function get_pir_review_row(int $questionario_id, string $tipo, int $ref_id): array|false {
    $st = get_db()->prepare(
        "SELECT * FROM pir_requirement_reviews
         WHERE questionario_id = ? AND requisito_tipo = ? AND requisito_ref_id = ?"
    );
    $st->execute([$questionario_id, $tipo, $ref_id]);
    return $st->fetch();
}

function find_pir_requirement(int $questionario_id, string $tipo, int $ref_id): array|false {
    foreach (pir_project_requirements($questionario_id) as $requirement) {
        if ((string)($requirement['pir_tipo'] ?? '') === $tipo && (int)($requirement['pir_ref_id'] ?? 0) === $ref_id) {
            return $requirement;
        }
    }
    return false;
}

function security_exception_payload_from_post(array $data): array {
    $status = (string)($data['stato'] ?? 'aperta');
    if (!in_array($status, ['aperta', 'rientrata', 'annullata'], true)) {
        $status = 'aperta';
    }
    return [
        'id' => (int)($data['id'] ?? 0),
        'questionario_id' => (int)($data['questionario_id'] ?? 0),
        'source' => in_array((string)($data['source'] ?? 'manuale'), ['pir', 'manuale'], true) ? (string)($data['source'] ?? 'manuale') : 'manuale',
        'requisito_tipo' => in_array((string)($data['requisito_tipo'] ?? 'manuale'), ['catalogo', 'default_design', 'specifico', 'manuale'], true) ? (string)($data['requisito_tipo'] ?? 'manuale') : 'manuale',
        'requisito_ref_id' => ((int)($data['requisito_ref_id'] ?? 0)) ?: null,
        'pir_review_id' => ((int)($data['pir_review_id'] ?? 0)) ?: null,
        'codice' => trim((string)($data['codice'] ?? '')),
        'titolo' => trim((string)($data['titolo'] ?? '')),
        'categoria' => trim((string)($data['categoria'] ?? '')),
        'motivo' => trim((string)($data['motivo'] ?? '')),
        'data_rientro' => security_exception_valid_date((string)($data['data_rientro'] ?? '')) ?: null,
        'approvato_da' => trim((string)($data['approvato_da'] ?? '')),
        'stato' => $status,
        'note' => trim((string)($data['note'] ?? '')),
    ];
}

function save_security_exception(array $data): array {
    $payload = security_exception_payload_from_post($data);
    if ($payload['questionario_id'] <= 0 || !get_questionario((int)$payload['questionario_id'])) {
        return ['ok' => false, 'message' => 'Progetto non valido per eccezione.'];
    }
    if ($payload['titolo'] === '') {
        return ['ok' => false, 'message' => 'Titolo eccezione obbligatorio.'];
    }
    if ($payload['source'] === 'pir') {
        $review = get_pir_review_row((int)$payload['questionario_id'], (string)$payload['requisito_tipo'], (int)$payload['requisito_ref_id']);
        if (!$review || !in_array((string)($review['stato'] ?? ''), ['KO', 'parziale'], true)) {
            return ['ok' => false, 'message' => 'Puoi creare eccezioni PIR solo da requisiti KO o parziali.'];
        }
        $payload['pir_review_id'] = (int)$review['id'];
    }

    $user = current_user();
    $db = get_db();
    if ((int)$payload['id'] > 0) {
        $db->prepare(
            "UPDATE security_exceptions
             SET questionario_id=?, source=?, requisito_tipo=?, requisito_ref_id=?, pir_review_id=?, codice=?, titolo=?, categoria=?, motivo=?, data_rientro=?, approvato_da=?, stato=?, note=?
             WHERE id=?"
        )->execute([
            $payload['questionario_id'], $payload['source'], $payload['requisito_tipo'], $payload['requisito_ref_id'], $payload['pir_review_id'],
            $payload['codice'], $payload['titolo'], $payload['categoria'], $payload['motivo'], $payload['data_rientro'], $payload['approvato_da'], $payload['stato'], $payload['note'], $payload['id'],
        ]);
        return ['ok' => true, 'message' => 'Eccezione aggiornata.'];
    }

    $db->prepare(
        "INSERT INTO security_exceptions
         (questionario_id, source, requisito_tipo, requisito_ref_id, pir_review_id, codice, titolo, categoria, motivo, data_rientro, approvato_da, stato, note, created_by_user_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
           pir_review_id=VALUES(pir_review_id), codice=VALUES(codice), titolo=VALUES(titolo), categoria=VALUES(categoria), motivo=VALUES(motivo),
           data_rientro=VALUES(data_rientro), approvato_da=VALUES(approvato_da), stato=VALUES(stato), note=VALUES(note)"
    )->execute([
        $payload['questionario_id'], $payload['source'], $payload['requisito_tipo'], $payload['requisito_ref_id'], $payload['pir_review_id'],
        $payload['codice'], $payload['titolo'], $payload['categoria'], $payload['motivo'], $payload['data_rientro'], $payload['approvato_da'], $payload['stato'], $payload['note'],
        $user ? (int)$user['id'] : null,
    ]);
    return ['ok' => true, 'message' => 'Eccezione salvata.'];
}

function delete_security_exception(int $id): void {
    get_db()->prepare('DELETE FROM security_exceptions WHERE id = ?')->execute([$id]);
}

function get_security_exception_rows_by_project(): array {
    $saved = get_security_exceptions_saved();
    $savedByPir = [];
    $rows = [];
    foreach ($saved as $exception) {
        if ((string)$exception['source'] === 'pir' && (int)($exception['requisito_ref_id'] ?? 0) > 0) {
            $savedByPir[$exception['questionario_id'] . ':' . $exception['requisito_tipo'] . ':' . $exception['requisito_ref_id']] = $exception;
        }
        $rows[] = ['kind' => 'saved', 'exception' => $exception, 'questionario' => $exception];
    }

    foreach (get_questionari() as $questionario) {
        $reviews = get_pir_reviews_map((int)$questionario['id']);
        foreach (pir_project_requirements((int)$questionario['id']) as $requirement) {
            $key = $requirement['pir_tipo'] . ':' . $requirement['pir_ref_id'];
            $review = $reviews[$key] ?? [];
            if (!in_array((string)($review['stato'] ?? ''), ['KO', 'parziale'], true)) {
                continue;
            }
            $savedKey = $questionario['id'] . ':' . $requirement['pir_tipo'] . ':' . $requirement['pir_ref_id'];
            if (isset($savedByPir[$savedKey])) {
                continue;
            }
            $rows[] = [
                'kind' => 'pir_candidate',
                'questionario' => $questionario,
                'requirement' => $requirement,
                'review' => $review,
            ];
        }
    }

    usort($rows, function (array $a, array $b): int {
        $pa = strtolower((string)($a['questionario']['nome_progetto'] ?? ''));
        $pb = strtolower((string)($b['questionario']['nome_progetto'] ?? ''));
        if ($pa !== $pb) return $pa <=> $pb;
        $da = (string)($a['exception']['data_rientro'] ?? '9999-12-31');
        $db = (string)($b['exception']['data_rientro'] ?? '9999-12-31');
        return $da <=> $db;
    });
    return $rows;
}

function get_security_exception_calendar_items(): array {
    $st = get_db()->query(
        "SELECT se.*, q.nome_progetto, q.codice_progetto, q.task_jira
         FROM security_exceptions se
         JOIN questionari q ON q.id = se.questionario_id
         WHERE se.data_rientro IS NOT NULL AND se.stato = 'aperta'
         ORDER BY se.data_rientro, q.nome_progetto, se.titolo"
    );
    return $st->fetchAll();
}
function get_pir_meetings(int $questionario_id): array {
    $st = get_db()->prepare("SELECT * FROM pir_meetings WHERE questionario_id = ? ORDER BY data_riunione DESC, id DESC");
    $st->execute([$questionario_id]);
    return $st->fetchAll();
}

function get_pir_meeting_participants(int $meeting_id): array {
    $st = get_db()->prepare("SELECT * FROM pir_meeting_participants WHERE meeting_id = ? ORDER BY id");
    $st->execute([$meeting_id]);
    return $st->fetchAll();
}

function get_pir_meeting_attachments(int $meeting_id): array {
    $st = get_db()->prepare("SELECT * FROM pir_meeting_attachments WHERE meeting_id = ? ORDER BY created_at DESC, id DESC");
    $st->execute([$meeting_id]);
    return $st->fetchAll();
}

function save_pir_meeting(int $questionario_id, string $data_riunione, string $note, array|string $participants): array {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_riunione)) {
        return ["ok" => false, "message" => "Data riunione non valida."];
    }
    $db = get_db();
    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO pir_meetings (questionario_id,data_riunione,note) VALUES (?,?,?)")
            ->execute([$questionario_id, $data_riunione, trim($note)]);
        $meeting_id = (int)$db->lastInsertId();
        save_pir_participants($meeting_id, $participants);
        $db->commit();
        return ["ok" => true, "message" => "Riunione PIR salvata.", "meeting_id" => $meeting_id];
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function update_pir_meeting(int $meeting_id, int $questionario_id, string $data_riunione, string $note, array|string $participants): array {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_riunione)) {
        return ["ok" => false, "message" => "Data riunione non valida."];
    }
    $db = get_db();
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE pir_meetings SET data_riunione=?, note=? WHERE id=? AND questionario_id=?")
            ->execute([$data_riunione, trim($note), $meeting_id, $questionario_id]);
        $db->prepare("DELETE FROM pir_meeting_participants WHERE meeting_id=?")->execute([$meeting_id]);
        save_pir_participants($meeting_id, $participants);
        $db->commit();
        return ["ok" => true, "message" => "Riunione PIR aggiornata."];
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function save_pir_participants(int $meeting_id, array|string $participants): void {
    $stmt = get_db()->prepare(
        "INSERT INTO pir_meeting_participants (meeting_id,nome,ruolo,reparto,email,telefono,partecipato)
         VALUES (?,?,?,?,?,?,?)"
    );
    foreach (normalize_pir_participants($participants) as $participant) {
        $stmt->execute([
            $meeting_id,
            $participant["nome"],
            $participant["ruolo"],
            $participant["reparto"],
            $participant["email"],
            $participant["telefono"],
            $participant["partecipato"],
        ]);
    }
}

function normalize_pir_participants(array|string $participants): array {
    if (is_string($participants)) {
        $rows = [];
        foreach (preg_split('/\R/', $participants) as $line) {
            $line = trim($line);
            if ($line === "") {
                continue;
            }
            $rows[] = [
                "nome" => $line,
                "ruolo" => "",
                "reparto" => "",
                "email" => "",
                "telefono" => "",
                "partecipato" => 1,
            ];
        }
        return $rows;
    }

    $names = $participants["nome"] ?? [];
    $roles = $participants["ruolo"] ?? [];
    $departments = $participants["reparto"] ?? [];
    $emails = $participants["email"] ?? [];
    $phones = $participants["telefono"] ?? [];
    $attended = $participants["partecipato"] ?? [];
    $count = max(
        count((array)$names),
        count((array)$roles),
        count((array)$departments),
        count((array)$emails),
        count((array)$phones),
        count((array)$attended)
    );

    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $name = trim((string)($names[$i] ?? ""));
        if ($name === "") {
            continue;
        }
        $rows[] = [
            "nome" => $name,
            "ruolo" => trim((string)($roles[$i] ?? "")),
            "reparto" => trim((string)($departments[$i] ?? "")),
            "email" => trim((string)($emails[$i] ?? "")),
            "telefono" => trim((string)($phones[$i] ?? "")),
            "partecipato" => ((string)($attended[$i] ?? "1")) === "0" ? 0 : 1,
        ];
    }
    return $rows;
}

function delete_pir_meeting(int $meeting_id, int $questionario_id): void {
    get_db()->prepare("DELETE FROM pir_meetings WHERE id = ? AND questionario_id = ?")->execute([$meeting_id, $questionario_id]);
}

function add_pir_link_attachment(int $meeting_id, string $title, string $url): array {
    $url = trim($url);
    if (!is_safe_public_url($url)) {
        return ["ok" => false, "message" => "Link allegato non valido."];
    }
    $title = trim($title);
    if ($title === "") {
        $title = fetch_url_title($url) ?: $url;
    }
    get_db()->prepare(
        "INSERT INTO pir_meeting_attachments (meeting_id,tipo,titolo,url) VALUES (?, 'link', ?, ?)"
    )->execute([$meeting_id, $title, $url]);
    return ["ok" => true, "message" => "Link allegato salvato."];
}

function add_pir_file_attachment(int $meeting_id, array $file, string $title = ""): array {
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ["ok" => false, "message" => "Nessun file selezionato."];
    }
    if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ["ok" => false, "message" => "Errore durante il caricamento del file."];
    }
    $maxBytes = (int)(getenv("REQ_UPLOAD_MAX_BYTES") ?: 26214400);
    if ((int)($file["size"] ?? 0) <= 0 || (int)$file["size"] > $maxBytes) {
        return ["ok" => false, "message" => "File non valido o superiore alla dimensione massima consentita."];
    }
    $original = (string)$file["name"];
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowedExtensions = [
        "pdf","doc","docx","xls","xlsx","ppt","pptx","txt",
        "png","jpg","jpeg","gif","webp",
        "mp3","wav","mp4","webm","mov","avi",
    ];
    if (!in_array($extension, $allowedExtensions, true)) {
        return ["ok" => false, "message" => "Tipo file non consentito."];
    }
    $tmpPath = (string)($file["tmp_name"] ?? "");
    $detectedMime = "";
    if (is_file($tmpPath)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string)$finfo->file($tmpPath);
    }
    $allowedMimePrefixes = ["image/", "audio/", "video/"];
    $allowedMimes = [
        "application/pdf",
        "text/plain",
        "application/msword",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "application/vnd.ms-powerpoint",
        "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        "application/zip",
        "application/octet-stream",
    ];
    $mimeAllowed = in_array($detectedMime, $allowedMimes, true);
    foreach ($allowedMimePrefixes as $prefix) {
        if (str_starts_with($detectedMime, $prefix)) {
            $mimeAllowed = true;
            break;
        }
    }
    if (!$mimeAllowed) {
        return ["ok" => false, "message" => "Contenuto file non consentito."];
    }
    $baseDir = __DIR__ . "/../public/uploads/pir";
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }
    $htaccess = $baseDir . "/.htaccess";
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Options -Indexes\nphp_flag engine off\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phar\n<FilesMatch \"\\.(php|phtml|phar)$\">\nRequire all denied\n</FilesMatch>\n");
    }
    $safeName = "pir_" . $meeting_id . "_" . bin2hex(random_bytes(8)) . ($extension ? "." . preg_replace('/[^A-Za-z0-9]/', '', $extension) : "");
    $target = $baseDir . "/" . $safeName;
    if (!move_uploaded_file((string)$file["tmp_name"], $target)) {
        return ["ok" => false, "message" => "Impossibile salvare il file allegato."];
    }
    get_db()->prepare(
        "INSERT INTO pir_meeting_attachments (meeting_id,tipo,titolo,file_path,original_name,mime_type,size_bytes)
         VALUES (?, 'file', ?, ?, ?, ?, ?)"
    )->execute([
        $meeting_id,
        trim($title) ?: $original,
        "public/uploads/pir/" . $safeName,
        $original,
        $detectedMime ?: (string)($file["type"] ?? ""),
        (int)($file["size"] ?? 0),
    ]);
    return ["ok" => true, "message" => "File allegato salvato."];
}

function delete_pir_attachment(int $attachment_id, int $questionario_id): void {
    $st = get_db()->prepare(
        "SELECT a.*
         FROM pir_meeting_attachments a
         JOIN pir_meetings m ON m.id = a.meeting_id
         WHERE a.id = ? AND m.questionario_id = ?"
    );
    $st->execute([$attachment_id, $questionario_id]);
    $attachment = $st->fetch();
    if (!$attachment) {
        return;
    }

    if (($attachment["tipo"] ?? "") === "file" && !empty($attachment["file_path"])) {
        $baseDir = realpath(__DIR__ . "/../public/uploads/pir");
        $target = realpath(__DIR__ . "/../" . (string)$attachment["file_path"]);
        if ($baseDir && $target && str_starts_with($target, $baseDir . DIRECTORY_SEPARATOR) && is_file($target)) {
            @unlink($target);
        }
    }

    get_db()->prepare("DELETE FROM pir_meeting_attachments WHERE id = ?")->execute([$attachment_id]);
}

function fetch_url_title(string $url): string {
    $url = trim($url);
    if (!is_safe_public_url($url)) {
        return "";
    }

    $oembedTitle = fetch_oembed_title($url);
    if ($oembedTitle !== "") {
        return $oembedTitle;
    }

    $html = http_get_text($url, 2097152);
    if ($html === "") {
        return "";
    }
    return extract_html_title($html);
}

function is_safe_public_url(string $url): bool {
    if ($url === "" || !filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $parts = parse_url($url);
    $scheme = strtolower((string)($parts["scheme"] ?? ""));
    if (!in_array($scheme, ["http", "https"], true)) {
        return false;
    }
    $host = strtolower(trim((string)($parts["host"] ?? ""), "[]"));
    if ($host === "" || $host === "localhost" || str_ends_with($host, ".localhost")) {
        return false;
    }
    $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
    if (!$ips) {
        return false;
    }
    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
    }
    return true;
}

function http_get_text(string $url, int $limit = 262144): string {
    if (!is_safe_public_url($url)) {
        return "";
    }
    $context = stream_context_create([
        "http" => [
            "timeout" => 6,
            "follow_location" => 0,
            "max_redirects" => 0,
            "header" => "User-Agent: Mozilla/5.0 (compatible; RequisitiSEC-PIR/1.0)\r\nAccept: text/html,application/json,*/*;q=0.8\r\nAccept-Language: it-IT,it;q=0.9,en;q=0.8\r\n",
        ],
        "ssl" => [
            "verify_peer" => true,
            "verify_peer_name" => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context, 0, $limit);
    return is_string($body) ? $body : "";
}

function fetch_oembed_title(string $url): string {
    $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ""));
    $endpoint = "";
    if ($host === "youtu.be" || str_ends_with($host, ".youtu.be") || $host === "youtube.com" || str_ends_with($host, ".youtube.com")) {
        $endpoint = "https://www.youtube.com/oembed?format=json&url=" . rawurlencode($url);
    } elseif ($host === "vimeo.com" || str_ends_with($host, ".vimeo.com")) {
        $endpoint = "https://vimeo.com/api/oembed.json?url=" . rawurlencode($url);
    }

    if ($endpoint === "") {
        return "";
    }

    $json = http_get_text($endpoint, 65536);
    if ($json === "") {
        return "";
    }
    $payload = json_decode($json, true);
    if (!is_array($payload) || empty($payload["title"])) {
        return "";
    }
    return normalize_auto_title((string)$payload["title"]);
}

function clean_html_title(string $title): string {
    $decoded = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string)preg_replace('/\s+/u', ' ', $decoded));
}

function normalize_auto_title(string $title): string {
    $title = clean_html_title($title);
    $badTitles = [
        "verify to continue",
        "just a moment",
        "attention required",
        "access denied",
        "captcha",
    ];
    $lower = strtolower($title);
    foreach ($badTitles as $badTitle) {
        if ($lower === $badTitle || str_contains($lower, $badTitle)) {
            return "";
        }
    }
    return $title;
}

function extract_html_title(string $html): string {
    if (preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $h1Matches)) {
        foreach ($h1Matches[1] as $h1Html) {
            if (preg_match('/<[^>]+\btitle=["\']([^"\']+)["\']/is', $h1Html, $titleAttribute)) {
                $title = normalize_auto_title($titleAttribute[1]);
                if ($title !== "") {
                    return $title;
                }
            }
            $title = normalize_auto_title($h1Html);
            if ($title !== "") {
                return $title;
            }
        }
    }

    if (preg_match('/<meta[^>]+(?:property|name)=["\'](?:og:title|twitter:title)["\'][^>]+content=["\']([^"\']+)["\']/is', $html, $matches)
        || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\'](?:og:title|twitter:title)["\']/is', $html, $matches)) {
        $title = normalize_auto_title($matches[1]);
        if ($title !== "") {
            return $title;
        }
    }

    if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonLdMatches)) {
        foreach ($jsonLdMatches[1] as $jsonLd) {
            $title = extract_json_title(json_decode(html_entity_decode($jsonLd, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true));
            if ($title !== "") {
                return $title;
            }
        }
    }

    if (!preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
        return "";
    }
    return normalize_auto_title($matches[1]);
}

function extract_json_title(mixed $payload): string {
    if (!is_array($payload)) {
        return "";
    }
    foreach (["headline", "name", "title"] as $key) {
        if (isset($payload[$key]) && is_scalar($payload[$key])) {
            $title = normalize_auto_title((string)$payload[$key]);
            if ($title !== "") {
                return $title;
            }
        }
    }
    foreach ($payload as $value) {
        $title = extract_json_title($value);
        if ($title !== "") {
            return $title;
        }
    }
    return "";
}


