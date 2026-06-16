<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/generate-js.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: content-list.php');
    exit;
}

$type = admin_post('type');
$slug = admin_post('slug');
$types = admin_content_types();
if (!isset($types[$type])) {
    admin_flash('error', 'ประเภทไม่ถูกต้อง');
    header('Location: content-list.php');
    exit;
}

$cfg = $types[$type];
$store = json_read($cfg['file']);
$items = $store[$cfg['itemsKey']] ?? [];
if (!isset($items[$slug])) {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: content-list.php?type=' . urlencode($type));
    exit;
}

$newSlug = admin_duplicate_slug($slug, array_keys($items));
$copy = $items[$slug];
$copy['slug'] = $newSlug;
$copy['title'] = ($copy['title'] ?? $slug) . ' (สำเนา)';
$copy['visible'] = false;
$copy['dateModified'] = date('Y-m-d');
$items[$newSlug] = $copy;
$store[$cfg['itemsKey']] = $items;
if (isset($store['list']) && !in_array($newSlug, $store['list'], true)) {
    array_unshift($store['list'], $newSlug);
}
json_write($cfg['file'], $store);
admin_create_content_shell($type, $newSlug);
generate_all_js();
admin_flash('success', 'สำเนารายการแล้ว — สถานะ: ซ่อนจากเว็บ');
header('Location: content-edit.php?type=' . urlencode($type) . '&id=' . urlencode($newSlug));
exit;
