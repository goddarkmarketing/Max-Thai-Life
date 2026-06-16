<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$types = admin_content_types();
$type = $_POST['type'] ?? '';
if (!isset($types[$type]) || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: dashboard.php');
    exit;
}

$cfg = $types[$type];
$store = json_read($cfg['file']);
$orderText = admin_post('order_text');
$slugs = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $orderText) ?: [])));

$store['list'] = $slugs;
json_write($cfg['file'], $store);
admin_flash('success', 'บันทึกลำดับแล้ว');
header('Location: content-list.php?type=' . urlencode($type));
exit;
