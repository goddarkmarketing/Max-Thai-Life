<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $data = admin_apply_site_seo_post($data);
    json_write('site.json', $data);
    admin_flash('success', 'บันทึก SEO แล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
    header('Location: site-seo.php');
    exit;
}

$meta = $data['meta'] ?? [];

admin_layout_start('SEO', 'site-seo.php');
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">

  <?php admin_card_start('SEO & การติดตาม'); ?>
  <?php admin_field('Meta Description', 'meta_description', $meta['description'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
  <?php admin_image_field('รูป Open Graph', 'meta_og_image', $meta['ogImage'] ?? '', 'logo'); ?>
  <?php admin_field('Google Analytics ID', 'meta_analytics_id', $meta['analyticsId'] ?? '', ['hint' => 'เช่น G-XXXXXXXXXX · เว้นว่างถ้าไม่ใช้']); ?>
  <?php admin_card_end(); ?>

  <?php admin_actions('dashboard.php'); ?>
</form>

<?php admin_layout_end(); ?>
