<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$types = admin_content_types();
$type = $_GET['type'] ?? 'articles';
if (!isset($types[$type])) {
    $type = 'articles';
}
$cfg = $types[$type];
$store = json_read($cfg['file']);
$items = $store[$cfg['itemsKey']] ?? [];
$previewType = $type === 'claims' ? 'claims' : $type;
$hasVisual = in_array($type, ['articles', 'news'], true);

admin_layout_start($cfg['label'], 'content-list.php?type=' . $type);
?>

<?php admin_card_start('รายการ' . $cfg['label'], 'คลิกแก้ไขแต่ละรายการ'); ?>
<div class="admin-list-toolbar">
  <a href="content-edit.php?type=<?= admin_h($type) ?>&id=new" class="admin-btn admin-btn--primary">+ เพิ่ม<?= admin_h($cfg['label']) ?></a>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหาชื่อ…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table" data-searchable>
    <thead>
      <tr>
        <th>ชื่อ</th>
        <th>Slug</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $slug => $item): ?>
        <?php $visible = admin_is_visible($item); ?>
        <tr data-search-text="<?= admin_h(strtolower(($item['title'] ?? $slug) . ' ' . $slug . ' ' . ($item['category'] ?? ''))) ?>">
          <td><strong><?= admin_h($item['title'] ?? $slug) ?></strong></td>
          <td><code><?= admin_h($slug) ?></code></td>
          <td>
            <?php if ($visible): ?>
              <span class="admin-badge admin-badge--ok">เผยแพร่</span>
            <?php else: ?>
              <span class="admin-badge admin-badge--muted">ซ่อน</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="admin-table-actions">
              <?php if ($type !== 'claims'): ?>
                <a href="<?= admin_h(admin_content_preview_url($previewType, $slug)) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูหน้า</a>
              <?php endif; ?>
              <?php if ($hasVisual): ?>
                <a href="content-visual.php?type=<?= admin_h($type) ?>&id=<?= admin_h($slug) ?>" class="admin-btn admin-btn--primary admin-btn--sm">แก้ไขหน้า</a>
              <?php endif; ?>
              <a href="content-edit.php?type=<?= admin_h($type) ?>&id=<?= admin_h($slug) ?>" class="admin-btn admin-btn--secondary admin-btn--sm">ฟอร์ม</a>
              <form method="post" action="toggle-visible.php" class="admin-inline-form">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="kind" value="<?= admin_h($type) ?>">
                <input type="hidden" name="slug" value="<?= admin_h($slug) ?>">
                <input type="hidden" name="back" value="content-list.php?type=<?= admin_h($type) ?>">
                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm"><?= $visible ? 'ซ่อน' : 'แสดง' ?></button>
              </form>
              <form method="post" action="content-delete.php" class="admin-delete-form" onsubmit="return confirm('ลบ <?= admin_h($item['title'] ?? $slug) ?> ?');">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="type" value="<?= admin_h($type) ?>">
                <input type="hidden" name="slug" value="<?= admin_h($slug) ?>">
                <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_card_end(); ?>

<?php if ($type === 'news' || $type === 'careers' || $type === 'claims'): ?>
<?php admin_card_start('ลำดับการแสดง', 'slug คั่นด้วยบรรทัดใหม่'); ?>
<form method="post" action="content-order.php">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="type" value="<?= admin_h($type) ?>">
  <?php
    $listKey = $type === 'claims' ? 'list' : 'list';
    $list = $store[$listKey] ?? array_keys($items);
    admin_field('ลำดับ (slug)', 'order_text', implode("\n", $list), ['type' => 'textarea', 'rows' => 8]);
  ?>
  <?php admin_actions('content-list.php?type=' . $type); ?>
</form>
<?php admin_card_end(); ?>
<?php endif; ?>

<?php admin_layout_end(); ?>
