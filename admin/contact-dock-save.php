<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$return = 'site-contact-dock.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: ' . $return);
    exit;
}

$section = admin_post('section');
$index = admin_post('index', 'new');
$return = admin_post_return_page($return);
$data = json_read('site.json');

if ($section === 'contactDockSettings') {
    $dock = admin_normalize_contact_dock($data['contactDock'] ?? []);
    $dock['enabled'] = isset($_POST['enabled']);
    $data['contactDock'] = $dock;
    admin_footer_publish_site($data);
    admin_flash('success', 'บันทึกและเผยแพร่ปุ่มลอยแล้ว');
    header('Location: ' . $return);
    exit;
}

if ($section === 'contactDock') {
    try {
        $data['contactDock'] = admin_contact_dock_save_item($data['contactDock'] ?? [], $index, $_POST);
        admin_footer_publish_site($data);
        admin_flash('success', 'บันทึกและเผยแพร่ปุ่มลอยแล้ว');
    } catch (RuntimeException $e) {
        admin_flash('error', $e->getMessage());
    }
    header('Location: ' . $return);
    exit;
}

admin_flash('error', 'คำขอไม่ถูกต้อง');
header('Location: ' . $return);
exit;
