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
$updated = false;

if ($section === 'socialLink') {
    $social = admin_normalize_social($data['social'] ?? []);
    if (isset($social['links'][$index])) {
        $social['links'][$index]['visible'] = !admin_footer_link_visible($social['links'][$index]);
        $data['social'] = $social;
        $updated = true;
    }
} elseif ($section === 'agentContact') {
    $agent = admin_normalize_agent_contacts($data['agent'] ?? []);
    if (isset($agent['extraContacts'][$index])) {
        $agent['extraContacts'][$index]['visible'] = !admin_footer_link_visible($agent['extraContacts'][$index]);
        $data['agent'] = $agent;
        $updated = true;
    }
}

if (!$updated) {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: ' . $return);
    exit;
}

admin_footer_publish_site($data);
admin_flash('success', 'อัปเดตสถานะและเผยแพร่ขึ้นเว็บแล้ว');
header('Location: ' . $return);
exit;
