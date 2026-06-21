<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: site-footer.php');
    exit;
}

$section = admin_post('section');
$col = (int) admin_post('col', '0');
$index = admin_post('index', 'new');

$data = json_read('site.json');
$footer = admin_normalize_footer($data['footer'] ?? []);

try {
    $footer = admin_footer_save_item($footer, $section, $col, $index, $_POST);
    $data['footer'] = $footer;
    json_write('site.json', $data);
    admin_flash('success', 'บันทึก Footer แล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
} catch (RuntimeException $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: site-footer.php');
exit;
