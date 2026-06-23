<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/generate-js.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: plans-list.php');
    exit;
}

$slug = admin_post('slug');
$plans = json_read('plans.json');
$items = $plans['items'] ?? [];
$source = null;
foreach ($items as $plan) {
    $href = $plan['href'] ?? '';
    if (preg_replace('#^plans/|\.html$#', '', $href) === $slug) {
        $source = $plan;
        break;
    }
}
if ($source === null) {
    admin_flash('error', 'ไม่พบแผน');
    header('Location: plans-list.php');
    exit;
}

$existing = array_map(static fn ($p) => preg_replace('#^plans/|\.html$#', '', $p['href'] ?? ''), $items);
$newSlug = admin_duplicate_slug($slug, $existing);
$newPlan = $source;
$newPlan['title'] = ($source['title'] ?? $slug) . ' (สำเนา)';
$newPlan['href'] = 'plans/' . $newSlug . '.html';
$newPlan['visible'] = false;
$items[] = $newPlan;
$plans['items'] = $items;
json_write('plans.json', $plans);

$details = json_read('plans-detail.json');
if (isset($details['items'][$slug])) {
    $copy = $details['items'][$slug];
    $copy['title'] = ($copy['title'] ?? $source['title'] ?? $newSlug) . ' (สำเนา)';
    $copy['breadcrumb'] = $copy['title'];
    $copy['visible'] = false;
    $details['items'][$newSlug] = $copy;
    json_write('plans-detail.json', $details);
}

admin_create_content_shell('plans', $newSlug);
generate_all_js();
admin_flash('success', 'สำเนาแผนแล้ว — สถานะ: ซ่อนจากเว็บ');
header('Location: plan-edit.php?slug=' . urlencode($newSlug));
exit;
