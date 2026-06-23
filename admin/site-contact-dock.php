<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/inline-list-ui.php';

admin_require_login();

$data = json_read('site.json');
$contactDock = admin_normalize_contact_dock($data['contactDock'] ?? []);
$csrf = admin_csrf_token();

$dockApi = [
    'save' => 'contact-dock-save.php',
    'toggle' => 'contact-dock-toggle.php',
    'delete' => 'contact-dock-delete.php',
    'return' => 'site-contact-dock.php',
];

admin_layout_start('ปุ่มลอย', 'site-contact-dock.php');
?>

<?php admin_card_start('ปุ่มลอยมุมขวาล่าง', 'ปุ่มกลมที่ลอยอยู่มุมขวาล่างทุกหน้า — เพิ่มได้ไม่จำกัด'); ?>
<form method="post" action="contact-dock-save.php" class="admin-inline-form" style="margin-bottom:1rem">
  <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
  <input type="hidden" name="section" value="contactDockSettings">
  <input type="hidden" name="return" value="site-contact-dock.php">
  <label class="admin-check">
    <input type="checkbox" name="enabled" value="1"<?= !empty($contactDock['enabled']) ? ' checked' : '' ?>>
    แสดงปุ่มลอยบนเว็บ
  </label>
  <button type="submit" class="admin-btn admin-btn--secondary admin-btn--sm">บันทึกการแสดงผล</button>
</form>
<div class="admin-list-toolbar">
  <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="contactDock" data-footer-col="0">+ เพิ่มปุ่มลอย</button>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหา…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table admin-table--nav" data-searchable>
    <?php admin_inline_list_table_head(); ?>
    <tbody
      data-footer-tbody
      data-footer-section="contactDock"
      data-footer-col="0"
      data-csrf="<?= admin_h($csrf) ?>"
      data-reorder-url="contact-dock-reorder.php"
    >
      <?php foreach (($contactDock['items'] ?? []) as $i => $item): ?>
        <?php admin_inline_list_render_item_rows('contactDock', 0, (int) $i, $item, $dockApi); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<template id="footer-edit-template-contactDock">
  <?php admin_inline_list_render_edit_row('contactDock', 0, 'new', [], $dockApi, false); ?>
</template>
<p class="admin-hint">ไอคอนใช้ชื่อจาก Lucide เช่น phone, message-circle, mail, file-text — บันทึกแล้วเผยแพร่ขึ้นเว็บทันที</p>
<?php admin_card_end(); ?>

<div class="admin-form-actions">
  <a href="dashboard.php" class="admin-btn admin-btn--ghost">กลับแดชบอร์ด</a>
</div>

<script src="js/footer-inline.js"></script>
<script src="js/contact-pickers.js"></script>
<?php admin_layout_end(); ?>
