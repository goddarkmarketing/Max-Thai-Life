<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $data = admin_apply_site_navigation_post($data);
    json_write('site.json', $data);
    admin_flash('success', 'บันทึกเมนูเว็บแล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
    header('Location: site-nav.php');
    exit;
}

$navigation = $data['navigation'] ?? admin_default_navigation();

admin_layout_start('เมนูเว็บ', 'site-nav.php');
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">

  <?php admin_card_start('เมนูนำทางเว็บไซต์', 'ลำดับเมนูด้านบนทุกหน้า · ลากไม่ได้ — เรียงจากบนลงล่าง'); ?>
  <?php admin_render_nav_repeater($navigation); ?>
  <?php admin_card_end(); ?>

  <?php admin_actions('dashboard.php'); ?>
</form>

<?php admin_layout_end(); ?>
