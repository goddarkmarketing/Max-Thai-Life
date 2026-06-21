<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');
$footer = admin_normalize_footer($data['footer'] ?? []);

if (($data['footer'] ?? []) !== $footer) {
    $data['footer'] = $footer;
    json_write('site.json', $data);
}

function admin_footer_render_view_row(
    string $label,
    string $href,
    bool $visible,
    string $section,
    int $col,
    int $index,
    bool $showPreview = true
): void {
    $slug = admin_footer_href_slug($href);
    ?>
    <tr
      class="footer-row footer-row--view"
      data-footer-view
      data-index="<?= $index ?>"
      data-search-text="<?= admin_h(strtolower($label . ' ' . $href . ' ' . $slug)) ?>"
    >
      <td><strong><?= admin_h($label) ?></strong></td>
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
          <?php if ($showPreview && $href !== ''): ?>
            <a href="<?= admin_h(admin_footer_preview_url($href)) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูลิงก์</a>
          <?php endif; ?>
          <button type="button" class="admin-btn admin-btn--primary admin-btn--sm" data-footer-edit>แก้ไข</button>
          <form method="post" action="footer-toggle.php" class="admin-inline-form">
            <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
            <input type="hidden" name="section" value="<?= admin_h($section) ?>">
            <input type="hidden" name="col" value="<?= $col ?>">
            <input type="hidden" name="index" value="<?= $index ?>">
            <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm"><?= $visible ? 'ซ่อน' : 'แสดง' ?></button>
          </form>
          <form method="post" action="footer-delete.php" class="admin-delete-form" onsubmit="return confirm('ลบ <?= admin_h($label) ?> ?');">
            <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
            <input type="hidden" name="section" value="<?= admin_h($section) ?>">
            <input type="hidden" name="col" value="<?= $col ?>">
            <input type="hidden" name="index" value="<?= $index ?>">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
          </form>
        </div>
      </td>
    </tr>
    <?php
}

function admin_footer_render_edit_row(
    string $section,
    int $col,
    string $index,
    array $item = [],
    bool $hidden = true
): void {
    $label = (string) ($item['label'] ?? '');
    $href = (string) ($item['href'] ?? '');
    $visible = $item === [] ? true : admin_footer_link_visible($item);
    $external = !empty($item['external']);
    $variant = ($item['variant'] ?? 'white') === 'outline' ? 'outline' : 'white';
    ?>
    <tr
      class="footer-row footer-row--edit"
      data-footer-edit-row
      data-index="<?= admin_h($index) ?>"
      <?= $hidden ? 'hidden' : '' ?>
    >
      <td colspan="4">
        <form method="post" action="footer-save.php" class="footer-inline-edit">
          <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
          <input type="hidden" name="section" value="<?= admin_h($section) ?>">
          <input type="hidden" name="col" value="<?= $col ?>">
          <input type="hidden" name="index" value="<?= admin_h($index) ?>">

          <div class="footer-inline-edit__fields">
            <label class="footer-inline-edit__field">
              <span class="footer-inline-edit__label">ชื่อ</span>
              <input type="text" name="label" class="admin-input" value="<?= admin_h($label) ?>" required>
            </label>
            <label class="footer-inline-edit__field footer-inline-edit__field--grow">
              <span class="footer-inline-edit__label">URL</span>
              <input type="text" name="href" class="admin-input" value="<?= admin_h($href) ?>" required placeholder="contact.html หรือ https://...">
            </label>
            <?php if ($section === 'topCta'): ?>
              <label class="footer-inline-edit__field">
                <span class="footer-inline-edit__label">สไตล์</span>
                <select name="variant" class="admin-input">
                  <option value="white"<?= $variant === 'white' ? ' selected' : '' ?>>ขาว</option>
                  <option value="outline"<?= $variant === 'outline' ? ' selected' : '' ?>>ขอบขาว</option>
                </select>
              </label>
            <?php endif; ?>
            <div class="footer-inline-edit__checks">
              <label class="admin-check">
                <input type="checkbox" name="visible" value="1"<?= $visible ? ' checked' : '' ?>>
                แสดงบนเว็บ
              </label>
              <?php if ($section === 'link' || $section === 'bottom'): ?>
                <label class="admin-check">
                  <input type="checkbox" name="external" value="1"<?= $external ? ' checked' : '' ?>>
                  เปิดแท็บใหม่
                </label>
              <?php endif; ?>
            </div>
          </div>
          <div class="footer-inline-edit__actions">
            <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">บันทึก</button>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-footer-cancel>ยกเลิก</button>
          </div>
        </form>
      </td>
    </tr>
    <?php
}

function admin_footer_render_item_rows(
    string $section,
    int $col,
    int $index,
    array $item,
    bool $showPreview = true
): void {
    admin_footer_render_view_row(
        (string) ($item['label'] ?? ''),
        (string) ($item['href'] ?? ''),
        admin_footer_link_visible($item),
        $section,
        $col,
        $index,
        $showPreview
    );
    admin_footer_render_edit_row($section, $col, (string) $index, $item, true);
}

admin_layout_start('Footer', 'site-footer.php');
?>

<?php admin_card_start('ตั้งค่าทั่วไป', 'Tagline และข้อความลิขสิทธิ์'); ?>
<div class="admin-list-toolbar">
  <a href="footer-edit.php?section=settings" class="admin-btn admin-btn--primary">แก้ไขตั้งค่าทั่วไป</a>
