<?php
declare(strict_types=1);

/**
 * Create a ZIP of images/plan-covers for uploading to maxthailife.com (Plesk).
 * Usage: php admin/tools/prepare-plan-covers-zip.php
 */

require_once __DIR__ . '/../includes/config.php';

$source = ROOT_PATH . '/images/plan-covers';
$outDir = DATA_PATH . '/deploy';
$outFile = $outDir . '/plan-covers.zip';

if (!is_dir($source)) {
    fwrite(STDERR, "Missing folder: {$source}\n");
    exit(1);
}

if (!is_dir($outDir) && !mkdir($outDir, 0755, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Cannot create: {$outDir}\n");
    exit(1);
}

if (file_exists($outFile)) {
    unlink($outFile);
}

$zip = new ZipArchive();
if ($zip->open($outFile, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Cannot create zip: {$outFile}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS)
);

$count = 0;
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $full = $file->getPathname();
    $rel = 'plan-covers/' . substr($full, strlen($source) + 1);
    $zip->addFile($full, str_replace('\\', '/', $rel));
    $count++;
}

$zip->close();

echo "Created {$outFile} ({$count} files)\n";
echo "Upload to server as: /images/plan-covers/\n";
