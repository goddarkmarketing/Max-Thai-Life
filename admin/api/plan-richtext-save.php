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

$slug = trim((string) ($payload['slug'] ?? ''));
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing slug']);
    exit;
}

$details = json_read('plans-detail.json');
if (!isset($details['items']) || !is_array($details['items'])) {
    $details['items'] = [];
}
if (!isset($details['items'][$slug]) || !is_array($details['items'][$slug])) {
    $details['items'][$slug] = [];
}

$bodyHtml = (string) ($payload['bodyHtml'] ?? '');

$details['items'][$slug]['editor'] = 'richtext';
$details['items'][$slug]['bodyHtml'] = $bodyHtml;
if (array_key_exists('ctaTitle', $payload)) {
    $details['items'][$slug]['ctaTitle'] = trim((string) $payload['ctaTitle']);
}
if (array_key_exists('ctaLead', $payload)) {
    $details['items'][$slug]['ctaLead'] = trim((string) $payload['ctaLead']);
}
json_write('plans-detail.json', $details);

if (!empty($payload['publish'])) {
    generate_all_js();
}

echo json_encode(['ok' => true]);
