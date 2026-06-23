<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

admin_require_login();

$return = admin_post_return_page('site-social.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    admin_flash('error', 'คำขอไม่ถูกต้อง');
    header('Location: ' . $return);
    exit;
}

$section = admin_post('section');
$index = (int) admin_post('index', '-1');
$data = json_read('site.json');
$removed = false;

if ($section === 'socialLink') {
    $social = admin_normalize_social($data['social'] ?? []);
    if (isset($social['links'][$index])) {
        array_splice($social['links'], $index, 1);
        $data['social'] = $social;
        $removed = true;
    }
} elseif ($section === 'agentContact') {
    $agent = admin_normalize_agent_contacts($data['agent'] ?? []);
    if (isset($agent['extraContacts'][$index])) {
        array_splice($agent['extraContacts'], $index, 1);
        $data['agent'] = $agent;
        $removed = true;
    }
}

if (!$removed) {
    admin_flash('error', 'ไม่พบรายการที่จะลบ');
    header('Location: ' . $return);
    exit;
}

admin_footer_publish_site($data);
admin_flash('success', 'ลบรายการและเผยแพร่ขึ้นเว็บแล้ว');
header('Location: ' . $return);
exit;
