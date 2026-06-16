<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../admin/includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

if (!empty($input['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$topic = trim((string) ($input['topic'] ?? ''));
$name = trim((string) ($input['name'] ?? ''));
$phone = preg_replace('/\D/', '', (string) ($input['phone'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));
$source = trim((string) ($input['source'] ?? 'contact'));

$allowedTopics = ['insurance', 'quote', 'agent', 'inquiry'];
if (!in_array($topic, $allowedTopics, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'กรุณาเลือกหัวข้อ']);
    exit;
}

if ($name === '' || strlen($name) > 120) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'กรุณากรอกชื่อ-นามสกุล']);
    exit;
}

if ($phone === '' || strlen($phone) < 9 || strlen($phone) > 15) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง']);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'รูปแบบอีเมลไม่ถูกต้อง']);
    exit;
}

if (strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ข้อความยาวเกินไป']);
    exit;
}

try {
    admin_save_lead([
        'id' => bin2hex(random_bytes(8)),
        'topic' => $topic,
        'topicLabel' => admin_inquiry_topic_label($topic),
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'message' => $message,
        'source' => in_array($source, ['home', 'contact'], true) ? $source : 'contact',
        'status' => 'new',
        'createdAt' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    echo json_encode(['ok' => true, 'message' => 'ส่งข้อความแล้ว — เราจะติดต่อกลับโดยเร็ว']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'บันทึกไม่สำเร็จ กรุณาลองใหม่']);
}
