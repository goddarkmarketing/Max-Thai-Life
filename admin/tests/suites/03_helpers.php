<?php
declare(strict_types=1);

function suite_helpers(TestRunner $t): void
{
    echo "\n[3] ฟังก์ชันระบบหลังบ้าน\n";

    $t->test('admin_slugify สร้าง slug ถูกต้อง', function (TestRunner $t) {
        $t->assertEquals('money-fit-test', admin_slugify('Money Fit Test'));
        $t->assertEquals('test', admin_slugify('  test  '));
    });

    $t->test('admin_default_navigation มี 8 เมนู', function (TestRunner $t) {
        $nav = admin_default_navigation();
        $t->assertEquals(8, count($nav));
    });

    $t->test('admin_save_lead + admin_load_leads', function (TestRunner $t) {
        $original = json_read('leads.json');
        $id = bin2hex(random_bytes(8));
        admin_save_lead([
            'id' => $id,
            'topic' => 'insurance',
            'topicLabel' => 'สนใจทำประกันชีวิต',
            'name' => 'ผู้ทดสอบระบบ',
            'phone' => '0812345678',
            'email' => '',
            'message' => 'ข้อความทดสอบอัตโนมัติ',
            'source' => 'contact',
            'status' => 'new',
            'createdAt' => date('c'),
            'ip' => '127.0.0.1',
        ]);
        $leads = admin_load_leads();
        $found = false;
        foreach ($leads['items'] ?? [] as $item) {
            if (($item['id'] ?? '') === $id) {
                $found = true;
                break;
            }
        }
        $t->assertTrue($found, 'ต้องบันทึก lead ได้');
        admin_delete_lead($id);
        json_write('leads.json', $original);
    });

    $t->test('admin_create_manual_backup + admin_delete_backup', function (TestRunner $t) {
        $id = admin_create_manual_backup();
        $t->assertTrue(is_dir(BACKUP_PATH . '/' . $id), 'ต้องสร้างโฟลเดอร์สำรอง');
        $files = glob(BACKUP_PATH . '/' . $id . '/*.json') ?: [];
        $t->assertTrue(count($files) > 0, 'ต้องมีไฟล์ JSON ในสำรอง');
        admin_delete_backup($id);
        $t->assertFalse(is_dir(BACKUP_PATH . '/' . $id), 'ต้องลบสำรองได้');
    });

    $t->test('admin_apply_site_* บันทึก navigation และ footer', function (TestRunner $t) {
        $original = json_read('site.json');
        $_POST = [
            'nav_label' => ['ทดสอบเมนู'],
            'nav_href' => ['test.html'],
            'nav_visible' => ['1'],
            'footer_label' => ['ลิงก์ทดสอบ'],
            'footer_href' => ['plans/test.html'],
            'footer_tagline' => 'tagline test',
        ];
        $data = $original;
        $data = admin_apply_site_navigation_post($data);
        $t->assertEquals('ทดสอบเมนู', $data['navigation'][0]['label'] ?? '');

        $_POST['footer_tagline'] = 'Footer marker';
        $data = admin_apply_site_footer_post($data);
        $t->assertEquals('Footer marker', $data['footer']['tagline'] ?? '');

        json_write('site.json', $original);
        unset($_POST);
    });

    $t->test('admin_log_publish บันทึกประวัติ', function (TestRunner $t) {
        $original = json_read('publish-log.json');
        $_SESSION['admin_user'] = 'test-runner';
        admin_log_publish();
        $log = admin_list_publish_log();
        $t->assertTrue(count($log) > 0, 'ต้องมีรายการเผยแพร่');
        $t->assertEquals('test-runner', $log[0]['user'] ?? '');
        json_write('publish-log.json', $original);
    });
}
