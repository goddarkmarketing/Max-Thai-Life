<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/inline-list-ui.php';

admin_require_login();

$data = json_read('site.json');
$footer = admin_normalize_footer($data['footer'] ?? []);

if (($data['footer'] ?? []) !== $footer) {
    $data['footer'] = $footer;
    json_write('site.json', $data);
}

$csrf = admin_csrf_token();
$footerApi = [
    'save' => 'footer-save.php',
    'toggle' => 'footer-toggle.php',
    'delete' => 'footer-delete.php',
    'return' => 'site-footer.php',
];

admin_layout_start('Footer', 'site-footer.php');
?>

<?php admin_card_start('ตั้งค่าทั่วไป', 'Tagline และข้อความลิขสิทธิ์'); ?>
<div class="admin-list-toolbar">
  <a href="footer-edit.php?section=settings" class="admin-btn admin-btn--primary">แก้ไขตั้งค่าทั่วไป</a>
</div>
<p><strong>Tagline:</strong> <?= admin_h($footer['tagline'] ?? '') ?></p>
<p><strong>ลิขสิทธิ์:</strong> <?= admin_h($footer['bottom']['copyright'] ?? '') ?></p>
<p class="admin-hint">ข้อมูลตัวแทน (ชื่อ โทร ใบอนุญาต) แก้ที่เมนู <a href="site.php">ตั้งค่าเว็บไซต์</a> · ไอคอนโซเชียลและช่องทางเพิ่มเติมที่ <a href="site-social.php">โซเชียล</a> · ปุ่มลอยที่ <a href="site-contact-dock.php">ปุ่มลอย</a></p>
<?php admin_card_end(); ?>

<?php admin_card_start('ปุ่ม CTA ด้านบน Footer', 'คลิกชื่อ · URL · สถานะเพื่อแก้ไข · ลากไอคอนจุดเพื่อสลับลำดับ'); ?>
<div class="admin-list-toolbar">
  <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="topCta" data-footer-col="0">+ เพิ่มปุ่ม</button>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหา…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table admin-table--nav" data-searchable>
    <?php admin_inline_list_table_head(); ?>
    <tbody data-footer-tbody data-footer-section="topCta" data-footer-col="0" data-csrf="<?= admin_h($csrf) ?>" data-reorder-url="footer-reorder.php">
      <?php foreach (($footer['topCta'] ?? []) as $i => $item): ?>
        <?php admin_inline_list_render_item_rows('topCta', 0, (int) $i, $item, $footerApi); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<template id="footer-edit-template-topCta">
  <?php admin_inline_list_render_edit_row('topCta', 0, 'new', [], $footerApi, false); ?>
</template>
<?php admin_card_end(); ?>

<?php foreach (($footer['columns'] ?? []) as $colIndex => $column): ?>
  <?php if (($column['type'] ?? '') === 'agent'): ?>
    <?php admin_card_start($column['title'] ?? 'ติดต่อตัวแทน', 'ดึงข้อมูลจากเมนูข้อมูลเว็บไซต์อัตโนมัติ'); ?>
    <div class="admin-list-toolbar">
      <a href="footer-edit.php?section=column&amp;col=<?= (int) $colIndex ?>" class="admin-btn admin-btn--secondary admin-btn--sm">แก้ไขหัวข้อ</a>
      <a href="site.php" class="admin-btn admin-btn--ghost admin-btn--sm">แก้ไขข้อมูลตัวแทน</a>
      <a href="site-social.php" class="admin-btn admin-btn--ghost admin-btn--sm">โซเชียล / ช่องทางเพิ่ม</a>
    </div>
    <p class="admin-hint">แสดงชื่อ ตำแหน่ง สาขา โทร และใบอนุญาตจากข้อมูลตัวแทน</p>
    <?php admin_card_end(); ?>
    <?php continue; ?>
  <?php endif; ?>

  <?php admin_card_start($column['title'] ?? 'คอลัมน์ Footer', 'คลิกชื่อ · URL · สถานะเพื่อแก้ไข · ลากไอคอนจุดเพื่อสลับลำดับ'); ?>
  <div class="admin-list-toolbar">
    <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="link" data-footer-col="<?= (int) $colIndex ?>">+ เพิ่มลิงก์</button>
    <a href="footer-edit.php?section=column&amp;col=<?= (int) $colIndex ?>" class="admin-btn admin-btn--secondary admin-btn--sm">แก้ไขหัวข้อคอลัมน์</a>
    <input type="search" class="admin-input admin-table-search" placeholder="ค้นหาชื่อลิงก์…" data-table-search>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table admin-table--nav" data-searchable>
      <?php admin_inline_list_table_head('URL / Slug'); ?>
      <tbody data-footer-tbody data-footer-section="link" data-footer-col="<?= (int) $colIndex ?>" data-csrf="<?= admin_h($csrf) ?>" data-reorder-url="footer-reorder.php">
        <?php foreach (($column['links'] ?? []) as $i => $item): ?>
          <?php admin_inline_list_render_item_rows('link', (int) $colIndex, (int) $i, $item, $footerApi, true, true); ?>
        <?php endforeach; ?>
        <?php if (!empty($column['moreLink']['label'])): ?>
          <?php admin_inline_list_render_more_link_rows((int) $colIndex, $column['moreLink'], $footerApi); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <template id="footer-edit-template-link-<?= (int) $colIndex ?>">
    <?php admin_inline_list_render_edit_row('link', (int) $colIndex, 'new', [], $footerApi, false); ?>
  </template>
  <?php admin_card_end(); ?>
<?php endforeach; ?>

<?php admin_card_start('ลิงก์ท้าย Footer (Legal)', 'คลิกชื่อ · URL · สถานะเพื่อแก้ไข · ลากไอคอนจุดเพื่อสลับลำดับ'); ?>
<div class="admin-list-toolbar">
  <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="bottom" data-footer-col="0">+ เพิ่มลิงก์</button>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหา…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table admin-table--nav" data-searchable>
    <?php admin_inline_list_table_head(); ?>
    <tbody data-footer-tbody data-footer-section="bottom" data-footer-col="0" data-csrf="<?= admin_h($csrf) ?>" data-reorder-url="footer-reorder.php">
      <?php foreach (($footer['bottom']['links'] ?? []) as $i => $item): ?>
        <?php admin_inline_list_render_item_rows('bottom', 0, (int) $i, $item, $footerApi); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<template id="footer-edit-template-bottom">
  <?php admin_inline_list_render_edit_row('bottom', 0, 'new', [], $footerApi, false); ?>
</template>
<p class="admin-hint">แก้ไขในตารางได้เลย — บันทึกจะเผยแพร่ขึ้นเว็บทันที</p>
<?php admin_card_end(); ?>

<div class="admin-form-actions">
  <a href="dashboard.php" class="admin-btn admin-btn--ghost">กลับแดชบอร์ด</a>
</div>

<script src="js/footer-inline.js"></script>
<?php admin_layout_end(); ?>
