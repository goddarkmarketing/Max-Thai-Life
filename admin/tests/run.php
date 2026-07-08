<?php
declare(strict_types=1);

/**
 * ชุดทดสอบระบบหลังบ้าน Wealth Agent TL
 *
 * รัน: C:\xampp\php\php.exe admin\tests\run.php
 *      C:\xampp\php\php.exe admin\tests\run.php --base=http://localhost/1496
 *
 * ทดสอบ HTTP ต้องเปิด XAMPP Apache ก่อน
 * ข้อมูล JSON/JS จะถูกสำรองและคืนค่าหลังรันเสร็จ
 */

$baseUrl = 'http://localhost/1496';
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $baseUrl = substr($arg, 7);
    }
}

require_once __DIR__ . '/bootstrap.php';

$snapshot = new DataSnapshot();
$snapshot->backupJsonFiles();
$snapshot->backupJsFiles();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

$runner = new TestRunner();
$http = new HttpClient($baseUrl);

echo "══════════════════════════════════════\n";
echo " Wealth Agent TL — ชุดทดสอบระบบหลังบ้าน\n";
echo " Base URL: {$baseUrl}\n";
echo "══════════════════════════════════════\n";

$suites = glob(__DIR__ . '/suites/*.php') ?: [];
sort($suites);

foreach ($suites as $suiteFile) {
    require_once $suiteFile;
    $map = [
        '01_infrastructure' => 'suite_infrastructure',
        '02_publish_sync' => 'suite_publish_sync',
        '03_helpers' => 'suite_helpers',
        '04_visibility' => 'suite_visibility',
        '05_http' => 'suite_http',
        '06_inquiries' => 'suite_inquiries',
        '07_landing_blocks' => 'suite_landing_blocks',
        '08_visual_api' => 'suite_visual_api',
    ];
    $fn = $map[basename($suiteFile, '.php')] ?? null;
    if ($fn === null || !function_exists($fn)) {
        continue;
    }
    if ($fn === 'suite_http') {
        suite_http($runner, $http);
    } elseif ($fn === 'suite_inquiries') {
        suite_inquiries($runner, $http);
    } elseif ($fn === 'suite_visual_api') {
        suite_visual_api($runner, $http);
    } else {
        $fn($runner);
    }
}

$snapshot->restoreJsonFiles();
$snapshot->restoreJsFiles();
$snapshot->cleanup();

exit($runner->summary());
