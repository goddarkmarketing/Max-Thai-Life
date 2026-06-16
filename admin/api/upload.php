<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/image-specs.php';

admin_require_login();

$specs = require __DIR__ . '/../includes/image-specs.php';
$allowed = array_keys($specs);
$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF invalid']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ไม่พบไฟล์ที่อัปโหลด']);
    exit;
}

$specKey = $_POST['spec'] ?? 'article_cover';
if (!in_array($specKey, $allowed, true)) {
    $specKey = 'article_cover';
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'รองรับเฉพาะ JPG, PNG, WEBP, GIF, SVG']);
    exit;
}

if ($file['size'] > 8 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ไฟล์ใหญ่เกิน 8 MB']);
    exit;
}

$basePath = $specs[$specKey]['path'] ?? 'images/uploads/';
if (str_ends_with($basePath, '/')) {
    $targetDir = ROOT_PATH . '/' . rtrim(str_replace('\\', '/', $basePath), '/');
} else {
    $targetDir = ROOT_PATH . '/' . str_replace('\\', '/', dirname($basePath));
}

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$filename = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $targetDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'บันทึกไฟล์ไม่สำเร็จ']);
    exit;
}

$relative = str_replace('\\', '/', substr($dest, strlen(ROOT_PATH) + 1));

echo json_encode([
    'ok' => true,
    'path' => $relative,
    'hint' => $specs[$specKey]['hint'] ?? '',
]);
