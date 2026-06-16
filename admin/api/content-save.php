<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/generate-js.php';

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
if (!isset($types[$type]) || !in_array($type, ['articles', 'news'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid type']);
    exit;
}

$slug = trim($payload['slug'] ?? '');
$item = $payload['item'] ?? null;
if ($slug === '' || !is_array($item)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing data']);
    exit;
}

$cfg = $types[$type];
$store = json_read($cfg['file']);
$key = $cfg['itemsKey'];
$existing = $store[$key][$slug] ?? [];
$item['slug'] = $slug;
$item['dateModified'] = date('Y-m-d');
if (!isset($item['visible'])) {
    $item['visible'] = $existing['visible'] ?? true;
}
$store[$key][$slug] = array_merge($existing, $item);
json_write($cfg['file'], $store);

if (!empty($payload['publish'])) {
    generate_all_js();
}

echo json_encode(['ok' => true]);
