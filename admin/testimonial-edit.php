<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$home = json_read('home.json');
$section = admin_testimonials_normalize($home['testimonialsSection'] ?? []);
$items = $section['items'];

$id = trim($_GET['id'] ?? '');
$isNew = $id === 'new' || $id === '';

$current = null;
$currentIndex = null;
if (!$isNew) {
    foreach ($items as $i => $it) {
        if (($it['id'] ?? '') === $id) {
            $current = $it;
            $currentIndex = $i;
            break;
        }
    }
    if ($current === null) {
        admin_flash('error', 'ไม่พบรีวิว');
        header('Location: testimonials-list.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $quote = trim(admin_post('quote'));
    $author = trim(admin_post('author'));
    $visible = admin_post('visible') === '1';

    if ($quote === '') {
        admin_flash('error', 'กรุณากรอกคำรีวิว');
        header('Location: testimonial-edit.php?id=' . urlencode($isNew ? 'new' : $id));
        exit;
    }

    if ($isNew) {
        $items[] = [
            'id' => admin_testimonial_uid(),
            'quote' => $quote,
            'author' => $author,
            'visible' => $visible,
        ];
    } else {
        $items[$currentIndex]['quote'] = $quote;
        $items[$currentIndex]['author'] = $author;
        $items[$currentIndex]['visible'] = $visible;
    }

    $section['items'] = $items;
    admin_testimonials_persist($section);
    admin_flash('success', 'บันทึกและเผยแพร่รีวิวแล้ว');
    header('Location: testimonials-list.php');
    exit;
}

$quote = (string) ($current['quote'] ?? '');
$author = (string) ($current['author'] ?? '');
$visible = ($current['visible'] ?? true) !== false;

admin_layout_start(($isNew ? 'เพิ่มรีวิว' : 'แก้ไขรีวิว'), 'testimonials-list.php');
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">

  <?php admin_card_start($isNew ? 'เพิ่มรีวิวลูกค้า' : 'แก้ไขรีวิวลูกค้า'); ?>
  <?php admin_field('คำรีวิว', 'quote', $quote, ['type' => 'textarea', 'rows' => 5, 'required' => true]); ?>
  <?php admin_field('ชื่อผู้รีวิว', 'author', $author, ['hint' => 'เช่น คุณสมชาย · วัยทำงาน นครปฐม']); ?>
  <label class="admin-checkbox">
    <input type="checkbox" name="visible" value="1" <?= $visible ? 'checked' : '' ?>>
    <span>แสดงรีวิวนี้บนหน้าแรก</span>
  </label>
  <?php admin_card_end(); ?>

  <div class="admin-form-actions">
    <button type="submit" class="admin-btn admin-btn--primary">บันทึกและเผยแพร่</button>
    <a href="testimonials-list.php" class="admin-btn admin-btn--ghost">กลับ</a>
  </div>
</form>

<?php admin_layout_end(); ?>
