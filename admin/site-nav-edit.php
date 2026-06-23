<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');
$navigation = $data['navigation'] ?? admin_default_navigation();
$index = $_GET['index'] ?? 'new';
$isNew = $index === 'new';
$indexInt = $isNew ? -1 : (int) $index;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $index = admin_post('index', (string) $index);
    $isNew = $index === 'new';

    try {
        $navigation = admin_nav_save_item($navigation, $index, $_POST);
        $data['navigation'] = $navigation;
        admin_nav_publish_site($data);
        admin_flash('success', 'บันทึกและเผยแพร่เมนูขึ้นเว็บแล้ว');
        header('Location: site-nav.php');
        exit;
    } catch (RuntimeException $e) {
        admin_flash('error', $e->getMessage());
        header('Location: site-nav-edit.php?index=' . urlencode($index));
        exit;
    }
}

if (!$isNew && !isset($navigation[$indexInt])) {
    admin_flash('error', 'ไม่พบเมนู');
    header('Location: site-nav.php');
    exit;
}

$item = $isNew ? [] : $navigation[$indexInt];
$pageTitle = $isNew ? 'เพิ่มเมนูเว็บ' : 'แก้ไขเมนู: ' . ($item['label'] ?? '');
$itemHref = (string) ($item['href'] ?? '');
$isPlans = admin_nav_is_plans_href($itemHref);
$childItems = $item['children'] ?? [];
if ($childItems === [] && $isPlans) {
    $childItems = admin_default_plan_nav_children();
}
$hasChildren = $childItems !== [] || $isPlans;

admin_layout_start($pageTitle, 'site-nav.php');
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="index" value="<?= admin_h((string) $index) ?>">

  <?php admin_card_start('รายละเอียดเมนู'); ?>
  <?php admin_field('ชื่อเมนู', 'label', $item['label'] ?? ''); ?>
  <?php admin_field('ลิงก์', 'href', $item['href'] ?? '', ['hint' => 'เช่น about.html, plans.html, contact.html']); ?>
  <div class="admin-grid admin-grid--2">
    <label class="admin-check">
      <input type="checkbox" name="visible" value="1"<?= admin_nav_item_visible($item) ? ' checked' : '' ?>>
      แสดงในเมนู
    </label>
    <label class="admin-check">
      <input type="checkbox" name="cta" value="1"<?= !empty($item['cta']) ? ' checked' : '' ?>>
      เป็นปุ่ม CTA (เน้นสี)
    </label>
  </div>
  <?php admin_card_end(); ?>

  <?php admin_card_start('เมนูย่อย (dropdown)', 'เพิ่มรายการย่อยใต้เมนูนี้ — ว่างไว้ถ้าเป็นลิงก์เดี่ยว'); ?>
  <label class="admin-check admin-check--block">
    <input type="checkbox" name="has_children" value="1" id="nav-has-children"<?= $hasChildren ? ' checked' : '' ?>>
    มีเมนูย่อย (แสดงเป็น dropdown)
  </label>
  <div id="nav-children-panel"<?= $hasChildren ? '' : ' hidden' ?>>
    <?php admin_render_nav_children_repeater($childItems, [
        'title' => 'รายการเมนูย่อย',
        'min' => 0,
        'emptyRow' => true,
    ]); ?>
  </div>
  <?php admin_card_end(); ?>

  <?php admin_actions('site-nav.php', $isNew ? null : [
      'action' => 'nav-delete.php',
      'label' => 'ลบเมนูนี้',
      'confirm' => 'ลบเมนูนี้ถาวร?',
      'fields' => ['index' => (string) $indexInt],
  ]); ?>
</form>

<script>
(function () {
  var toggle = document.getElementById('nav-has-children');
  var panel = document.getElementById('nav-children-panel');
  if (!toggle || !panel) return;
  toggle.addEventListener('change', function () {
    panel.hidden = !toggle.checked;
  });
})();
</script>
<?php admin_layout_end(); ?>
