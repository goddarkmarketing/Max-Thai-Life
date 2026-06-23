<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');
$navigation = $data['navigation'] ?? admin_default_navigation();
$csrf = admin_csrf_token();

function admin_nav_render_main_view_row(int $index, array $item): void
{
    $label = (string) ($item['label'] ?? '');
    $href = (string) ($item['href'] ?? '');
    $visible = admin_nav_item_visible($item);
    $search = strtolower($label . ' ' . $href);
    ?>
    <tr
      class="nav-row nav-row--main"
      data-nav-view
      data-nav-type="main"
      data-nav-index="<?= $index ?>"
      data-search-text="<?= admin_h($search) ?>"
    >
      <td class="nav-col-order">
        <div class="nav-col-order-inner">
          <button type="button" class="nav-drag-handle" data-nav-drag="main" aria-label="ลากสลับลำดับเมนู" title="ลากเพื่อสลับลำดับ">
            <?= admin_inline_drag_icon() ?>
          </button>
          <span class="nav-order-num" data-nav-order-num><?= $index + 1 ?></span>
        </div>
      </td>
      <td class="nav-col-label">
        <button type="button" class="nav-inline-trigger" data-nav-edit>
          <strong><?= admin_h($label) ?></strong>
        </button>
        <?php if (!empty($item['cta'])): ?>
          <span class="admin-badge admin-badge--muted">CTA</span>
        <?php endif; ?>
      </td>
      <td class="nav-col-href">
        <button type="button" class="nav-inline-trigger nav-inline-trigger--code" data-nav-edit>
          <code><?= admin_h($href) ?></code>
        </button>
      </td>
      <td class="nav-col-status">
        <button type="button" class="nav-status-trigger" data-nav-edit>
          <?php if ($visible): ?>
            <span class="admin-badge admin-badge--ok">แสดง</span>
          <?php else: ?>
            <span class="admin-badge admin-badge--muted">ซ่อน</span>
          <?php endif; ?>
        </button>
      </td>
      <td class="nav-col-actions">
        <div class="admin-table-actions admin-table-actions--tight">
          <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-nav-add-child data-parent-index="<?= $index ?>">+ เมนูย่อย</button>
          <?php if ($href !== ''): ?>
            <a href="<?= admin_h(admin_nav_preview_url($href)) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูลิงก์</a>
          <?php endif; ?>
          <form method="post" action="nav-toggle.php" class="admin-inline-form">
            <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
            <input type="hidden" name="index" value="<?= $index ?>">
            <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm"><?= $visible ? 'ซ่อน' : 'แสดง' ?></button>
          </form>
          <form method="post" action="nav-delete.php" class="admin-inline-form" onsubmit="return confirm('ลบเมนู <?= admin_h($label) ?> ?');">
            <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
            <input type="hidden" name="index" value="<?= $index ?>">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
          </form>
        </div>
      </td>
    </tr>
    <?php
}

function admin_nav_render_main_edit_row(int $index, array $item, bool $hidden = true): void
{
    $label = (string) ($item['label'] ?? '');
    $href = (string) ($item['href'] ?? '');
    $visible = admin_nav_item_visible($item);
    ?>
    <tr
      class="nav-row nav-row--edit"
      data-nav-edit-row
      data-nav-type="main"
      data-nav-index="<?= $index ?>"
      <?= $hidden ? 'hidden' : '' ?>
    >
      <td colspan="5">
        <form method="post" action="nav-save.php" class="nav-inline-edit">
          <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
          <input type="hidden" name="kind" value="main">
          <input type="hidden" name="index" value="<?= $index ?>">
          <div class="nav-inline-edit__fields">
            <label class="nav-inline-edit__field">
              <span class="nav-inline-edit__label">ชื่อเมนู</span>
              <input type="text" name="label" class="admin-input" value="<?= admin_h($label) ?>" required>
            </label>
            <label class="nav-inline-edit__field nav-inline-edit__field--grow">
              <span class="nav-inline-edit__label">ลิงก์</span>
              <input type="text" name="href" class="admin-input" value="<?= admin_h($href) ?>" required placeholder="about.html">
            </label>
            <div class="nav-inline-edit__checks">
              <label class="admin-check">
                <input type="checkbox" name="visible" value="1"<?= $visible ? ' checked' : '' ?>>
                แสดงในเมนู
              </label>
              <label class="admin-check">
                <input type="checkbox" name="cta" value="1"<?= !empty($item['cta']) ? ' checked' : '' ?>>
                ปุ่ม CTA
              </label>
            </div>
          </div>
          <div class="nav-inline-edit__actions">
            <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">บันทึก</button>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-nav-cancel>ยกเลิก</button>
          </div>
        </form>
      </td>
    </tr>
    <?php
}

