<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $action = admin_post('action');
    $id = admin_post('id');
    try {
        if ($action === 'done') {
            admin_update_lead($id, 'done');
            admin_flash('success', 'ทำเครื่องหมายว่าติดต่อแล้ว');
        } elseif ($action === 'reopen') {
            admin_update_lead($id, 'new');
            admin_flash('success', 'เปิดใหม่แล้ว');
        } elseif ($action === 'delete') {
            admin_delete_lead($id);
            admin_flash('success', 'ลบข้อความแล้ว');
        }
    } catch (Throwable $e) {
        admin_flash('error', $e->getMessage());
    }
    header('Location: inquiries.php');
    exit;
}

$filter = $_GET['status'] ?? 'all';
$data = admin_load_leads();
$items = $data['items'] ?? [];

if ($filter === 'new') {
    $items = array_values(array_filter($items, static fn ($i) => ($i['status'] ?? '') === 'new'));
} elseif ($filter === 'done') {
    $items = array_values(array_filter($items, static fn ($i) => ($i['status'] ?? '') === 'done'));
}

$newCount = admin_count_new_leads();

admin_layout_start('ข้อความติดต่อ', 'inquiries.php');
?>

<div class="admin-stats">
  <div class="admin-stat">
    <div class="admin-stat-value"><?= $newCount ?></div>
    <div class="admin-stat-label">รอติดต่อ</div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat-value"><?= count($data['items'] ?? []) ?></div>
    <div class="admin-stat-label">ทั้งหมด</div>
  </div>
</div>

<div class="admin-tabs">
  <a href="inquiries.php" class="admin-tab<?= $filter === 'all' ? ' is-active' : '' ?>">ทั้งหมด</a>
  <a href="inquiries.php?status=new" class="admin-tab<?= $filter === 'new' ? ' is-active' : '' ?>">รอติดต่อ<?= $newCount > 0 ? ' (' . $newCount . ')' : '' ?></a>
  <a href="inquiries.php?status=done" class="admin-tab<?= $filter === 'done' ? ' is-active' : '' ?>">ติดต่อแล้ว</a>
</div>

<?php admin_card_start('รายการข้อความ'); ?>

<?php if ($items === []): ?>
  <p class="admin-hint">ยังไม่มีข้อความ — ลูกค้าส่งผ่านฟอร์มหน้าแรกหรือหน้าติดต่อ</p>
<?php else: ?>
  <div class="admin-inquiry-list">
    <?php foreach ($items as $item): ?>
      <?php
      $isNew = ($item['status'] ?? '') === 'new';
      $created = admin_format_datetime_th($item['createdAt'] ?? '');
      ?>
      <article class="admin-inquiry-item<?= $isNew ? ' is-new' : '' ?>">
        <header class="admin-inquiry-head">
          <div>
            <strong><?= admin_h($item['name'] ?? '') ?></strong>
            <?php if ($isNew): ?><span class="admin-badge admin-badge--new">ใหม่</span><?php endif; ?>
            <div class="admin-hint"><?= admin_h($created) ?> · <?= admin_h($item['topicLabel'] ?? '') ?> · <?= admin_h($item['source'] === 'home' ? 'หน้าแรก' : 'หน้าติดต่อ') ?></div>
          </div>
          <div class="admin-table-actions">
            <?php if ($isNew): ?>
              <form method="post" class="admin-inline-form">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="done">
                <input type="hidden" name="id" value="<?= admin_h($item['id'] ?? '') ?>">
                <button type="submit" class="admin-btn admin-btn--secondary admin-btn--sm">ติดต่อแล้ว</button>
              </form>
            <?php else: ?>
              <form method="post" class="admin-inline-form">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="reopen">
                <input type="hidden" name="id" value="<?= admin_h($item['id'] ?? '') ?>">
                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">เปิดใหม่</button>
              </form>
            <?php endif; ?>
            <form method="post" class="admin-inline-form" onsubmit="return confirm('ลบข้อความนี้?');">
              <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= admin_h($item['id'] ?? '') ?>">
              <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
            </form>
          </div>
        </header>
        <dl class="admin-inquiry-meta">
          <div><dt>โทร</dt><dd><a href="tel:<?= admin_h($item['phone'] ?? '') ?>"><?= admin_h($item['phone'] ?? '') ?></a></dd></div>
          <?php if (!empty($item['email'])): ?>
            <div><dt>อีเมล</dt><dd><a href="mailto:<?= admin_h($item['email']) ?>"><?= admin_h($item['email']) ?></a></dd></div>
          <?php endif; ?>
        </dl>
        <?php if (!empty($item['message'])): ?>
          <p class="admin-inquiry-message"><?= nl2br(admin_h($item['message'])) ?></p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_card_end(); ?>

<?php admin_layout_end(); ?>
