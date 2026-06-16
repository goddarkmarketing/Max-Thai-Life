<?php
declare(strict_types=1);

function suite_infrastructure(TestRunner $t): void
{
    echo "\n[1] โครงสร้างพื้นฐาน\n";

    $t->test('โฟลเดอร์ data/ และ js/ มีอยู่', function (TestRunner $t) {
        $t->assertTrue(is_dir(DATA_PATH), 'data/');
        $t->assertTrue(is_dir(JS_PATH), 'js/');
    });

    $requiredJson = [
        'site.json', 'home.json', 'pages.json', 'plans.json', 'plans-detail.json',
        'articles.json', 'news.json', 'careers.json', 'claim-reviews.json',
        'leads.json', 'publish-log.json',
    ];
    foreach ($requiredJson as $file) {
        $t->test("ไฟล์ JSON: {$file}", function (TestRunner $t) use ($file) {
            $path = DATA_PATH . '/' . $file;
            $t->assertFileExists($path);
            $data = json_decode(file_get_contents($path) ?: '', true);
            $t->assertTrue(is_array($data), "{$file} ต้องเป็น JSON object/array");
        });
    }

    $requiredJs = [
        'site-data.js', 'home-data.js', 'pages-data.js', 'plans-data.js',
        'plans-detail-content.js', 'articles-data.js', 'news-data.js',
        'careers-data.js', 'claim-reviews-data.js',
    ];
    foreach ($requiredJs as $file) {
        $t->test("ไฟล์ JS หน้าบ้าน: {$file}", function (TestRunner $t) use ($file) {
            $t->assertFileExists(JS_PATH . '/' . $file);
        });
    }

    $adminPages = [
        'dashboard.php', 'site.php', 'site-nav.php', 'site-footer.php', 'site-seo.php',
        'home.php', 'pages.php', 'plans-list.php', 'media.php', 'backups.php',
        'inquiries.php', 'account.php', 'publish.php',
    ];
    foreach ($adminPages as $page) {
        $t->test("ไฟล์ admin: {$page}", function (TestRunner $t) use ($page) {
            $t->assertFileExists(ADMIN_PATH . '/' . $page);
        });
    }
}
