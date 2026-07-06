<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../admin/includes/analytics.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

if (!empty($input['website'])) {
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = trim((string) ($input['type'] ?? ''));
$id = trim((string) ($input['id'] ?? ''));
$userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

try {
    $counted = admin_analytics_track($type, $id, $userAgent, $ip);
    $views = admin_analytics_count($type, $id);
    echo json_encode([
        'ok' => true,
        'counted' => $counted,
        'views' => $views,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'track failed'], JSON_UNESCAPED_UNICODE);
}
