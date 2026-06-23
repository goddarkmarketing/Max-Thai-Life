<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$return = admin_post_return_page('site-contact-dock.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: ' . $return);
    exit;
}

$index = (int) admin_post('index', '-1');
$data = json_read('site.json');

if (!isset($data['contactDock']['items'][$index])) {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: ' . $return);
    exit;
}

$dock = admin_normalize_contact_dock($data['contactDock']);
$dock['items'][$index]['visible'] = !admin_footer_link_visible($dock['items'][$index]);
$data['contactDock'] = $dock;
admin_footer_publish_site($data);
admin_flash('success', 'อัปเดตสถานะและเผยแพร่ขึ้นเว็บแล้ว');
header('Location: ' . $return);
exit;
