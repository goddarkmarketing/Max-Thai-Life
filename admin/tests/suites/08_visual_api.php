<?php
declare(strict_types=1);

function suite_visual_api(TestRunner $t, HttpClient $http): void
{
    echo "\n[8] Visual Editor + API อัปโหลด/บันทึก\n";

    if (!$http->ping()) {
        $t->test('Apache ทำงาน (visual API)', function (TestRunner $t) {
            $t->skip('ไม่สามารถเชื่อมต่อ http://localhost/1496 — เปิด XAMPP Apache ก่อน');
        });
        return;
    }

    $http->login();

    $t->test('หน้า landing-pages.php โหลดได้', function (TestRunner $t) use ($http) {
        $res = $http->getAdminPage('landing-pages.php');
        $t->assertEquals(200, $res['code']);
        $t->assertContains('landing', strtolower($res['body']));
    });

    $t->test('หน้า page-visual.php มี PAGE_VISUAL_DATA', function (TestRunner $t) use ($http) {
        $res = $http->getAdminPage('page-visual.php?page=about');
        $t->assertEquals(200, $res['code']);
        $boot = $http->extractPageVisualData($res['body']);
        $t->assertEquals('about', $boot['page'] ?? null);
        $t->assertTrue(is_string($boot['csrf'] ?? null) && ($boot['csrf'] ?? '') !== '');
        $t->assertTrue(is_array($boot['pageData'] ?? null));
        $t->assertContains('page-visual-editor.js', $res['body']);
        $t->assertContains('page-block-builder.js', $res['body']);
    });

    $t->test('หน้า plan-visual.php มี PAGE_VISUAL_DATA (editorKind=plan)', function (TestRunner $t) use ($http) {
        $slug = test_first_plan_slug();
        $res = $http->getAdminPage('plan-visual.php?slug=' . rawurlencode($slug));
        $t->assertEquals(200, $res['code']);
        $boot = $http->extractPageVisualData($res['body']);
        $t->assertEquals($slug, $boot['slug'] ?? null);
        $t->assertEquals('plan', $boot['editorKind'] ?? null);
        $t->assertTrue(is_string($boot['csrf'] ?? null));
    });

    $t->test('API page-save.php บันทึก hero title', function (TestRunner $t) use ($http) {
        $original = json_read('pages.json');
        $marker = test_marker('PAGE_SAVE');

        $pageRes = $http->getAdminPage('page-visual.php?page=about');
        $boot = $http->extractPageVisualData($pageRes['body']);
        $pageData = $boot['pageData'];
        $pageData['hero']['title'] = $marker;
        $pageData['title'] = $marker;

        $res = $http->postAdminJson('api/page-save.php', [
            'csrf' => $boot['csrf'],
            'page' => 'about',
            'pageData' => $pageData,
            'publish' => true,
        ]);
        $t->assertEquals(200, $res['code']);
        $body = json_decode($res['body'], true);
        $t->assertTrue(is_array($body) && ($body['ok'] ?? false) === true, $res['body']);

        $saved = json_read('pages.json');
        $t->assertEquals($marker, $saved['about']['hero']['title'] ?? null);
        $t->assertContains($marker, JsReader::readFile('pages-data.js'));

        json_write('pages.json', $original);
        generate_all_js();
    });

    $t->test('API plan-save.php บันทึก heroLead', function (TestRunner $t) use ($http) {
        $slug = test_first_plan_slug();
        $originalDetail = json_read('plans-detail.json');
        $marker = test_marker('PLAN_SAVE');

        $pageRes = $http->getAdminPage('plan-visual.php?slug=' . rawurlencode($slug));
        $boot = $http->extractPageVisualData($pageRes['body']);
        $pageData = $boot['pageData'];
        $pageData['hero']['lead'] = $marker;

        $res = $http->postAdminJson('api/plan-save.php', [
            'csrf' => $boot['csrf'],
            'slug' => $slug,
            'pageData' => $pageData,
            'publish' => true,
        ]);
        $t->assertEquals(200, $res['code']);
        $body = json_decode($res['body'], true);
        $t->assertTrue(is_array($body) && ($body['ok'] ?? false) === true, $res['body']);

        $saved = json_read('plans-detail.json');
        $t->assertEquals($marker, $saved['items'][$slug]['heroLead'] ?? null);
        $t->assertContains($marker, JsReader::readFile('plans-detail-content.js'));

        json_write('plans-detail.json', $originalDetail);
        generate_all_js();
    });

    $t->test('API upload.php อัปโหลดรูป plan_content', function (TestRunner $t) use ($http) {
        $png = sys_get_temp_dir() . '/mtl-test-' . bin2hex(random_bytes(4)) . '.png';
        test_create_png($png);

        $pageRes = $http->getAdminPage('plan-visual.php?slug=' . rawurlencode(test_first_plan_slug()));
        $boot = $http->extractPageVisualData($pageRes['body']);

        $res = $http->postMultipart('api/upload.php', [
            'csrf' => $boot['csrf'],
            'spec' => 'plan_content',
        ], 'file', $png);

        @unlink($png);

        $t->assertEquals(200, $res['code']);
        $body = json_decode($res['body'], true);
        $t->assertTrue(is_array($body) && ($body['ok'] ?? false) === true, $res['body']);
        $path = $body['path'] ?? '';
        $t->assertTrue($path !== '', 'ต้องได้ path กลับมา');
        $full = ROOT_PATH . '/' . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $t->assertFileExists($full);
        if (is_file($full)) {
            unlink($full);
        }
    });

    $t->test('API upload.php อัปโหลดรูป media_library', function (TestRunner $t) use ($http) {
        $png = sys_get_temp_dir() . '/mtl-test-' . bin2hex(random_bytes(4)) . '.png';
        test_create_png($png);

        $pageRes = $http->getAdminPage('page-visual.php?page=about');
        $boot = $http->extractPageVisualData($pageRes['body']);

        $res = $http->postMultipart('api/upload.php', [
            'csrf' => $boot['csrf'],
            'spec' => 'media_library',
        ], 'file', $png);

        @unlink($png);

        $t->assertEquals(200, $res['code']);
        $body = json_decode($res['body'], true);
        $t->assertTrue($body['ok'] ?? false, $res['body']);
        $full = ROOT_PATH . '/' . str_replace('/', DIRECTORY_SEPARATOR, $body['path'] ?? '');
        if (is_file($full)) {
            unlink($full);
        }
    });

    $t->test('API upload.php ปฏิเสธ CSRF ไม่ถูกต้อง', function (TestRunner $t) use ($http) {
        $png = sys_get_temp_dir() . '/mtl-test-' . bin2hex(random_bytes(4)) . '.png';
        test_create_png($png);
        $res = $http->postMultipart('api/upload.php', [
            'csrf' => 'invalid-token',
            'spec' => 'media_library',
        ], 'file', $png);
        @unlink($png);
        $t->assertEquals(403, $res['code']);
    });

    $t->test('API page-save.php ปฏิเสธ page key ไม่ถูกต้อง', function (TestRunner $t) use ($http) {
        $pageRes = $http->getAdminPage('page-visual.php?page=about');
        $boot = $http->extractPageVisualData($pageRes['body']);
        $res = $http->postAdminJson('api/page-save.php', [
            'csrf' => $boot['csrf'],
            'page' => 'invalid-page-key',
            'pageData' => $boot['pageData'],
        ]);
        $t->assertEquals(400, $res['code']);
    });

    $t->test('หน้า landing หน้าบ้านโหลด JS ครบ', function (TestRunner $t) use ($http) {
        $pages = [
            '/about.html' => 'pages-data.js',
            '/products.html' => ['pages-data.js', 'articles-data.js'],
            '/career.html' => ['pages-data.js', 'careers-data.js'],
            '/news.html' => ['pages-data.js', 'news-data.js'],
            '/claim-reviews.html' => ['pages-data.js', 'claim-reviews-data.js'],
        ];
        foreach ($pages as $path => $scripts) {
            $res = $http->request('GET', $path);
            $t->assertEquals(200, $res['code'], "{$path} HTTP " . $res['code']);
            foreach ((array) $scripts as $script) {
                $t->assertContains($script, $res['body'], "{$path} ต้องโหลด {$script}");
            }
        }
    });

    $guest = new HttpClient('http://localhost/1496');
    $t->test('API upload.php ต้อง login ก่อน', function (TestRunner $t) use ($guest) {
        $res = $guest->request('POST', '/admin/api/upload.php', ['csrf' => 'x', 'spec' => 'media_library'], [], false);
        $t->assertTrue(
            $res['code'] === 302 || str_contains($res['redirect'] ?? '', 'index.php'),
            'ต้อง redirect ไปหน้า login (HTTP ' . $res['code'] . ')'
        );
    });
}
