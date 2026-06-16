<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/generate-js.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $action = admin_post('action');
    $backupId = admin_post('backup_id');
    $file = admin_post('file');
    try {
        if ($action === 'create') {
            $newId = admin_create_manual_backup();
            admin_flash('success', 'สร้างไฟล์สำรองแล้ว · ' . admin_format_backup_datetime($newId));
        } elseif ($action === 'restore_all') {
            admin_restore_backup($backupId);
            generate_all_js();
            admin_flash('success', 'กู้คืนข้อมูลทั้งหมดแล้ว — ตรวจสอบหน้าเว็บก่อนเผยแพร่');
        } elseif ($action === 'restore_file') {
            admin_restore_backup($backupId, $file);
            generate_all_js();
            admin_flash('success', 'กู้คืน ' . $file . ' แล้ว');
        } elseif ($action === 'delete') {
            admin_delete_backup($backupId);
            admin_flash('success', 'ลบไฟล์สำรองแล้ว');
        } else {
            throw new InvalidArgumentException('คำขอไม่ถูกต้อง');
        }
    } catch (Throwable $e) {
        admin_flash('error', $e->getMessage());
    }
    header('Location: backups.php');
    exit;
}

$backups = admin_list_backups();

admin_layout_start('สำรอง / กู้คืน', 'backups.php');
?>

<?php admin_card_start('สำรองข้อมูล', 'สำรองอัตโนมัติทุกครั้งที่บันทึก JSON · หรือกดสร้างด้วยตนเอง'); ?>
<form method="post" class="admin-backup-create">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="action" value="create">
  <button type="submit" class="admin-btn admin-btn--primary">+ สร้างไฟล์สำรองตอนนี้</button>
  <p class="admin-hint admin-hint--tight">บันทึกไฟล์ JSON ทั้งหมดในโฟลเดอร์ <code>data/</code> พร้อมวันที่และเวลา</p>
</form>
<?php admin_card_end(); ?>

<?php admin_card_start('รายการสำรอง'); ?>
<?php if ($backups === []): ?>
  <p class="admin-hint">ยังไม่มีไฟล์สำรอง — กดปุ่มด้านบนเพื่อสร้างชุดแรก</p>
<?php else: ?>
  <div class="admin-backup-list">
    <?php foreach ($backups as $b): ?>
      <article class="admin-backup-item">
        <header class="admin-backup-item-head">
          <div>
            <strong><?= admin_h($b['label']) ?></strong>
            <div class="admin-hint"><code><?= admin_h($b['id']) ?></code> · <?= (int) $b['count'] ?> ไฟล์</div>
          </div>
          <div class="admin-table-actions">
            <a href="backup-download.php?id=<?= urlencode($b['id']) ?>&file=all.zip" class="admin-btn admin-btn--secondary admin-btn--sm">ดาวน์โหลดทั้งชุด (.zip)</a>
            <form method="post" class="admin-inline-form" onsubmit="return confirm('กู้คืนข้อมูลทั้งหมดจากชุดนี้? ข้อมูลปัจจุบันจะถูกแทนที่');">
              <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
              <input type="hidden" name="action" value="restore_all">
              <input type="hidden" name="backup_id" value="<?= admin_h($b['id']) ?>">
              <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">กู้คืน</button>
            </form>
            <form method="post" class="admin-inline-form" onsubmit="return confirm('ลบไฟล์สำรองชุดนี้ถาวร?');">
              <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="backup_id" value="<?= admin_h($b['id']) ?>">
              <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
            </form>
          </div>
        </header>
        <ul class="admin-backup-files">
          <?php foreach ($b['files'] as $fname): ?>
            <li>
              <span><?= admin_h($fname) ?></span>
              <a href="backup-download.php?id=<?= urlencode($b['id']) ?>&file=<?= urlencode($fname) ?>" class="admin-btn admin-btn--ghost admin-btn--sm">ดาวน์โหลด</a>
            </li>
          <?php endforeach; ?>
        </ul>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_card_end(); ?>

<?php admin_layout_end(); ?>
