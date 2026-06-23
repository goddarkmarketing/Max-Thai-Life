<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/inline-list-ui.php';

admin_require_login();

$data = json_read('site.json');
$social = admin_normalize_social($data['social'] ?? []);
$agent = admin_normalize_agent_contacts($data['agent'] ?? []);

if (($data['social'] ?? []) !== $social) {
    $data['social'] = $social;
    json_write('site.json', $data);
}

$csrf = admin_csrf_token();

$socialApi = [
    'save' => 'social-save.php',
    'toggle' => 'social-toggle.php',
    'delete' => 'social-delete.php',
    'return' => 'site-social.php',
];

admin_layout_start('โซเชียลและช่องทางติดต่อ', 'site-social.php');
?>

<?php admin_card_start('ไอคอนโซเชียลใน Footer', 'ปุ่มกลมใต้คอลัมน์ติดต่อตัวแทน — เพิ่มได้ไม่จำกัด'); ?>
<div class="admin-list-toolbar">
  <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="socialLink" data-footer-col="0">+ เพิ่มไอคอน</button>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหา…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table admin-table--nav" data-searchable>
    <?php admin_inline_list_table_head('URL / ลิงก์'); ?>
    <tbody
      data-footer-tbody
      data-footer-section="socialLink"
      data-footer-col="0"
      data-csrf="<?= admin_h($csrf) ?>"
      data-reorder-url="social-reorder.php"
    >
      <?php foreach (($social['links'] ?? []) as $i => $item): ?>
        <?php admin_inline_list_render_item_rows('socialLink', 0, (int) $i, $item, $socialApi); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<template id="footer-edit-template-socialLink">
  <?php admin_inline_list_render_edit_row('socialLink', 0, 'new', [], $socialApi, false); ?>
</template>
<p class="admin-hint">เลือกไอคอนจากตาราง 10 แบบ · ปรับสีปุ่มด้วย Color Picker (HEX) — บันทึกแล้วเผยแพร่ขึ้นเว็บทันที</p>
<?php admin_card_end(); ?>

<?php admin_card_start('ช่องทางติดต่อเพิ่มเติม', 'แสดงเป็นรายการในคอลัมน์ติดต่อตัวแทน (ใต้โทร/ใบอนุญาต)'); ?>
<div class="admin-list-toolbar">
  <button type="button" class="admin-btn admin-btn--primary" data-footer-add data-footer-section="agentContact" data-footer-col="0">+ เพิ่มช่องทาง</button>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหา…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table admin-table--nav" data-searchable>
    <?php admin_inline_list_table_head('ข้อความ / ลิงก์'); ?>
    <tbody
      data-footer-tbody
      data-footer-section="agentContact"
      data-footer-col="0"
      data-csrf="<?= admin_h($csrf) ?>"
      data-reorder-url="social-reorder.php"
    >
      <?php foreach (($agent['extraContacts'] ?? []) as $i => $item): ?>
        <?php admin_inline_list_render_item_rows('agentContact', 0, (int) $i, $item, $socialApi, !empty($item['href'])); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<template id="footer-edit-template-agentContact">
  <?php admin_inline_list_render_edit_row('agentContact', 0, 'new', [], $socialApi, false); ?>
</template>
<p class="admin-hint">ข้อมูลตัวแทนหลัก (ชื่อ โทร ใบอนุญาต) แก้ที่ <a href="site.php">ตั้งค่าเว็บไซต์</a></p>
<?php admin_card_end(); ?>

<div class="admin-form-actions">
  <a href="dashboard.php" class="admin-btn admin-btn--ghost">กลับแดชบอร์ด</a>
</div>

<script src="js/footer-inline.js"></script>
<script src="js/contact-pickers.js"></script>
<?php admin_layout_end(); ?>
