<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$articles = json_read('articles.json');
$plans = json_read('plans.json');
$news = json_read('news.json');
$careers = json_read('careers.json');
$claims = json_read('claim-reviews.json');

$articleCount = count($articles['items'] ?? []);
$planCount = count($plans['items'] ?? []);
$newsCount = count($news['items'] ?? []);
$careerCount = count($careers['items'] ?? []);
$claimCount = count($claims['items'] ?? []);

admin_layout_start('ภาพรวม', 'dashboard.php');
?>

<div class="admin-stats">
  <div class="admin-stat">
    <div class="admin-stat-value"><?= $planCount ?></div>
    <div class="admin-stat-label">แผนประกัน</div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-value"><?= $articleCount ?></div>
    <div class="admin-stat-label">บทความ</div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-value"><?= $newsCount ?></div>
    <div class="admin-stat-label">ข่าว/กิจกรรม</div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-value"><?= $careerCount + $claimCount ?></div>
    <div class="admin-stat-label">อาชีพ + รีวิวเคลม</div>
  </div>
</div>

<?php admin_card_start('ทางลัด', 'เลือกส่วนที่ต้องการแก้ไข'); ?>
<div class="admin-quick-links">
  <?php
  admin_render_quick_link('analytics.php', 'eye', 'สถิติการเข้าชม', 'ยอดวิวจริง แยกตามบทความ แผน ข่าว อาชีพ');
  admin_render_quick_link('site.php', 'settings', 'ตั้งค่าเว็บไซต์', 'ชื่อแบรนด์ ตัวแทน โทรศัพท์ โซเชียล');
  admin_render_quick_link('home.php', 'home', 'หน้าแรก', 'แบนเนอร์ โปรไฟล์ รีวิวลูกค้า');
  admin_render_quick_link('plans-list.php', 'shield', 'แผนประกัน', 'การ์ดแผน + รายละเอียดครบ');
  admin_render_quick_link('content-list.php?type=articles', 'article', 'บทความ', 'แก้ไขบทความทั้งหมด');
  admin_render_quick_link('content-list.php?type=news', 'news', 'ข่าว/กิจกรรม', 'ข่าวและลำดับการแสดง');
  admin_render_quick_link('inquiries.php', 'mail', 'ข้อความติดต่อ', 'ฟอร์มจากหน้าแรกและหน้าติดต่อ');
  admin_render_quick_link('site-nav.php', 'menu', 'เมนูเว็บไซต์', 'จัดลำดับและซ่อน/แสดงเมนู');
  admin_render_quick_link('media.php', 'image', 'คลังรูป', 'อัปโหลด ดู และลบรูป');
  admin_render_quick_link('backups.php', 'backup', 'สำรอง/กู้คืน', 'Snapshot เต็ม JSON + JS + รูป');
  admin_render_quick_link('account.php', 'user', 'บัญชีผู้ใช้', 'เปลี่ยนรหัสผ่าน');
  ?>
</div>
<?php admin_card_end(); ?>

<?php admin_card_start('วิธีใช้งาน'); ?>
<ol class="admin-steps-list">
  <li>เลือกเมนูด้านซ้ายเพื่อแก้ไขเนื้อหา</li>
  <li>กด <strong>บันทึก</strong> ในแต่ละหน้าเพื่อเก็บข้อมูล</li>
  <li>กด <strong>เผยแพร่ขึ้นเว็บ</strong> เพื่ออัปเดตหน้าเว็บจริง</li>
  <li>ส่วนอัปโหลดรูปจะแสดงขนาดภาพที่แนะนำกำกับไว้</li>
</ol>
<?php admin_card_end(); ?>

<?php
$publishLog = admin_list_publish_log();
if ($publishLog !== []):
?>
<?php admin_card_start('ประวัติการเผยแพร่'); ?>
<ul class="admin-publish-log">
  <?php foreach (array_slice($publishLog, 0, 8) as $entry): ?>
    <li>
      <strong><?= admin_h(admin_format_datetime_th($entry['at'] ?? '')) ?></strong>
      <span class="admin-hint">โดย <?= admin_h($entry['user'] ?? 'admin') ?></span>
    </li>
  <?php endforeach; ?>
</ul>
<?php admin_card_end(); ?>
<?php endif; ?>

<?php admin_layout_end(); ?>
