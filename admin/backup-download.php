<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$id = (string) ($_GET['id'] ?? '');
$file = (string) ($_GET['file'] ?? '');

try {
    if ($file === 'all.zip' || $file === 'data-only.zip') {
        $backupId = basename($id);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
            throw new InvalidArgumentException('รหัสสำรองไม่ถูกต้อง');
        }
        $dir = BACKUP_PATH . '/' . $backupId;
        if (!is_dir($dir)) {
            throw new RuntimeException('ไม่พบไฟล์สำรอง');
        }

        $dataOnly = $file === 'data-only.zip';
        $zipPath = $dir . '/' . $file;
        if (!is_file($zipPath)) {
            $zipPath = admin_build_backup_zip($backupId, $dataOnly);
        }

        $downloadName = ($dataOnly ? 'backup-data-' : 'backup-') . $backupId . '.zip';
        admin_send_file_download($zipPath, $downloadName, 'application/zip');
    }

    $path = admin_backup_file_path($id, $file);
    $mime = 'application/octet-stream';
    if (str_ends_with(strtolower($path), '.json')) {
        $mime = 'application/json; charset=utf-8';
    } elseif (str_ends_with(strtolower($path), '.js')) {
        $mime = 'application/javascript; charset=utf-8';
    }
    admin_send_file_download($path, basename($path), $mime);
} catch (Throwable $e) {
    admin_backup_prepare_download();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ดาวน์โหลดไม่สำเร็จ: ' . $e->getMessage();
    echo "\n\nถ้า zip เต็มใหญ่เกินไป ลองปุ่ม「JSON」หรือดาวน์โหลดโฟลเดอร์ data/backups/ ผ่าน cPanel/FTP";
    exit;
}
