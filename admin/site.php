<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $data = admin_apply_site_general_post($data);
    json_write('site.json', $data);
    admin_flash('success', 'บันทึกตั้งค่าเว็บไซต์แล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
    header('Location: site.php');
    exit;
}

$brand = $data['brand'] ?? [];
$agent = $data['agent'] ?? [];
$social = $data['social'] ?? [];

admin_layout_start('ตั้งค่าเว็บไซต์', 'site.php');
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">

  <?php admin_card_start('แบรนด์และโลโก้'); ?>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('ชื่อเว็บไซต์', 'brand_name', $brand['name'] ?? ''); ?>
    <?php admin_field('คำบรรยายใต้ชื่อ', 'brand_sub', $brand['sub'] ?? ''); ?>
  </div>
  <?php admin_image_field('โลโก้', 'brand_logo', $brand['logo'] ?? '', 'logo'); ?>
  <?php admin_card_end(); ?>

  <?php admin_card_start('ข้อมูลตัวแทน', 'ใช้ทั้งหน้าแรก footer และหน้าติดต่อ'); ?>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('ชื่อ-นามสกุล', 'agent_name', $agent['name'] ?? ''); ?>
    <?php admin_field('ตำแหน่ง', 'agent_title', $agent['title'] ?? ''); ?>
    <?php admin_field('สาขา', 'agent_branch', $agent['branch'] ?? ''); ?>
    <?php admin_field('เบอร์โทร (แสดงผล)', 'agent_phone_display', $agent['phoneDisplay'] ?? ''); ?>
    <?php admin_field('เบอร์โทร (ลิงก์)', 'agent_phone', $agent['phone'] ?? '', ['hint' => 'ตัวเลขเท่านั้น เช่น 0852925320']); ?>
    <?php admin_field('เลขใบอนุญาต', 'agent_license', $agent['license'] ?? ''); ?>
    <?php admin_field('สิทธิ์ UL', 'agent_ul', $agent['ulRights'] ?? ''); ?>
  </div>
  <?php admin_field('ข้อความแนะนำ (Hero)', 'agent_tagline', $agent['tagline'] ?? '', ['type' => 'textarea', 'rows' => 2]); ?>
  <?php admin_card_end(); ?>

  <?php admin_card_start('โซเชียล'); ?>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('Facebook URL', 'social_facebook', $social['facebook'] ?? ''); ?>
    <?php admin_field('LINE URL', 'social_line', $social['line'] ?? ''); ?>
    <?php admin_field('อีเมล', 'social_email', $social['email'] ?? '', ['type' => 'email']); ?>
  </div>
  <?php admin_card_end(); ?>

  <?php admin_actions('dashboard.php'); ?>
</form>

<?php admin_layout_end(); ?>
