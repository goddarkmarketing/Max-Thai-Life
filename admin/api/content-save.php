<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/generate-js.php';
require_once __DIR__ . '/../includes/content-blocks.php';

admin_require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

if (!admin_verify_csrf($payload['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF invalid']);
    exit;
}

$type = $payload['type'] ?? '';
$types = admin_content_types();
if (!isset($types[$type]) || !in_array($type, ['articles', 'news', 'careers', 'claims'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid type']);
    exit;
}

$slug = trim($payload['slug'] ?? '');
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing slug']);
    exit;
}

$cfg = $types[$type];
$store = json_read($cfg['file']);
$key = $cfg['itemsKey'];
$existing = $store[$key][$slug] ?? null;
if ($existing === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Item not found']);
    exit;
}

if (!empty($payload['visual']) && isset($payload['pageData']) && is_array($payload['pageData'])) {
    $merged = admin_content_page_data_to_item($payload['pageData'], $existing);
    $existing = array_merge($existing, $merged);
} else {
    $item = $payload['item'] ?? null;
    if (!is_array($item)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing data']);
        exit;
    }
    $existing = array_merge($existing, $item);
}

$existing['slug'] = $slug;
$existing['dateModified'] = date('Y-m-d');
if (!isset($existing['visible'])) {
    $existing['visible'] = true;
}

$store[$key][$slug] = $existing;
json_write($cfg['file'], $store);

$publish = !empty($payload['publish']) || !empty($payload['visual']);
if ($publish) {
    generate_all_js();
}

echo json_encode(['ok' => true]);
