<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/landing-pages.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('pages.json');
$data = admin_normalize_pages_data($data);
json_write('pages.json', $data);

$meta = admin_landing_page_meta();

admin_layout_start('หน้าเว็บ', 'landing-pages.php');
?>

<?php admin_card_start('แก้ไขหน้าเว็บแบบ Visual', 'คลิกแก้ไขหน้า — สลับตำแหน่ง เพิ่มข้อความและรูปภาพได้เหมือนแผนประกัน'); ?>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>หน้า</th>
        <th>ไฟล์</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (admin_landing_page_keys() as $key): ?>
        <?php $m = $meta[$key]; ?>
        <tr>
          <td><strong><?= admin_h($m['label']) ?></strong></td>
          <td><code><?= admin_h($m['file']) ?></code></td>
          <td>
            <div class="admin-table-actions">
              <a href="page-visual.php?page=<?= admin_h($key) ?>" class="admin-btn admin-btn--primary admin-btn--sm">แก้ไขหน้า</a>
              <a href="../<?= admin_h($m['file']) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูหน้า</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="admin-hint">รายการบทความ/ข่าว/อาชีพ/รีวิวเคลม แก้ที่เมนูเนื้อหา — หน้านี้จัดการโครงสร้างและข้อความของหน้ารวม</p>
<?php admin_card_end(); ?>

<div class="admin-form-actions">
  <a href="publish.php" class="admin-btn admin-btn--primary">เผยแพร่ขึ้นเว็บ</a>
  <a href="dashboard.php" class="admin-btn admin-btn--ghost">กลับแดชบอร์ด</a>
</div>

<?php admin_layout_end(); ?>
