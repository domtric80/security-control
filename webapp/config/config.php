<?php
$debugEnabled = filter_var(getenv('REQ_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL);
ini_set('display_errors', $debugEnabled ? '1' : '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('html_errors', '0');
ini_set('zend.exception_ignore_args', '1');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $exception) use ($debugEnabled): void {
    $errorId = bin2hex(random_bytes(6));
    error_log("[REQ-$errorId] " . (string)$exception);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Errore applicativo. Riferimento: REQ-$errorId" . PHP_EOL);
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $safeErrorId = htmlspecialchars("REQ-$errorId", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    if ($debugEnabled) {
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo "<h1>Errore applicativo</h1><p>$message</p><p>Riferimento: $safeErrorId</p>";
    } else {
        echo "<h1>Errore applicativo</h1><p>Si è verificato un errore inatteso. Riferimento: $safeErrorId</p>";
    }
    exit;
});

// Database configuration - edit these values to match your environment.
define('DB_DRIVER', getenv('REQ_DB_DRIVER') ?: 'mysql'); // 'mysql' or 'pgsql'
define('DB_HOST', getenv('REQ_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('REQ_DB_PORT') ?: '3306');
define('DB_NAME', getenv('REQ_DB_NAME') ?: 'requisiti');
define('DB_USER', getenv('REQ_DB_USER') ?: 'root');
define('DB_PASS', getenv('REQ_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Application settings.
define('APP_NAME', 'Security Control');
$appBaseUrl = getenv('REQ_APP_BASE_URL');
define('APP_BASE_URL', $appBaseUrl === false ? '/webapp' : rtrim($appBaseUrl, '/'));
define('APP_ENV', getenv('REQ_APP_ENV') ?: 'local');

// Legacy emergency admin credentials. Disabled by default: use DB/LDAP/OIDC users.
define('LEGACY_ADMIN_ENABLED', filter_var(getenv('REQ_LEGACY_ADMIN_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL));
define('ADMIN_USER', getenv('REQ_ADMIN_USER') ?: '');
define('ADMIN_PASS', getenv('REQ_ADMIN_PASS') ?: '');

// External identity providers.
define('LDAP_ENABLED', filter_var(getenv('REQ_LDAP_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL));
define('LDAP_URI', getenv('REQ_LDAP_URI') ?: '');
define('LDAP_HOST', getenv('REQ_LDAP_HOST') ?: '');
define('LDAP_PORT', getenv('REQ_LDAP_PORT') ?: '');
define('LDAP_ENCRYPTION', getenv('REQ_LDAP_ENCRYPTION') ?: 'none');
define('LDAP_PROTOCOL_VERSION', getenv('REQ_LDAP_PROTOCOL_VERSION') ?: '3');
define('LDAP_BASE_DN', getenv('REQ_LDAP_BASE_DN') ?: '');
define('LDAP_BIND_DN', getenv('REQ_LDAP_BIND_DN') ?: '');
define('LDAP_BIND_PASSWORD', getenv('REQ_LDAP_BIND_PASSWORD') ?: '');
define('LDAP_USER_FILTER', getenv('REQ_LDAP_USER_FILTER') ?: '(sAMAccountName={username})');
define('LDAP_ATTR_USERNAME', getenv('REQ_LDAP_ATTR_USERNAME') ?: 'sAMAccountName');
define('LDAP_ATTR_EMAIL', getenv('REQ_LDAP_ATTR_EMAIL') ?: 'mail');
define('LDAP_ATTR_FIRST_NAME', getenv('REQ_LDAP_ATTR_FIRST_NAME') ?: 'givenName');
define('LDAP_ATTR_LAST_NAME', getenv('REQ_LDAP_ATTR_LAST_NAME') ?: 'sn');
define('LDAP_DEFAULT_ROLE', getenv('REQ_LDAP_DEFAULT_ROLE') ?: 'utente');

define('OIDC_ENABLED', filter_var(getenv('REQ_OIDC_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL));
define('OIDC_ISSUER', rtrim(getenv('REQ_OIDC_ISSUER') ?: '', '/'));
define('OIDC_CLIENT_ID', getenv('REQ_OIDC_CLIENT_ID') ?: '');
define('OIDC_CLIENT_SECRET', getenv('REQ_OIDC_CLIENT_SECRET') ?: '');
define('OIDC_REDIRECT_URI', getenv('REQ_OIDC_REDIRECT_URI') ?: '');
define('OIDC_SCOPE', getenv('REQ_OIDC_SCOPE') ?: 'openid profile email');
define('OIDC_DEFAULT_ROLE', getenv('REQ_OIDC_DEFAULT_ROLE') ?: 'utente');

define('OLLAMA_BASE_URL', rtrim(getenv('REQ_OLLAMA_BASE_URL') ?: 'http://host.docker.internal:11434', '/'));
define('OLLAMA_MODEL', getenv('REQ_OLLAMA_MODEL') ?: '');
define('OLLAMA_TIMEOUT_SECONDS', (int)(getenv('REQ_OLLAMA_TIMEOUT_SECONDS') ?: 300));

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.cookie_httponly', '1');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$secureCookie = getenv('REQ_SESSION_SECURE');
$secureCookie = $secureCookie === false ? $isHttps : filter_var($secureCookie, FILTER_VALIDATE_BOOL);
session_name('REQSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
