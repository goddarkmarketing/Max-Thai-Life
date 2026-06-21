<?php
declare(strict_types=1);

function suite_landing_blocks(TestRunner $t): void
{
    echo "\n[7] Landing / Plan blocks — ฟังก์ชัน PHP\n";

    $t->test('admin_landing_page_keys ครบ 5 หน้า', function (TestRunner $t) {
        $keys = admin_landing_page_keys();
        $t->assertEquals(5, count($keys));
        $t->assertTrue(in_array('about', $keys, true));
        $t->assertTrue(in_array('claimReviews', $keys, true));
    });

    $t->test('admin_landing_default_block สร้างบล็อก image ได้', function (TestRunner $t) {
        $block = admin_landing_default_block('image');
        $t->assertEquals('image', $block['type'] ?? null);
        $t->assertTrue(is_array($block['image'] ?? null));
        $t->assertEquals('', $block['image']['src'] ?? null);
    });

    $t->test('admin_normalize_block_section แมป legacy heading → title', function (TestRunner $t) {
        $sec = admin_normalize_block_section([
            'type' => 'heading',
            'heading' => 'หัวข้อทดสอบ',
            'lead' => 'คำอธิบาย',
        ], 0);
        $t->assertEquals('หัวข้อทดสอบ', $sec['title'] ?? null);
        $t->assertEquals('คำอธิบาย', $sec['description'] ?? null);
        $t->assertTrue($sec['isVisible'] ?? false);
    });

    $t->test('featured block sync จาก careers.json เมื่อมี slug', function (TestRunner $t) {
        $careers = json_read('careers.json');
        $slug = array_key_first($careers['items'] ?? []);
        if ($slug === null) {
            $t->skip('ไม่มี career item');
        }
        $item = $careers['items'][$slug];
        $sec = admin_normalize_block_section([
            'type' => 'featured',
            'slug' => $slug,
            'title' => 'หัวข้อ section',
        ], 0);
        $t->assertEquals($item['title'] ?? '', $sec['featureTitle'] ?? null);
        $t->assertContains('careers/' . $slug, $sec['buttonLink'] ?? '');
        if (!empty($item['image'])) {
            $t->assertEquals($item['image'], $sec['image']['src'] ?? null);
        }
    });

    $t->test('admin_normalize_pages_data สร้างทุกหน้า landing', function (TestRunner $t) {
        $data = admin_normalize_pages_data([]);
        foreach (admin_landing_page_keys() as $key) {
            $t->assertTrue(isset($data[$key]), "ต้องมี key {$key}");
            $t->assertTrue(is_array($data[$key]['hero'] ?? null), "{$key} ต้องมี hero");
            $t->assertTrue(is_array($data[$key]['sections'] ?? null), "{$key} ต้องมี sections");
        }
    });

    $t->test('admin_landing_page_from_post บันทึก marker ใน hero', function (TestRunner $t) {
        $marker = test_marker('LANDING_HERO');
        $page = admin_landing_page_from_post('about', [
            'hero' => ['title' => $marker, 'lead' => 'lead test', 'breadcrumb' => 'เกี่ยวกับเรา'],
            'sections' => [],
            'cta' => ['title' => '', 'lead' => '', 'buttons' => []],
        ]);
        $t->assertEquals($marker, $page['hero']['title'] ?? null);
        $t->assertEquals($marker, $page['title'] ?? null);
    });

    $t->test('admin_plan_detail_to_page_data ↔ admin_plan_page_data_to_detail', function (TestRunner $t) {
        $slug = test_first_plan_slug();
        $details = json_read('plans-detail.json');
        $detail = $details['items'][$slug] ?? null;
        if ($detail === null) {
            $t->skip("ไม่มี detail สำหรับ {$slug}");
        }
        $marker = test_marker('PLAN_BLOCK');
        $pageData = admin_plan_detail_to_page_data($detail, null, $slug);
        $pageData['hero']['lead'] = $marker;
        $restored = admin_plan_page_data_to_detail($pageData, $detail);
        $t->assertEquals($marker, $restored['heroLead'] ?? null);
    });

    $t->test('admin_plan_upgrade_sections แปลง customHtml table → specTable', function (TestRunner $t) {
        $sections = admin_plan_upgrade_sections([
            [
                'type' => 'customHtml',
                'customHtml' => '<table class="plan-spec-table"><tr><th>ประเภท</th><td>ออมทรัพย์</td></tr></table>',
            ],
        ]);
        $t->assertEquals('specTable', $sections[0]['type'] ?? null);
        $t->assertTrue(count($sections[0]['items'] ?? []) > 0);
    });

    $t->test('admin_plan_upgrade_sections แปลง customHtml list → bulletList', function (TestRunner $t) {
        $sections = admin_plan_upgrade_sections([
            [
                'type' => 'customHtml',
                'customHtml' => '<ul><li><strong>ข้อ 1</strong> — รายละเอียด</li></ul>',
            ],
        ]);
        $t->assertEquals('bulletList', $sections[0]['type'] ?? null);
        $t->assertTrue(count($sections[0]['items'] ?? []) > 0);
    });

    $t->test('image-specs มี plan_content และ media_library', function (TestRunner $t) {
        $specs = require ADMIN_PATH . '/includes/image-specs.php';
        $t->assertTrue(isset($specs['plan_content']));
        $t->assertTrue(isset($specs['media_library']));
        $t->assertTrue(isset($specs['video_library']));
    });
}
