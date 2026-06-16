<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: dashboard.php');
    exit;
}

$types = admin_content_types();
$type = $_POST['type'] ?? '';
$slug = trim($_POST['slug'] ?? '');

if (!isset($types[$type]) || $slug === '') {
    admin_flash('error', 'ไม่พบรายการที่จะลบ');
    header('Location: dashboard.php');
    exit;
}

$cfg = $types[$type];
$store = json_read($cfg['file']);
$itemsKey = $cfg['itemsKey'];

if (!isset($store[$itemsKey][$slug])) {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: content-list.php?type=' . urlencode($type));
    exit;
}

unset($store[$itemsKey][$slug]);

if (isset($store['list'])) {
    $store['list'] = array_values(array_filter($store['list'], fn($s) => $s !== $slug));
}
if (isset($store['home'])) {
    $store['home'] = array_values(array_filter($store['home'], fn($s) => $s !== $slug));
}

json_write($cfg['file'], $store);
admin_delete_content_shell($type, $slug);

admin_flash('success', 'ลบรายการแล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
header('Location: content-list.php?type=' . urlencode($type));
exit;
