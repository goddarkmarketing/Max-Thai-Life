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

$types = admin_content_types();
$type = $_GET['type'] ?? ($payload['type'] ?? '');
if (!isset($types[$type]) || !in_array($type, ['articles', 'news', 'careers'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid type']);
    exit;
}
$cfg = $types[$type];

$slug = trim((string) ($payload['slug'] ?? ''));
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing slug']);
    exit;
}

$store = json_read($cfg['file']);
$itemsKey = $cfg['itemsKey'];
if (!isset($store[$itemsKey][$slug]) || !is_array($store[$itemsKey][$slug])) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Item not found']);
    exit;
}

$bodyHtml = (string) ($payload['bodyHtml'] ?? '');

$store[$itemsKey][$slug]['editor'] = 'richtext';
$store[$itemsKey][$slug]['bodyHtml'] = $bodyHtml;
json_write($cfg['file'], $store);

if (!empty($payload['publish'])) {
    generate_all_js();
}

echo json_encode(['ok' => true]);
