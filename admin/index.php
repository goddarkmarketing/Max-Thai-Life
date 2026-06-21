<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/generate-js.php';

if (admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$brand = admin_brand_meta();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = admin_post('username');
    $pass = admin_post('password');
    if (admin_login($user, $pass)) {
        if (!json_installed()) {
            admin_run_import();
            generate_all_js();
        }
        header('Location: dashboard.php');
        exit;
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ | <?= admin_h($brand['name']) ?> Admin</title>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-login-page">
  <div class="admin-login-wrap">
    <div class="admin-login-card">
      <div class="admin-login-brand">
        <img src="<?= admin_h(admin_brand_logo_url()) ?>" alt="<?= admin_h($brand['name']) ?>" class="admin-brand-logo" width="46" height="46">
        <h1><?= admin_h($brand['name']) ?></h1>
        <p>ระบบจัดการเว็บไซต์</p>
      </div>
      <?php if ($error !== ''): ?>
        <div class="admin-login-error"><?= admin_h($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="admin-field">
          <label class="admin-label" for="username">ชื่อผู้ใช้</label>
          <input class="admin-input" type="text" id="username" name="username" required autocomplete="username">
        </div>
        <div class="admin-field">
          <label class="admin-label" for="password">รหัสผ่าน</label>
          <input class="admin-input" type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="admin-btn admin-btn--primary admin-btn--block">เข้าสู่ระบบ</button>
      </form>
    </div>
  </div>
</body>
</html>
