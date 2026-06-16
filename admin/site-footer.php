<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $data = admin_apply_site_footer_post($data);
    json_write('site.json', $data);
    admin_flash('success', 'บันทึก Footer แล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
    header('Location: site-footer.php');
    exit;
}

$footer = $data['footer'] ?? [];
$planLinks = $footer['planLinks'] ?? [];
if ($planLinks === []) {
    $planLinks = [['label' => '', 'href' => '']];
}

admin_layout_start('Footer', 'site-footer.php');
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">

  <?php admin_card_start('Footer'); ?>
  <?php admin_field('Footer tagline', 'footer_tagline', $footer['tagline'] ?? ''); ?>
  <?php admin_card_end(); ?>

  <?php admin_card_start('ลิงก์แผนประกัน', 'คอลัมน์แผนประกันแนะนำในส่วนท้ายเว็บ'); ?>
  <?php admin_render_link_repeater('ลิงก์', 'footer', $planLinks); ?>
  <?php admin_card_end(); ?>

  <?php admin_actions('dashboard.php'); ?>
</form>

<?php admin_layout_end(); ?>
