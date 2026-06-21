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
    try {
        if ($action === 'create') {
            $newId = admin_create_manual_backup();
            $manifest = admin_read_backup_manifest(BACKUP_PATH . '/' . $newId);
            $size = admin_format_bytes((int) ($manifest['totalBytes'] ?? 0));
            $count = (int) ($manifest['totalFiles'] ?? 0);
            $max = admin_backup_max_count();
            admin_flash('success', 'สร้างสำรองเต็มแล้ว · ' . admin_format_backup_datetime($newId) . " · {$count} ไฟล์ · {$size} · เก็บล่าสุด {$max} ชุด");
        } elseif ($action === 'restore_all') {
            admin_restore_backup($backupId);
            generate_all_js();
            admin_flash('success', 'กู้คืนข้อมูลทั้งหมดแล้ว — ตรวจสอบหน้าเว็บก่อนเผยแพร่');
        } elseif ($action === 'delete') {
            admin_delete_backup($backupId);
            admin_flash('success', 'ลบไฟล์สำรองแล้ว');
        } elseif ($action === 'delete_all') {
            $removed = admin_delete_all_backups();
            admin_flash('success', 'ลบไฟล์สำรองทั้งหมดแล้ว · ' . $removed . ' ชุด');
        } else {
            throw new InvalidArgumentException('คำขอไม่ถูกต้อง');
        }
    } catch (Throwable $e) {
        admin_flash('error', $e->getMessage());
    }
    header('Location: backups.php');
    exit;
}

$pruned = admin_prune_backups();
if ($pruned > 0) {
    admin_flash('success', 'ลบชุดสำรองเก่าอัตโนมัติ ' . $pruned . ' ชุด — เก็บไว้ล่าสุด ' . admin_backup_max_count() . ' ชุด');
}

$backups = admin_list_backups();
$maxBackups = admin_backup_max_count();

admin_layout_start('สำรอง / กู้คืน', 'backups.php');
?>

<?php admin_card_start('สำรองข้อมูล', 'Snapshot เต็ม 100% · เก็บสูงสุด ' . $maxBackups . ' ชุด (ลบชุดเก่าอัตโนมัติ)'); ?>
<form method="post" class="admin-backup-create">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="action" value="create">
  <button type="submit" class="admin-btn admin-btn--primary">+ สร้างไฟล์สำรองตอนนี้</button>
  <p class="admin-hint admin-hint--tight">รวม JSON · JS หน้าบ้าน · รูป/วิดีโอ — ชุดที่เกิน <?= (int) $maxBackups ?> จะถูกลบอัตโนมัติ</p>
</form>
<?php admin_card_end(); ?>

<?php admin_card_start('รายการสำรอง', count($backups) . ' / ' . $maxBackups . ' ชุด'); ?>
<?php if ($backups !== []): ?>
  <div class="admin-backup-toolbar">
    <form method="post" class="admin-inline-form" onsubmit="return confirm('ลบไฟล์สำรองทั้งหมด <?= count($backups) ?> ชุดถาวร?');">
      <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
      <input type="hidden" name="action" value="delete_all">
      <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบทั้งหมด</button>
    </form>
  </div>
<?php endif; ?>
<?php if ($backups === []): ?>
  <p class="admin-hint">ยังไม่มีไฟล์สำรอง — กดปุ่มด้านบนเพื่อสร้างชุดแรก</p>
<?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-backup-table">
      <thead>
        <tr>
          <th>วันที่ / เวลา</th>
          <th>ประเภท</th>
          <th class="admin-backup-table-meta">ไฟล์</th>
          <th>ขนาด</th>
          <th class="admin-table-col-actions">การทำงาน</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($backups as $b):
          $counts = $b['counts'] ?? [];
          $summary = [];
          if (!empty($counts['data'])) {
              $summary[] = 'JSON ' . (int) $counts['data'];
          }
          if (!empty($counts['js'])) {
              $summary[] = 'JS ' . (int) $counts['js'];
          }
          if (!empty($counts['media'])) {
              $summary[] = 'รูป ' . (int) $counts['media'];
          }
          $summaryText = $summary !== [] ? implode(' · ', $summary) : (int) ($b['totalFiles'] ?? 0) . ' ไฟล์';
        ?>
        <tr>
          <td><strong><?= admin_h($b['label']) ?></strong></td>
          <td>
            <?php if ($b['isFull'] ?? false): ?>
              <span class="admin-badge admin-badge--ok">เต็ม</span>
            <?php else: ?>
              <span class="admin-badge admin-badge--muted">JSON</span>
            <?php endif; ?>
          </td>
          <td class="admin-backup-table-meta"><?= admin_h($summaryText) ?></td>
          <td><?= admin_h(admin_format_bytes((int) ($b['totalBytes'] ?? 0))) ?></td>
          <td>
            <div class="admin-table-actions admin-table-actions--tight">
              <a href="backup-download.php?id=<?= urlencode($b['id']) ?>&file=all.zip" class="admin-btn admin-btn--secondary admin-btn--sm" title="ดาวน์โหลด">ZIP</a>
              <form method="post" class="admin-inline-form" onsubmit="return confirm('กู้คืนข้อมูลจากชุดนี้? ข้อมูลปัจจุบันจะถูกแทนที่');">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="restore_all">
                <input type="hidden" name="backup_id" value="<?= admin_h($b['id']) ?>">
                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">กู้คืน</button>
              </form>
              <form method="post" class="admin-inline-form" onsubmit="return confirm('ลบชุดนี้ถาวร?');">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="backup_id" value="<?= admin_h($b['id']) ?>">
                <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php admin_card_end(); ?>

<?php admin_layout_end(); ?>
