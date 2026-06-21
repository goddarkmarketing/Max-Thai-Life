<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');
$footer = admin_normalize_footer($data['footer'] ?? []);
$section = $_GET['section'] ?? 'link';
$col = isset($_GET['col']) ? (int) $_GET['col'] : 0;
$index = $_GET['index'] ?? 'new';
$isNew = $index === 'new';
$indexInt = $isNew ? -1 : (int) $index;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $section = admin_post('section', $section);
    $col = (int) admin_post('col', (string) $col);
    $index = admin_post('index', (string) $index);
    $isNew = $index === 'new';
    $indexInt = $isNew ? -1 : (int) $index;

    try {
        $footer = admin_footer_save_item($footer, $section, $col, $index, $_POST);
        $data['footer'] = $footer;
        json_write('site.json', $data);
        admin_flash('success', 'บันทึก Footer แล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดตหน้าเว็บ');
    } catch (RuntimeException $e) {
        admin_flash('error', $e->getMessage());
    }
    header('Location: site-footer.php');
    exit;
}

$pageTitle = 'แก้ไข Footer';
$back = 'site-footer.php';

if ($section === 'settings') {
    $pageTitle = 'ตั้งค่า Footer ทั่วไป';
} elseif ($section === 'column' && isset($footer['columns'][$col])) {
    $pageTitle = 'แก้ไขคอลัมน์: ' . ($footer['columns'][$col]['title'] ?? '');
} elseif ($section === 'topCta') {
    $pageTitle = $isNew ? 'เพิ่มปุ่ม CTA ด้านบน' : 'แก้ไขปุ่ม CTA ด้านบน';
} elseif ($section === 'bottom') {
    $pageTitle = $isNew ? 'เพิ่มลิงก์ท้ายหน้า' : 'แก้ไขลิงก์ท้ายหน้า';
} elseif ($section === 'link' && isset($footer['columns'][$col])) {
    $pageTitle = $isNew
        ? 'เพิ่มลิงก์ใน ' . ($footer['columns'][$col]['title'] ?? 'คอลัมน์')
        : 'แก้ไขลิงก์ใน ' . ($footer['columns'][$col]['title'] ?? 'คอลัมน์');
} else {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: site-footer.php');
    exit;
}

admin_layout_start($pageTitle, 'site-footer.php');
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="section" value="<?= admin_h($section) ?>">
  <input type="hidden" name="col" value="<?= (int) $col ?>">
  <input type="hidden" name="index" value="<?= admin_h((string) $index) ?>">

  <?php if ($section === 'settings'): ?>
    <?php admin_card_start('ตั้งค่า Footer ทั่วไป'); ?>
    <?php admin_field('Tagline (ใต้โลโก้)', 'tagline', $footer['tagline'] ?? ''); ?>
    <?php admin_field('ข้อความลิขสิทธิ์', 'copyright', $footer['bottom']['copyright'] ?? '', ['type' => 'textarea', 'rows' => 2, 'hint' => 'ใช้ {year} แทนปีปัจจุบัน']); ?>
    <?php admin_card_end(); ?>
  <?php elseif ($section === 'column' && isset($footer['columns'][$col])): ?>
    <?php $column = $footer['columns'][$col]; ?>
    <?php admin_card_start('คอลัมน์ Footer'); ?>
    <?php admin_field('หัวข้อคอลัมน์', 'title', $column['title'] ?? ''); ?>
  <?php if (($column['type'] ?? '') !== 'agent'): ?>
    <label class="admin-check">
      <input type="checkbox" name="wide" value="1"<?= !empty($column['wide']) ? ' checked' : '' ?>>
      คอลัมน์กว้าง (แสดงเต็มแถวบนมือถือ)
    </label>
  <?php endif; ?>
  <?php if (($column['id'] ?? '') === 'plans'): ?>
    <?php $more = $column['moreLink'] ?? []; ?>
    <hr class="admin-divider">
    <h3 class="admin-subtitle">ลิงก์ท้ายคอลัมน์</h3>
    <?php admin_field('ชื่อลิงก์', 'more_label', $more['label'] ?? ''); ?>
    <?php admin_field('URL', 'more_href', $more['href'] ?? ''); ?>
    <label class="admin-check">
      <input type="checkbox" name="more_visible" value="1"<?= admin_footer_link_visible($more) ? ' checked' : '' ?>>
      แสดงบนเว็บ
    </label>
  <?php endif; ?>
    <?php admin_card_end(); ?>
  <?php elseif ($section === 'topCta'): ?>
    <?php $item = $isNew ? [] : ($footer['topCta'][$indexInt] ?? []); ?>
    <?php admin_card_start('ปุ่ม CTA ด้านบน Footer'); ?>
    <?php admin_field('ชื่อปุ่ม', 'label', $item['label'] ?? ''); ?>
    <?php admin_field('URL', 'href', $item['href'] ?? ''); ?>
    <?php admin_field('สไตล์ปุ่ม', 'variant', $item['variant'] ?? 'white', [
        'type' => 'select',
        'options' => ['white' => 'ขาว', 'outline' => 'ขอบขาว'],
    ]); ?>
    <label class="admin-check">
      <input type="checkbox" name="visible" value="1"<?= admin_footer_link_visible($item) ? ' checked' : '' ?>>
      แสดงบนเว็บ
    </label>
    <?php admin_card_end(); ?>
  <?php elseif ($section === 'bottom'): ?>
    <?php $item = $isNew ? [] : ($footer['bottom']['links'][$indexInt] ?? []); ?>
    <?php admin_card_start('ลิงก์ท้าย Footer'); ?>
    <?php admin_field('ชื่อลิงก์', 'label', $item['label'] ?? ''); ?>
    <?php admin_field('URL', 'href', $item['href'] ?? ''); ?>
    <label class="admin-check">
      <input type="checkbox" name="external" value="1"<?= !empty($item['external']) ? ' checked' : '' ?>>
      เปิดในแท็บใหม่ (ลิงก์ภายนอก)
    </label>
    <label class="admin-check">
      <input type="checkbox" name="visible" value="1"<?= admin_footer_link_visible($item) ? ' checked' : '' ?>>
      แสดงบนเว็บ
    </label>
    <?php admin_card_end(); ?>
  <?php elseif ($section === 'link' && isset($footer['columns'][$col])): ?>
    <?php $item = $isNew ? [] : ($footer['columns'][$col]['links'][$indexInt] ?? []); ?>
    <?php admin_card_start('ลิงก์ใน ' . ($footer['columns'][$col]['title'] ?? 'คอลัมน์')); ?>
    <?php admin_field('ชื่อลิงก์', 'label', $item['label'] ?? ''); ?>
    <?php admin_field('URL', 'href', $item['href'] ?? '', ['hint' => 'เช่น plans.html หรือ https://...']); ?>
    <label class="admin-check">
      <input type="checkbox" name="external" value="1"<?= !empty($item['external']) ? ' checked' : '' ?>>
      เปิดในแท็บใหม่ (ลิงก์ภายนอก)
    </label>
    <label class="admin-check">
      <input type="checkbox" name="visible" value="1"<?= admin_footer_link_visible($item) ? ' checked' : '' ?>>
      แสดงบนเว็บ
    </label>
    <?php admin_card_end(); ?>
  <?php endif; ?>

  <?php admin_actions($back); ?>
</form>

<?php admin_layout_end(); ?>