</div>
<p><strong>Tagline:</strong> <?= admin_h($footer['tagline'] ?? '') ?></p>
<p><strong>ลิขสิทธิ์:</strong> <?= admin_h($footer['bottom']['copyright'] ?? '') ?></p>
<p class="admin-hint">ข้อมูลตัวแทน (ชื่อ โทร ใบอนุญาต) แก้ที่เมนู <a href="site.php">ข้อมูลเว็บไซต์</a></p>
<?php admin_card_end(); ?>

<?php admin_card_start('ปุ่ม CTA ด้านบน Footer'); ?>
<div class="admin-list-toolbar">
  <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="topCta" data-footer-col="0">+ เพิ่มปุ่ม</button>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหา…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table" data-searchable>
    <thead>
      <tr>
        <th>ชื่อ</th>
        <th>URL</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
    <tbody data-footer-tbody data-footer-section="topCta" data-footer-col="0">
      <?php foreach (($footer['topCta'] ?? []) as $i => $item): ?>
        <?php admin_footer_render_item_rows('topCta', 0, (int) $i, $item); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<template id="footer-edit-template-topCta">
  <?php admin_footer_render_edit_row('topCta', 0, 'new', [], false); ?>
</template>
<?php admin_card_end(); ?>

<?php foreach (($footer['columns'] ?? []) as $colIndex => $column): ?>
  <?php if (($column['type'] ?? '') === 'agent'): ?>
    <?php admin_card_start($column['title'] ?? 'ติดต่อตัวแทน', 'ดึงข้อมูลจากเมนูข้อมูลเว็บไซต์อัตโนมัติ'); ?>
    <div class="admin-list-toolbar">
      <a href="footer-edit.php?section=column&amp;col=<?= (int) $colIndex ?>" class="admin-btn admin-btn--secondary admin-btn--sm">แก้ไขหัวข้อ</a>
      <a href="site.php" class="admin-btn admin-btn--ghost admin-btn--sm">แก้ไขข้อมูลตัวแทน</a>
    </div>
    <p class="admin-hint">แสดงชื่อ ตำแหน่ง สาขา โทร และใบอนุญาตจากข้อมูลตัวแทน</p>
    <?php admin_card_end(); ?>
    <?php continue; ?>
  <?php endif; ?>

  <?php admin_card_start($column['title'] ?? 'คอลัมน์ Footer'); ?>
  <div class="admin-list-toolbar">
    <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="link" data-footer-col="<?= (int) $colIndex ?>">+ เพิ่มลิงก์</button>
    <a href="footer-edit.php?section=column&amp;col=<?= (int) $colIndex ?>" class="admin-btn admin-btn--secondary admin-btn--sm">แก้ไขคอลัมน์</a>
    <input type="search" class="admin-input admin-table-search" placeholder="ค้นหาชื่อลิงก์…" data-table-search>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table" data-searchable>
      <thead>
        <tr>
          <th>ชื่อ</th>
          <th>URL / Slug</th>
          <th>สถานะ</th>
          <th></th>
        </tr>
      </thead>
      <tbody data-footer-tbody data-footer-section="link" data-footer-col="<?= (int) $colIndex ?>">
        <?php foreach (($column['links'] ?? []) as $i => $item): ?>
          <?php admin_footer_render_item_rows('link', (int) $colIndex, (int) $i, $item); ?>
        <?php endforeach; ?>
        <?php if (!empty($column['moreLink']['label'])): ?>
          <?php $more = $column['moreLink']; ?>
          <tr data-search-text="<?= admin_h(strtolower(($more['label'] ?? '') . ' ' . ($more['href'] ?? '') . ' more')) ?>">
            <td><strong><?= admin_h($more['label'] ?? '') ?></strong> <span class="admin-badge admin-badge--muted">ลิงก์ท้ายคอลัมน์</span></td>
            <td><code><?= admin_h(admin_footer_href_slug($more['href'] ?? '')) ?></code></td>
            <td>
              <?php if (admin_footer_link_visible($more)): ?>
                <span class="admin-badge admin-badge--ok">เผยแพร่</span>
              <?php else: ?>
                <span class="admin-badge admin-badge--muted">ซ่อน</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="footer-edit.php?section=column&amp;col=<?= (int) $colIndex ?>" class="admin-btn admin-btn--primary admin-btn--sm">แก้ไข</a>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <template id="footer-edit-template-link-<?= (int) $colIndex ?>">
    <?php admin_footer_render_edit_row('link', (int) $colIndex, 'new', [], false); ?>
  </template>
  <?php admin_card_end(); ?>
<?php endforeach; ?>

<?php admin_card_start('ลิงก์ท้าย Footer (Legal)'); ?>
<div class="admin-list-toolbar">
  <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="bottom" data-footer-col="0">+ เพิ่มลิงก์</button>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหา…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table" data-searchable>
    <thead>
      <tr>
        <th>ชื่อ</th>
        <th>URL</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
    <tbody data-footer-tbody data-footer-section="bottom" data-footer-col="0">
      <?php foreach (($footer['bottom']['links'] ?? []) as $i => $item): ?>
        <?php admin_footer_render_item_rows('bottom', 0, (int) $i, $item); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<template id="footer-edit-template-bottom">
  <?php admin_footer_render_edit_row('bottom', 0, 'new', [], false); ?>
</template>
<?php admin_card_end(); ?>

<div class="admin-form-actions">
  <a href="publish.php" class="admin-btn admin-btn--primary">เผยแพร่ขึ้นเว็บ</a>
  <a href="dashboard.php" class="admin-btn admin-btn--ghost">กลับแดชบอร์ด</a>
</div>

<script src="js/footer-inline.js"></script>
<?php admin_layout_end(); ?>
