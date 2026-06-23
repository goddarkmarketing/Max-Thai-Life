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

$kind = admin_post('kind', 'main');
$data = json_read('site.json');
$navigation = $data['navigation'] ?? admin_default_navigation();

try {
    if ($kind === 'child') {
        $parentIndex = (int) admin_post('index', '-1');
        $childIndex = admin_post('child_index', 'new');
        $navigation = admin_nav_patch_child($navigation, $parentIndex, $childIndex, $_POST);
    } else {
        $index = (int) admin_post('index', '-1');
        $navigation = admin_nav_patch_item($navigation, $index, $_POST);
    }
    $data['navigation'] = $navigation;
    admin_nav_publish_site($data);
    admin_flash('success', 'บันทึกและเผยแพร่เมนูแล้ว');
} catch (RuntimeException $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: site-nav.php');
exit;
