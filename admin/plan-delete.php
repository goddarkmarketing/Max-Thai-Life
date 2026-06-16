<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: plans-list.php');
    exit;
}

$slug = trim($_POST['slug'] ?? '');
if ($slug === '') {
    admin_flash('error', 'ไม่พบแผน');
    header('Location: plans-list.php');
    exit;
}

$plans = json_read('plans.json');
$items = $plans['items'] ?? [];
$newItems = [];
$removed = false;

foreach ($items as $plan) {
    $href = $plan['href'] ?? '';
    $planSlug = preg_replace('#^plans/|\.html$#', '', $href);
    if ($planSlug === $slug) {
        $removed = true;
        continue;
    }
    $newItems[] = $plan;
}

if (!$removed) {
    admin_flash('error', 'ไม่พบแผนในรายการ');
    header('Location: plans-list.php');
    exit;
}

$plans['items'] = $newItems;
json_write('plans.json', $plans);

$details = json_read('plans-detail.json');
if (isset($details['items'][$slug])) {
    unset($details['items'][$slug]);
    json_write('plans-detail.json', $details);
}

admin_delete_content_shell('plans', $slug);

admin_flash('success', 'ลบแผนประกันแล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
header('Location: plans-list.php');
exit;
