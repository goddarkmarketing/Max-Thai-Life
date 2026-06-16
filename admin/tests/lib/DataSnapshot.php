<?php
declare(strict_types=1);

final class DataSnapshot
{
    private string $dir;

    public function __construct()
    {
        $this->dir = sys_get_temp_dir() . '/mtl-admin-test-' . bin2hex(random_bytes(4));
        if (!mkdir($this->dir) && !is_dir($this->dir)) {
            throw new RuntimeException('สร้างโฟลเดอร์สำรองชั่วคราวไม่สำเร็จ');
        }
    }

    public function backupJsonFiles(): void
    {
        foreach (glob(DATA_PATH . '/*.json') ?: [] as $file) {
            copy($file, $this->dir . '/' . basename($file));
        }
    }

    public function backupJsFiles(): void
    {
        $jsDir = $this->dir . '/js';
        if (!is_dir($jsDir)) {
            mkdir($jsDir, 0755, true);
        }
        foreach (glob(JS_PATH . '/*-data.js') ?: [] as $file) {
            copy($file, $jsDir . '/' . basename($file));
        }
        $detail = JS_PATH . '/plans-detail-content.js';
        if (is_file($detail)) {
            copy($detail, $jsDir . '/plans-detail-content.js');
        }
    }

    public function restoreJsonFiles(): void
    {
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            copy($file, DATA_PATH . '/' . basename($file));
        }
    }

    public function restoreJsFiles(): void
    {
        $jsDir = $this->dir . '/js';
        if (!is_dir($jsDir)) {
            return;
        }
        foreach (glob($jsDir . '/*.js') ?: [] as $file) {
            copy($file, JS_PATH . '/' . basename($file));
        }
    }

    public function cleanup(): void
    {
        $this->deleteDir($this->dir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $item) {
            if (is_dir($item)) {
                $this->deleteDir($item);
            } else {
                unlink($item);
            }
        }
        rmdir($dir);
    }
}
