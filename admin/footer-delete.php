<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$return = admin_post_return_page('site-footer.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: ' . $return);
    exit;
}

$section = admin_post('section');
$col = (int) admin_post('col', '-1');
$index = (int) admin_post('index', '-1');
$data = json_read('site.json');
$footer = admin_normalize_footer($data['footer'] ?? []);
$removed = false;

if ($section === 'topCta' && isset($footer['topCta'][$index])) {
    array_splice($footer['topCta'], $index, 1);
    $removed = true;
} elseif ($section === 'bottom' && isset($footer['bottom']['links'][$index])) {
    array_splice($footer['bottom']['links'], $index, 1);
    $removed = true;
} elseif ($section === 'link' && isset($footer['columns'][$col]['links'][$index])) {
    array_splice($footer['columns'][$col]['links'], $index, 1);
    $removed = true;
}

if (!$removed) {
    admin_flash('error', 'ไม่พบรายการที่จะลบ');
    header('Location: ' . $return);
    exit;
}

$data['footer'] = $footer;
admin_footer_publish_site($data);
admin_flash('success', 'ลบรายการและเผยแพร่ขึ้นเว็บแล้ว');
header('Location: ' . $return);
exit;
