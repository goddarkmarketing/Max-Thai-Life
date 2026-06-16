<?php
declare(strict_types=1);

function suite_http(TestRunner $t, HttpClient $http): void
{
    echo "\n[5] HTTP — หลังบ้านผ่านเว็บเซิร์ฟเวอร์ (ต้องเปิด Apache)\n";

    if (!$http->ping()) {
        $t->test('Apache ทำงาน', function (TestRunner $t) {
            $t->skip('ไม่สามารถเชื่อมต่อ http://localhost/1496 — เปิด XAMPP Apache ก่อน');
        });
        return;
    }

    $t->test('หน้าแรกเว็บไซต์โหลดได้', function (TestRunner $t) use ($http) {
        $res = $http->request('GET', '/index.html');
        $t->assertTrue($res['code'] === 200, 'HTTP ' . $res['code']);
        $t->assertContains('site-data.js', $res['body'], 'ต้องโหลด site-data.js');
        $t->assertContains('home-data.js', $res['body'], 'ต้องโหลด home-data.js');
    });

    $t->test('เข้าสู่ระบบ admin สำเร็จ', function (TestRunner $t) use ($http) {
        $http->login();
        $t->assertTrue($http->isLoggedIn());
        $dash = $http->getAdminPage('dashboard.php');
        $t->assertTrue($dash['code'] === 200, 'dashboard HTTP ' . $dash['code']);
        $t->assertContains('admin-sidebar', $dash['body']);
    });

    $adminPages = [
        'site.php' => 'ตั้งค่าเว็บไซต์',
        'site-nav.php' => 'เมนูนำทาง',
        'site-footer.php' => 'Footer',
        'site-seo.php' => 'SEO',
        'home.php' => 'หน้าแรก',
        'pages.php' => 'หน้าคงที่',
        'plans-list.php' => 'แผนประกัน',
        'inquiries.php' => 'ข้อความติดต่อ',
        'media.php' => 'คลังรูป',
        'backups.php' => 'สำรองข้อมูล',
        'account.php' => 'บัญชีผู้ใช้',
    ];

    foreach ($adminPages as $page => $label) {
        $t->test("หน้า admin โหลดได้: {$label}", function (TestRunner $t) use ($http, $page) {
            $res = $http->getAdminPage($page);
            $t->assertTrue($res['code'] === 200, "{$page} HTTP " . $res['code']);
            $t->assertContains('admin-sidebar', $res['body']);
        });
    }

    $t->test('บันทึก site.php + เผยแพร่ → site-data.js เปลี่ยนตาม', function (TestRunner $t) use ($http) {
        $marker = test_marker('HTTP_SITE');
        $site = json_read('site.json');

        $page = $http->getAdminPage('site.php');
        $csrf = $http->extractCsrf($page['body']);
        $fields = test_site_form_fields($site, ['brand_name' => $marker, 'csrf' => $csrf]);
        $save = $http->postAdmin('site.php', $fields);
        $t->assertTrue($save['code'] < 400, 'บันทึก site.php HTTP ' . $save['code']);

        $saved = json_read('site.json');
        $t->assertEquals($marker, $saved['brand']['name'] ?? null, 'site.json ต้องบันทึกค่าใหม่');

        $dash = $http->getAdminPage('dashboard.php');
        $pubCsrf = $http->extractCsrf($dash['body']);
        $pub = $http->postAdmin('publish.php', ['csrf' => $pubCsrf, 'back' => 'dashboard.php']);
        $t->assertTrue($pub['code'] < 400, 'เผยแพร่ HTTP ' . $pub['code']);

        $t->assertContains($marker, JsReader::readFile('site-data.js'), 'หน้าบ้านต้องมีชื่อแบรนด์ใหม่');
        $decoded = JsReader::decodeWindowVar('site-data.js', 'SITE_DATA');
        $t->assertEquals($marker, $decoded['brand']['name'] ?? null);

        json_write('site.json', $site);
        generate_all_js();
    });

    $t->test('บันทึก home.php + เผยแพร่ → home-data.js เปลี่ยนตาม', function (TestRunner $t) use ($http) {
        $marker = test_marker('HTTP_HOME');
        $home = json_read('home.json');

        $page = $http->getAdminPage('home.php');
        $csrf = $http->extractCsrf($page['body']);
        $fields = test_home_hero_form_fields($home, ['hero_lead' => $marker, 'csrf' => $csrf]);
        $save = $http->postAdmin('home.php', $fields);
        $t->assertTrue($save['code'] < 400, 'บันทึก home.php HTTP ' . $save['code']);

        $saved = json_read('home.json');
        $t->assertEquals($marker, $saved['hero']['lead'] ?? null);

        $dash = $http->getAdminPage('dashboard.php');
        $pubCsrf = $http->extractCsrf($dash['body']);
        $http->postAdmin('publish.php', ['csrf' => $pubCsrf, 'back' => 'dashboard.php']);

        $t->assertContains($marker, JsReader::readFile('home-data.js'));
        $decoded = JsReader::decodeWindowVar('home-data.js', 'HOME_DATA');
        $t->assertEquals($marker, $decoded['hero']['lead'] ?? null);

        json_write('home.json', $home);
        generate_all_js();
    });

    $t->test('หน้า admin ป้องกันการเข้าถึงโดยไม่ล็อกอิน', function (TestRunner $t) {
        $guest = new HttpClient('http://localhost/1496');
        $res = $guest->getAdminPage('dashboard.php');
        $t->assertTrue(
            $res['code'] === 200 && (str_contains($res['body'], 'เข้าสู่ระบบ') || str_contains($res['body'], 'username')),
            'ต้อง redirect ไปหน้า login'
        );
    });
}
