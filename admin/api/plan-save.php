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

$slug = trim($payload['slug'] ?? '');
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing slug']);
    exit;
}

$detail = $payload['detail'] ?? null;
if (!is_array($detail)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing detail']);
    exit;
}

$details = json_read('plans-detail.json');
$details['items'][$slug] = $detail;
json_write('plans-detail.json', $details);

$card = $payload['card'] ?? null;
if (is_array($card)) {
    $plans = json_read('plans.json');
    $items = $plans['items'] ?? [];
    foreach ($items as $i => $item) {
        $href = $item['href'] ?? '';
        $itemSlug = preg_replace('#^plans/|\.html$#', '', $href);
        if ($itemSlug === $slug) {
            $items[$i] = array_merge($item, $card);
            break;
        }
    }
    $plans['items'] = $items;
    json_write('plans.json', $plans);
}

if (!empty($payload['publish'])) {
    generate_all_js();
}

echo json_encode(['ok' => true, 'message' => 'บันทึกแล้ว']);
