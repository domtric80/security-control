<?php
require_once __DIR__ . '/../includes/functions.php';
require_permission('pir', 'read');

header('Content-Type: application/json; charset=utf-8');

$url = trim((string)($_GET['url'] ?? ''));
if (!is_safe_public_url($url)) {
    echo json_encode(['ok' => false, 'title' => '', 'message' => 'URL non valido.']);
    exit;
}

$title = fetch_url_title($url);
echo json_encode([
    'ok' => $title !== '',
    'title' => $title,
    'message' => $title !== '' ? 'Titolo recuperato.' : 'Titolo non disponibile.',
], JSON_UNESCAPED_UNICODE);
