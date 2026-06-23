<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$isAjax = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
    || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

function contact_dock_reorder_response(bool $ok, string $message, int $code = 200): void
{
    global $isAjax;
    if ($isAjax) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    admin_flash($ok ? 'success' : 'error', $message);
    header('Location: site-contact-dock.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    contact_dock_reorder_response(false, 'คำขอไม่ถูกต้อง', 403);
}

$order = $_POST['order'] ?? [];
if (!is_array($order) || $order === []) {
    contact_dock_reorder_response(false, 'ไม่มีลำดับที่ส่งมา', 400);
}

$data = json_read('site.json');
$dock = admin_normalize_contact_dock($data['contactDock'] ?? []);

try {
    $data['contactDock'] = admin_contact_dock_reorder($dock, $order);
    admin_footer_publish_site($data);
    contact_dock_reorder_response(true, 'จัดลำดับปุ่มลอยแล้ว');
} catch (RuntimeException $e) {
    contact_dock_reorder_response(false, $e->getMessage(), 400);
}
