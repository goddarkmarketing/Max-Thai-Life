<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$zipPath = $argv[1] ?? '';
$backupId = $argv[2] ?? '';

if ($zipPath === '' || !is_file($zipPath)) {
    fwrite(STDERR, "Usage: php _import-backup-zip.php <zip-path> [backup-id]\n");
    exit(1);
}

if ($backupId === '') {
    if (preg_match('/backup-(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.zip$/i', basename($zipPath), $m)) {
        $backupId = $m[1];
    } else {
        $backupId = date('Y-m-d_H-i-s');
    }
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
    fwrite(STDERR, "Invalid backup id: {$backupId}\n");
    exit(1);
}

if (!is_dir(BACKUP_PATH) && !mkdir(BACKUP_PATH, 0755, true)) {
    fwrite(STDERR, "Cannot create backup path\n");
    exit(1);
}

$dir = BACKUP_PATH . '/' . $backupId;
if (is_dir($dir)) {
    admin_remove_dir($dir);
}
if (!mkdir($dir, 0755, true)) {
    fwrite(STDERR, "Cannot create backup dir\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    fwrite(STDERR, "Cannot open zip\n");
    exit(1);
}

$counts = ['data' => 0, 'js' => 0, 'media' => 0];
$totalBytes = 0;
$totalFiles = 0;

for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
    if ($name === '' || str_ends_with($name, '/')) {
        continue;
    }

    $dest = $dir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $name);
    $destDir = dirname($dest);
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
        $zip->close();
        fwrite(STDERR, "Cannot create dir: {$destDir}\n");
        exit(1);
    }

    $stream = $zip->getStream($zip->getNameIndex($i));
    if ($stream === false) {
        $zip->close();
        fwrite(STDERR, "Cannot read: {$name}\n");
        exit(1);
    }
    $out = fopen($dest, 'wb');
    if ($out === false) {
        fclose($stream);
        $zip->close();
        fwrite(STDERR, "Cannot write: {$dest}\n");
        exit(1);
    }
    stream_copy_to_stream($stream, $out);
    fclose($stream);
    fclose($out);

    $size = (int) (filesize($dest) ?: 0);
    $totalBytes += $size;
    $totalFiles++;
    if (str_starts_with($name, 'data/')) {
        $counts['data']++;
    } elseif (str_starts_with($name, 'js/')) {
        $counts['js']++;
    } elseif (str_starts_with($name, 'media/')) {
        $counts['media']++;
    }
}

$zip->close();

// Keep original zip for reference/download
copy($zipPath, $dir . '/source.zip');

$manifest = [
    'version' => 2,
    'kind' => 'full',
    'createdAt' => date('c'),
    'importedFrom' => basename($zipPath),
    'totalFiles' => $totalFiles,
    'totalBytes' => $totalBytes,
    'counts' => $counts,
];
file_put_contents(
    $dir . '/manifest.json',
    json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    LOCK_EX
);

admin_prune_backups();

echo "Imported backup {$backupId} · {$totalFiles} files · " . admin_format_bytes($totalBytes) . PHP_EOL;
