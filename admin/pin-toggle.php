<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/generate-js.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: dashboard.php');
    exit;
}

$kind = admin_post('kind');
$back = admin_post('back', 'dashboard.php');

$applyPin = static function (array $item): array {
    if (admin_is_pinned($item)) {
        $item['pinned'] = false;
        unset($item['pinnedAt']);
    } else {
        $item['pinned'] = true;
        $item['pinnedAt'] = date('c');
    }
    return $item;
};

try {
    if ($kind === 'plan') {
        $slug = admin_post('slug');
        $plans = json_read('plans.json');
        $planItems = $plans['items'] ?? [];
        $found = false;
        foreach ($planItems as &$plan) {
            $href = $plan['href'] ?? '';
            if (preg_replace('#^plans/|\.html$#', '', $href) === $slug) {
                $plan = $applyPin($plan);
                $found = true;
                break;
            }
        }
        unset($plan);
        if (!$found) {
            throw new RuntimeException('ไม่พบรายการ');
        }
        $plans['items'] = $planItems;
        json_write('plans.json', $plans);
    } elseif (in_array($kind, ['articles', 'news', 'careers', 'claims'], true)) {
        $type = $kind;
        $types = admin_content_types();
        $slug = admin_post('slug');
        $store = json_read($types[$type]['file']);
        $key = $types[$type]['itemsKey'];
        if (!isset($store[$key][$slug])) {
            throw new RuntimeException('ไม่พบรายการ');
        }
        $store[$key][$slug] = $applyPin($store[$key][$slug]);
        json_write($types[$type]['file'], $store);
    } else {
        throw new InvalidArgumentException('ประเภทไม่ถูกต้อง');
    }
    generate_all_js();
    admin_flash('success', 'อัปเดตการปักหมุดแล้ว');
} catch (Throwable $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: ' . $back);
exit;
