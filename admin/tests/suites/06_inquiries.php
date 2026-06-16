<?php
declare(strict_types=1);

function suite_inquiries(TestRunner $t, ?HttpClient $http = null): void
{
    echo "\n[6] ฟอร์มติดต่อ — API และหลังบ้าน\n";

    $t->test('admin_save_lead บันทึกและอ่านได้', function (TestRunner $t) {
        $original = json_read('leads.json');
        $id = 'test_' . bin2hex(random_bytes(4));
        $name = test_marker('LEAD_NAME');
        admin_save_lead([
            'id' => $id,
            'topic' => 'quote',
            'topicLabel' => 'ขอใบเสนอเบี้ย',
            'name' => $name,
            'phone' => '0899999999',
            'email' => 'test@example.com',
            'message' => 'ข้อความทดสอบ',
            'source' => 'contact',
            'status' => 'new',
            'createdAt' => date('c'),
            'ip' => '127.0.0.1',
        ]);
        $leads = admin_load_leads();
        $found = null;
        foreach ($leads['items'] ?? [] as $item) {
            if (($item['id'] ?? '') === $id) {
                $found = $item;
                break;
            }
        }
        $t->assertTrue($found !== null, 'ต้องพบ lead ใน leads.json');
        $t->assertEquals($name, $found['name'] ?? null);
        $t->assertEquals('new', $found['status'] ?? null);

        admin_update_lead($id, 'read');
        $leads = admin_load_leads();
        foreach ($leads['items'] ?? [] as $item) {
            if (($item['id'] ?? '') === $id) {
                $t->assertEquals('read', $item['status'] ?? null);
            }
        }

        admin_delete_lead($id);
        json_write('leads.json', $original);
    });

    if ($http === null || !$http->ping()) {
        $t->test('API inquiry-submit.php', function (TestRunner $t) {
            $t->skip('ต้องเปิด Apache สำหรับทดสอบ API');
        });
        return;
    }

    $t->test('API inquiry-submit.php รับข้อความจากหน้าบ้าน', function (TestRunner $t) use ($http) {
        $original = json_read('leads.json');
        $name = test_marker('API_LEAD');
        $res = $http->postJson('/api/inquiry-submit.php', [
            'topic' => 'insurance',
            'name' => $name,
            'phone' => '0812345678',
            'email' => '',
            'message' => 'ทดสอบส่งจาก API',
            'source' => 'contact',
        ]);
        $t->assertEquals(200, $res['code']);
        $body = json_decode($res['body'], true);
        $t->assertTrue(is_array($body) && ($body['ok'] ?? false) === true, $res['body']);

        $leads = admin_load_leads();
        $found = false;
        $foundId = null;
        foreach ($leads['items'] ?? [] as $item) {
            if (($item['name'] ?? '') === $name) {
                $found = true;
                $foundId = $item['id'] ?? null;
                break;
            }
        }
        $t->assertTrue($found, 'ต้องบันทึกใน leads.json');

        if ($foundId) {
            admin_delete_lead($foundId);
        }
        json_write('leads.json', $original);
    });

    $t->test('API ปฏิเสธข้อมูลไม่ครบ', function (TestRunner $t) use ($http) {
        $res = $http->postJson('/api/inquiry-submit.php', [
            'topic' => 'insurance',
            'name' => '',
            'phone' => '0812345678',
        ]);
        $t->assertTrue($res['code'] === 400, 'ต้องตอบ 400');
        $body = json_decode($res['body'], true);
        $t->assertFalse($body['ok'] ?? true);
    });

    $http->login();
    $t->test('หน้า inquiries.php แสดงรายการข้อความ', function (TestRunner $t) use ($http) {
        $res = $http->getAdminPage('inquiries.php');
        $t->assertEquals(200, $res['code']);
        $t->assertContains('ข้อความติดต่อ', $res['body']);
    });
}
