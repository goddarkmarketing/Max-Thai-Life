<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$isAjax = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
    || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

function nav_reorder_response(bool $ok, string $message, int $code = 200): void
{
    global $isAjax;
    if ($isAjax) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    admin_flash($ok ? 'success' : 'error', $message);
    header('Location: site-nav.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    nav_reorder_response(false, 'คำขอไม่ถูกต้อง', 403);
}

$kind = admin_post('kind', 'main');
$order = $_POST['order'] ?? [];
if (!is_array($order) || $order === []) {
    nav_reorder_response(false, 'ไม่มีลำดับที่ส่งมา', 400);
}

$data = json_read('site.json');
$navigation = $data['navigation'] ?? admin_default_navigation();

try {
    if ($kind === 'child') {
        $parentIndex = (int) admin_post('parent_index', '-1');
        $navigation = admin_nav_reorder_children($navigation, $parentIndex, $order);
    } else {
        $navigation = admin_nav_reorder($navigation, $order);
    }
    $data['navigation'] = $navigation;
    admin_nav_publish_site($data);
    nav_reorder_response(true, 'จัดลำดับเมนูแล้ว');
} catch (RuntimeException $e) {
    nav_reorder_response(false, $e->getMessage(), 400);
}
