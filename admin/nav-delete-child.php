<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: site-nav.php');
    exit;
}

$parentIndex = (int) admin_post('parent_index', '-1');
$childIndex = (int) admin_post('child_index', '-1');
$data = json_read('site.json');
$navigation = $data['navigation'] ?? admin_default_navigation();

try {
    $navigation = admin_nav_delete_child($navigation, $parentIndex, $childIndex);
    $data['navigation'] = $navigation;
    admin_nav_publish_site($data);
    admin_flash('success', 'ลบเมนูย่อยแล้ว');
} catch (RuntimeException $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: site-nav.php');
exit;
