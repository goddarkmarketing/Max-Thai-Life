<?php
declare(strict_types=1);

/**
 * ทดสอบว่าแก้ JSON ใน data/ แล้ว generate_all_js() หน้าบ้านต้องเปลี่ยนตาม
 */
function suite_publish_sync(TestRunner $t): void
{
    echo "\n[2] การเผยแพร่ — data/ → js/ (หน้าบ้าน)\n";

    $cases = [
        [
            'label' => 'site.json → site-data.js (ชื่อแบรนด์)',
            'file' => 'site.json',
            'js' => 'site-data.js',
            'var' => 'SITE_DATA',
            'path' => ['brand', 'name'],
        ],
        [
            'label' => 'home.json → home-data.js (ข้อความ Hero)',
            'file' => 'home.json',
            'js' => 'home-data.js',
            'var' => 'HOME_DATA',
            'path' => ['hero', 'lead'],
        ],
        [
            'label' => 'pages.json → pages-data.js (หน้าเกี่ยวกับเรา)',
            'file' => 'pages.json',
            'js' => 'pages-data.js',
            'var' => 'PAGES_DATA',
            'path' => ['about', 'title'],
        ],
        [
            'label' => 'plans.json → plans-data.js (ชื่อแผนแรก)',
            'file' => 'plans.json',
            'js' => 'plans-data.js',
            'var' => 'PLANS_DATA',
            'path' => ['items', 0, 'title'],
            'isList' => true,
        ],
        [
            'label' => 'articles.json → articles-data.js',
            'file' => 'articles.json',
            'js' => 'articles-data.js',
            'var' => 'ARTICLES_DETAIL',
            'path' => ['items'],
            'firstItemKey' => true,
        ],
        [
            'label' => 'news.json → news-data.js',
            'file' => 'news.json',
            'js' => 'news-data.js',
            'var' => 'NEWS_DETAIL',
            'path' => ['items'],
            'firstItemKey' => true,
        ],
        [
            'label' => 'careers.json → careers-data.js',
            'file' => 'careers.json',
            'js' => 'careers-data.js',
            'var' => 'CAREERS_DETAIL',
            'path' => ['items'],
            'firstItemKey' => true,
        ],
        [
            'label' => 'claim-reviews.json → claim-reviews-data.js',
            'file' => 'claim-reviews.json',
            'js' => 'claim-reviews-data.js',
            'var' => 'CLAIM_REVIEWS_DETAIL',
            'path' => ['items'],
            'firstItemKey' => true,
        ],
    ];

    foreach ($cases as $case) {
        $t->test($case['label'], function (TestRunner $t) use ($case) {
            $marker = test_marker();
            $original = test_patch_json($case['file'], function (array &$data) use ($case, $marker) {
                if (!empty($case['firstItemKey'])) {
                    $items = $data['items'] ?? [];
                    if ($items === []) {
                        throw new RuntimeException('ไม่มีรายการใน ' . $case['file']);
                    }
                    $key = array_key_first($items);
                    $data['items'][$key]['title'] = $marker;
                    return;
                }
                $ref = &$data;
                $path = $case['path'];
                foreach ($path as $i => $segment) {
                    if ($i === count($path) - 1) {
                        $ref[$segment] = $marker;
                    } else {
                        if (!isset($ref[$segment])) {
                            throw new RuntimeException('path ไม่ถูกต้องใน ' . $case['file']);
                        }
                        $ref = &$ref[$segment];
                    }
                }
            });

            generate_all_js();
            $t->assertContains($marker, JsReader::readFile($case['js']), 'หลังเผยแพร่ต้องมีในหน้าบ้าน');

            $decoded = JsReader::decodeWindowVar($case['js'], $case['var']);
            if (!empty($case['firstItemKey'])) {
                $found = false;
                foreach ($decoded as $item) {
                    if (is_array($item) && ($item['title'] ?? '') === $marker) {
                        $found = true;
                        break;
                    }
                }
                $t->assertTrue($found, 'ค่าใน window.' . $case['var'] . ' ต้องตรง');
            } elseif ($case['var'] === 'PLANS_DATA') {
                $t->assertEquals($marker, $decoded[0]['title'] ?? null);
            } elseif ($case['var'] === 'SITE_DATA') {
                $t->assertEquals($marker, $decoded['brand']['name'] ?? null);
            } elseif ($case['var'] === 'HOME_DATA') {
                $t->assertEquals($marker, $decoded['hero']['lead'] ?? null);
            } elseif ($case['var'] === 'PAGES_DATA') {
                $t->assertEquals($marker, $decoded['about']['title'] ?? null);
            }

            test_restore_json($case['file'], $original);
            generate_all_js();
        });
    }

    $t->test('plans-detail.json → plans-detail-content.js', function (TestRunner $t) {
        $marker = test_marker();
        $original = test_patch_json('plans-detail.json', function (array &$data) use ($marker) {
            $items = $data['items'] ?? [];
            if ($items === []) {
                throw new RuntimeException('ไม่มี plans detail');
            }
            $key = array_key_first($items);
            $data['items'][$key]['heroLead'] = $marker;
        });

        generate_all_js();
        $t->assertContains($marker, JsReader::readFile('plans-detail-content.js'));

        $detail = JsReader::decodeWindowVar('plans-detail-content.js', 'PLANS_DETAIL');
        $found = false;
        foreach ($detail as $item) {
            if (is_array($item) && ($item['heroLead'] ?? '') === $marker) {
                $found = true;
                break;
            }
        }
        $t->assertTrue($found, 'PLANS_DETAIL ต้องมี heroLead ที่แก้');

        test_restore_json('plans-detail.json', $original);
        generate_all_js();
    });

    $t->test('site.json navigation → site-data.js', function (TestRunner $t) {
        $marker = test_marker('NAV_TEST');
        $original = test_patch_json('site.json', function (array &$data) use ($marker) {
            if (empty($data['navigation'])) {
                $data['navigation'] = admin_default_navigation();
            }
            $data['navigation'][0]['label'] = $marker;
        });

        generate_all_js();
        $site = JsReader::decodeWindowVar('site-data.js', 'SITE_DATA');
        $t->assertEquals($marker, $site['navigation'][0]['label'] ?? null);

        test_restore_json('site.json', $original);
        generate_all_js();
    });
}
