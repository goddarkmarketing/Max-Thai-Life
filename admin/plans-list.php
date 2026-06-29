<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$plans = json_read('plans.json');
$items = admin_sort_pinned_list($plans['items'] ?? []);

$categoryFilter = trim($_GET['category'] ?? '');
$categoryIds = array_column(admin_plan_categories(), 'id');
if ($categoryFilter !== '' && !in_array($categoryFilter, $categoryIds, true)) {
    $categoryFilter = '';
}
if ($categoryFilter !== '') {
    $items = array_values(array_filter($items, static fn(array $plan): bool => ($plan['category'] ?? '') === $categoryFilter));
}

$listUrl = admin_plans_list_url($categoryFilter !== '' ? $categoryFilter : null);
$activeNav = admin_plans_active_nav($categoryFilter !== '' ? $categoryFilter : null);
$pageTitle = $categoryFilter !== ''
    ? 'แผนประกัน · ' . admin_plan_category_label($categoryFilter)
    : 'แผนประกัน';

admin_layout_start($pageTitle, $activeNav);
?>

<?php admin_card_start('รายการแผนประกัน' . ($categoryFilter !== '' ? ' · ' . admin_plan_category_label($categoryFilter) : '')); ?>
<div class="admin-list-toolbar">
  <a href="plan-edit.php?slug=new<?= $categoryFilter !== '' ? '&category=' . admin_h($categoryFilter) : '' ?>" class="admin-btn admin-btn--primary">+ เพิ่มแผนประกัน</a>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหาชื่อแผน…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table" data-searchable>
    <thead>
      <tr>
        <th>ชื่อแผน</th>
        <th>หมวด</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $plan): ?>
        <?php
          $href = $plan['href'] ?? '';
          $slug = preg_replace('#^plans/|\.html$#', '', $href);
          $visible = admin_is_visible($plan);
          $pinned = admin_is_pinned($plan);
        ?>
        <tr data-search-text="<?= admin_h(strtolower(($plan['title'] ?? '') . ' ' . ($plan['category'] ?? '') . ' ' . ($plan['tag'] ?? ''))) ?>">
          <td>
            <?php if ($pinned): ?><span class="admin-pin-flag" title="ปักหมุด" aria-label="ปักหมุด">📌</span> <?php endif; ?>
            <strong><?= admin_h($plan['title'] ?? '') ?></strong><br><small><?= admin_h($plan['tag'] ?? '') ?></small>
          </td>
          <td><?= admin_h($plan['category'] ?? '') ?></td>
          <td>
            <?php if ($visible): ?>
              <span class="admin-badge admin-badge--ok">เผยแพร่</span>
            <?php else: ?>
              <span class="admin-badge admin-badge--muted">ซ่อน</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="admin-table-actions">
              <a href="<?= admin_h(admin_content_preview_url('plans', $slug)) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูหน้า</a>
              <a href="plan-edit.php?slug=<?= admin_h($slug) ?>" class="admin-btn admin-btn--secondary admin-btn--sm">การ์ด</a>
              <?php $planUsesRichtext = admin_plan_uses_richtext($slug); ?>
              <a href="<?= admin_h(admin_plan_edit_content_url($slug)) ?>" class="admin-btn admin-btn--primary admin-btn--sm"><?= $planUsesRichtext ? 'แก้ไขเนื้อหา' : 'แก้ไขหน้า' ?></a>
              <form method="post" action="pin-toggle.php" class="admin-inline-form">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="kind" value="plan">
                <input type="hidden" name="slug" value="<?= admin_h($slug) ?>">
                <input type="hidden" name="back" value="<?= admin_h($listUrl) ?>">
                <button type="submit" class="admin-btn admin-btn--sm <?= $pinned ? 'admin-btn--secondary' : 'admin-btn--ghost' ?>"><?= $pinned ? 'เลิกปัก' : 'ปักหมุด' ?></button>
              </form>
              <form method="post" action="toggle-visible.php" class="admin-inline-form">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="kind" value="plan">
                <input type="hidden" name="slug" value="<?= admin_h($slug) ?>">
                <input type="hidden" name="back" value="<?= admin_h($listUrl) ?>">
                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm"><?= $visible ? 'ซ่อน' : 'แสดง' ?></button>
              </form>
              <form method="post" action="plan-delete.php" class="admin-delete-form" onsubmit="return confirm('ลบแผน <?= admin_h($plan['title'] ?? $slug) ?> ?');">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
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

<?php admin_layout_end(); ?>
