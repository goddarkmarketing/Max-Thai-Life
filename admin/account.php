<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    try {
        $current = admin_post('current_password');
        $newPass = admin_post('new_password');
        $confirm = admin_post('confirm_password');
        if ($newPass !== $confirm) {
            throw new InvalidArgumentException('รหัสผ่านใหม่ไม่ตรงกัน');
        }
        if (!admin_save_password($current, $newPass)) {
            throw new InvalidArgumentException('รหัสผ่านปัจจุบันไม่ถูกต้อง');
        }
        admin_flash('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    } catch (Throwable $e) {
        admin_flash('error', $e->getMessage());
    }
    header('Location: account.php');
    exit;
}

admin_layout_start('บัญชีผู้ใช้', 'account.php');
?>

<?php admin_card_start('เปลี่ยนรหัสผ่าน'); ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <?php admin_field('รหัสผ่านปัจจุบัน', 'current_password', '', ['type' => 'password', 'required' => true]); ?>
  <?php admin_field('รหัสผ่านใหม่', 'new_password', '', ['type' => 'password', 'required' => true, 'hint' => 'อย่างน้อย 6 ตัวอักษร']); ?>
  <?php admin_field('ยืนยันรหัสผ่านใหม่', 'confirm_password', '', ['type' => 'password', 'required' => true]); ?>
  <?php admin_actions('dashboard.php'); ?>
</form>
<?php admin_card_end(); ?>

<?php admin_layout_end(); ?>
