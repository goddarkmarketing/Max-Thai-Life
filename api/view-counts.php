<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../admin/includes/analytics.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = trim((string) ($_GET['type'] ?? ''));
$id = trim((string) ($_GET['id'] ?? ''));

if ($id !== '') {
    echo json_encode([
        'ok' => true,
        'type' => $type,
        'id' => $id,
        'views' => admin_analytics_count($type, $id),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!admin_analytics_valid_type($type)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid type'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'type' => $type,
    'views' => admin_analytics_counts_for_type($type),
], JSON_UNESCAPED_UNICODE);
