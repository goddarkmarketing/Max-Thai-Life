<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$id = $_GET['id'] ?? '';
$file = $_GET['file'] ?? '';

try {
    if ($file === 'all.zip') {
        $backupId = basename($id);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
            throw new InvalidArgumentException('รหัสสำรองไม่ถูกต้อง');
        }
        $dir = BACKUP_PATH . '/' . $backupId;
        if (!is_dir($dir)) {
            throw new RuntimeException('ไม่พบไฟล์สำรอง');
        }
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('เซิร์ฟเวอร์ไม่รองรับ Zip');
        }
        $zip = new ZipArchive();
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup-' . $backupId . '-' . bin2hex(random_bytes(4)) . '.zip';
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('สร้างไฟล์ zip ไม่สำเร็จ');
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $path = $item->getPathname();
            $rel = str_replace('\\', '/', substr($path, strlen($dir) + 1));
            $zip->addFile($path, $rel);
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="backup-' . $backupId . '.zip"');
        header('Content-Length: ' . (string) filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    $path = admin_backup_file_path($id, $file);
    $mime = 'application/octet-stream';
    if (str_ends_with(strtolower($path), '.json')) {
        $mime = 'application/json; charset=utf-8';
    } elseif (str_ends_with(strtolower($path), '.js')) {
        $mime = 'application/javascript; charset=utf-8';
    }
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
} catch (Throwable $e) {
    admin_flash('error', $e->getMessage());
    header('Location: backups.php');
    exit;
}
