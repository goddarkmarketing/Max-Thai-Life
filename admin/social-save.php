<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$return = 'site-social.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: ' . $return);
    exit;
}

$section = admin_post('section');
$index = admin_post('index', 'new');
$return = admin_post_return_page($return);
$data = json_read('site.json');

try {
    if ($section === 'socialLink') {
        $data['social'] = admin_social_save_link($data['social'] ?? [], $index, $_POST);
    } elseif ($section === 'agentContact') {
        $data['agent'] = admin_agent_contact_save_item($data['agent'] ?? [], $index, $_POST);
    } else {
        throw new RuntimeException('คำขอไม่ถูกต้อง');
    }
    admin_footer_publish_site($data);
    admin_flash('success', 'บันทึกและเผยแพร่แล้ว');
} catch (RuntimeException $e) {
    admin_flash('error', $e->getMessage());
}

header('Location: ' . $return);
exit;
