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

try {
    generate_all_js();
    admin_log_publish();
    admin_flash('success', 'เผยแพร่เนื้อหาขึ้นเว็บไซต์เรียบร้อยแล้ว');
} catch (Throwable $e) {
    admin_flash('error', 'เผยแพร่ไม่สำเร็จ: ' . $e->getMessage());
}

$back = $_POST['back'] ?? 'dashboard.php';
header('Location: ' . $back);
exit;
