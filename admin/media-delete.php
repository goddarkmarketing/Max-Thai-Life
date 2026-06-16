<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: media.php');
    exit;
}

$path = admin_post('path');
try {
    admin_delete_media_file($path);
    admin_flash('success', 'ลบรูปแล้ว');
} catch (Throwable $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: media.php');
exit;