function admin_nav_render_child_view_row(int $parentIndex, int $childIndex, array $child, string $parentLabel): void
{
    $label = (string) ($child['label'] ?? '');
    $href = (string) ($child['href'] ?? '');
    $search = strtolower($parentLabel . ' ' . $label . ' ' . $href);
    ?>
    <tr
      class="nav-row nav-row--child"
      data-nav-view
      data-nav-type="child"
      data-nav-index="<?= $parentIndex ?>"
      data-nav-child="<?= $childIndex ?>"
      data-search-text="<?= admin_h($search) ?>"
    >
      <td class="nav-col-order nav-col-order--child">
        <div class="nav-col-order-inner">
          <button type="button" class="nav-drag-handle nav-drag-handle--child" data-nav-drag="child" aria-label="ลากสลับลำดับเมนูย่อย" title="ลากเพื่อสลับลำดับ">
            <?= admin_inline_drag_icon() ?>
          </button>
        </div>
      </td>
      <td class="nav-col-label nav-col-label--child">
        <button type="button" class="nav-inline-trigger" data-nav-edit>
          <strong><?= admin_h($label) ?></strong>
        </button>
      </td>
      <td class="nav-col-href">
        <button type="button" class="nav-inline-trigger nav-inline-trigger--code" data-nav-edit>
          <code><?= admin_h($href) ?></code>
        </button>
      </td>
      <td class="nav-col-status">
        <?php if (!empty($child['category'])): ?>
          <span class="admin-badge admin-badge--muted"><?= admin_h((string) $child['category']) ?></span>
        <?php else: ?>
          <span class="nav-cell-empty" aria-hidden="true">—</span>
        <?php endif; ?>
      </td>
      <td class="nav-col-actions">
        <div class="admin-table-actions admin-table-actions--tight">
          <form method="post" action="nav-delete-child.php" class="admin-inline-form">
            <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
            <input type="hidden" name="parent_index" value="<?= $parentIndex ?>">
            <input type="hidden" name="child_index" value="<?= $childIndex ?>">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm" onclick="return confirm('ลบเมนูย่อย <?= admin_h($label) ?> ?');">ลบ</button>
          </form>
        </div>
      </td>
    </tr>
    <?php
}

function admin_nav_render_child_edit_row(int $parentIndex, string $childIndex, array $child = [], bool $hidden = true): void
{
    $label = (string) ($child['label'] ?? '');
    $href = (string) ($child['href'] ?? '');
    $category = (string) ($child['category'] ?? '');
    ?>
    <tr
      class="nav-row nav-row--edit nav-row--child-edit"
      data-nav-edit-row
      data-nav-type="child"
      data-nav-index="<?= $parentIndex ?>"
      data-nav-child="<?= admin_h($childIndex) ?>"
      <?= $hidden ? 'hidden' : '' ?>
    >
      <td colspan="5">
        <form method="post" action="nav-save.php" class="nav-inline-edit nav-inline-edit--child">
          <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
          <input type="hidden" name="kind" value="child">
          <input type="hidden" name="index" value="<?= $parentIndex ?>">
          <input type="hidden" name="child_index" value="<?= admin_h($childIndex) ?>">
          <div class="nav-inline-edit__fields">
            <label class="nav-inline-edit__field">
              <span class="nav-inline-edit__label">ชื่อเมนูย่อย</span>
              <input type="text" name="label" class="admin-input" value="<?= admin_h($label) ?>" required>
            </label>
            <label class="nav-inline-edit__field nav-inline-edit__field--grow">
              <span class="nav-inline-edit__label">ลิงก์</span>
              <input type="text" name="href" class="admin-input" value="<?= admin_h($href) ?>" required placeholder="plans.html?category=health">
            </label>
            <label class="nav-inline-edit__field">
              <span class="nav-inline-edit__label">หมวด (category)</span>
              <input type="text" name="category" class="admin-input" value="<?= admin_h($category) ?>" placeholder="ว่างได้">
            </label>
          </div>
          <div class="nav-inline-edit__actions">
            <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">บันทึก</button>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-nav-cancel>ยกเลิก</button>
          </div>
        </form>
      </td>
    </tr>
    <?php
}

admin_layout_start('เมนูเว็บ', 'site-nav.php');
?>

<?php admin_card_start('เมนูนำทางเว็บไซต์', 'คลิกชื่อ · ลิงก์ · สถานะเพื่อแก้ไข · ลากไอคอนจุดเพื่อสลับลำดับ'); ?>
<div class="admin-list-toolbar">
  <a href="site-nav-edit.php?index=new" class="admin-btn admin-btn--primary">+ เพิ่มเมนู</a>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหาชื่อเมนู…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table admin-table--nav" data-searchable>
    <colgroup>
      <col class="nav-col-order">
      <col class="nav-col-label">
      <col class="nav-col-href">
      <col class="nav-col-status">
      <col class="nav-col-actions">
    </colgroup>
    <thead>
      <tr>
        <th class="nav-col-order">#</th>
        <th class="nav-col-label">ชื่อเมนู</th>
        <th class="nav-col-href">ลิงก์</th>
        <th class="nav-col-status">สถานะ</th>
        <th class="nav-col-actions"></th>
      </tr>
    </thead>
    <tbody id="site-nav-tbody" data-nav-tbody data-csrf="<?= admin_h($csrf) ?>">
      <?php foreach ($navigation as $i => $item):
        $parentLabel = (string) ($item['label'] ?? '');
        admin_nav_render_main_view_row((int) $i, $item);
        admin_nav_render_main_edit_row((int) $i, $item, true);
        foreach (($item['children'] ?? []) as $ci => $child) {
            admin_nav_render_child_view_row((int) $i, (int) $ci, $child, $parentLabel);
            admin_nav_render_child_edit_row((int) $i, (string) $ci, $child, true);
        }
      endforeach; ?>
    </tbody>
  </table>
</div>
<template id="nav-child-edit-template">
  <?php admin_nav_render_child_edit_row(0, 'new', [], false); ?>
</template>
<p class="admin-hint">แก้ไขในตารางได้เลย — บันทึกจะเผยแพร่ขึ้นเว็บทันที</p>
<?php admin_card_end(); ?>

<div class="admin-form-actions">
  <a href="dashboard.php" class="admin-btn admin-btn--ghost">กลับแดชบอร์ด</a>
</div>

<script src="js/site-nav-inline.js"></script>
<?php admin_layout_end(); ?>
