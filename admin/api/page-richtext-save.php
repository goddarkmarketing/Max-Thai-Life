<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/landing-pages.php';
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

$pageKey = trim((string) ($payload['page'] ?? ''));
if (!in_array($pageKey, admin_landing_page_keys(), true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid page']);
    exit;
}

$bodyHtml = (string) ($payload['bodyHtml'] ?? '');

$data = json_read('pages.json');
if (!isset($data[$pageKey]) || !is_array($data[$pageKey])) {
    $data[$pageKey] = [];
}
$data[$pageKey]['editor'] = 'richtext';
$data[$pageKey]['bodyHtml'] = $bodyHtml;
json_write('pages.json', $data);

if (!empty($payload['publish'])) {
    generate_all_js();
}

echo json_encode(['ok' => true]);
