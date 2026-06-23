<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$return = 'site-footer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: ' . $return);
    exit;
}

$section = admin_post('section');
$col = (int) admin_post('col', '0');
$index = admin_post('index', 'new');
$return = admin_post_return_page($return);

$data = json_read('site.json');
$footer = admin_normalize_footer($data['footer'] ?? []);

try {
    $footer = admin_footer_save_item($footer, $section, $col, $index, $_POST);
    $data['footer'] = $footer;
    admin_footer_publish_site($data);
    admin_flash('success', 'บันทึกและเผยแพร่ Footer แล้ว');
} catch (RuntimeException $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: ' . $return);
exit;
