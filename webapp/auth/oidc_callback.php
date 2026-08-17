<?php
require_once __DIR__ . '/../includes/functions.php';

$result = oidc_handle_callback((string)($_GET['code'] ?? ''), (string)($_GET['state'] ?? ''));
flash($result['ok'] ? 'success' : 'error', $result['message']);
redirect($result['ok'] ? first_allowed_url() : APP_BASE_URL . '/login.php');
