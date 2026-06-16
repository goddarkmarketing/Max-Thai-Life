<?php
declare(strict_types=1);

function suite_visibility(TestRunner $t): void
{
    echo "\n[4] ซ่อน/แสดงเนื้อหา — หน้าบ้านต้องไม่แสดงรายการที่ซ่อน\n";

    $t->test('บทความที่ visible:false ไม่ปรากฏใน articles-data.js', function (TestRunner $t) {
        $original = json_read('articles.json');
        $items = $original['items'] ?? [];
        if ($items === []) {
            $t->skip('ไม่มีบทความ');
        }
        $slug = array_key_first($items);
        $marker = test_marker('HIDDEN_ARTICLE');

        $patched = $original;
        $patched['items'][$slug]['title'] = $marker;
        $patched['items'][$slug]['visible'] = false;
        json_write('articles.json', $patched);

        generate_all_js();
        $t->assertNotContains($marker, JsReader::readFile('articles-data.js'), 'หน้าบ้านต้องไม่มีบทความที่ซ่อน');

        $patched['items'][$slug]['visible'] = true;
        json_write('articles.json', $patched);
        generate_all_js();
        $t->assertContains($marker, JsReader::readFile('articles-data.js'), 'เมื่อแสดงอีกครั้งต้องกลับมา');

        json_write('articles.json', $original);
        generate_all_js();
    });

    $t->test('แผนที่ visible:false ไม่ปรากฏใน plans-data.js', function (TestRunner $t) {
        $original = json_read('plans.json');
        $items = $original['items'] ?? [];
        if ($items === []) {
            $t->skip('ไม่มีแผน');
        }
        $marker = test_marker('HIDDEN_PLAN');

        $patched = $original;
        $patched['items'][0]['title'] = $marker;
        $patched['items'][0]['visible'] = false;
        json_write('plans.json', $patched);

        generate_all_js();
        $t->assertNotContains($marker, JsReader::readFile('plans-data.js'));

        json_write('plans.json', $original);
        generate_all_js();
    });
}
