<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$home = json_read('home.json');
$rawSection = $home['testimonialsSection'] ?? [];
$hadItems = isset($rawSection['items']) && is_array($rawSection['items']) && $rawSection['items'] !== [];
$section = admin_testimonials_normalize($rawSection);

// ครั้งแรกที่ยังไม่มี items (ของเดิมเก็บเป็น slides) ให้ migrate แล้วบันทึกทันที
if (!$hadItems) {
    admin_testimonials_persist($section);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $action = admin_post('action');
    $items = $section['items'];

    $findIndex = static function (array $items, string $id): ?int {
        foreach ($items as $i => $it) {
            if (($it['id'] ?? '') === $id) {
                return $i;
            }
        }
        return null;
    };

    if ($action === 'meta') {
        $section['title'] = admin_post('title');
        $section['subtitle'] = admin_post('subtitle');
        admin_testimonials_persist($section);
        admin_flash('success', 'บันทึกหัวข้อรีวิวแล้ว');
    } elseif ($action === 'delete') {
        $id = admin_post('id');
        $idx = $findIndex($items, $id);
        if ($idx !== null) {
            array_splice($items, $idx, 1);
            $section['items'] = $items;
            admin_testimonials_persist($section);
            admin_flash('success', 'ลบรีวิวแล้ว');
        }
    } elseif ($action === 'toggle-visible') {
        $id = admin_post('id');
        $idx = $findIndex($items, $id);
        if ($idx !== null) {
            $items[$idx]['visible'] = (($items[$idx]['visible'] ?? true) === false);
            $section['items'] = $items;
            admin_testimonials_persist($section);
        }
    } elseif ($action === 'toggle-pin') {
        $id = admin_post('id');
        $idx = $findIndex($items, $id);
        if ($idx !== null) {
            if (admin_is_pinned($items[$idx])) {
                $items[$idx]['pinned'] = false;
                unset($items[$idx]['pinnedAt']);
            } else {
                $items[$idx]['pinned'] = true;
                $items[$idx]['pinnedAt'] = date('c');
            }
            $section['items'] = $items;
            admin_testimonials_persist($section);
        }
    }

    header('Location: testimonials-list.php');
    exit;
}

$items = admin_sort_pinned_list($section['items']);

admin_layout_start('รีวิวลูกค้า', 'testimonials-list.php');
?>

<?php admin_card_start('หัวข้อส่วนรีวิว', 'แสดงบนหน้าแรก เหนือการ์ดรีวิว'); ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="action" value="meta">
  <div class="admin-grid admin-grid--2">
    <?php admin_field('หัวข้อ', 'title', $section['title'] ?? 'เสียงจากลูกค้า'); ?>
    <?php admin_field('คำบรรยาย', 'subtitle', $section['subtitle'] ?? ''); ?>
  </div>
  <div class="admin-form-actions">
    <button type="submit" class="admin-btn admin-btn--primary">บันทึกหัวข้อ</button>
  </div>
</form>
<?php admin_card_end(); ?>

<?php admin_card_start('รายการรีวิว'); ?>
<div class="admin-list-toolbar">
  <a href="testimonial-edit.php?id=new" class="admin-btn admin-btn--primary">+ เพิ่มรีวิว</a>
  <input type="search" class="admin-input admin-table-search" placeholder="ค้นหาคำรีวิว / ผู้รีวิว…" data-table-search>
</div>
<div class="admin-table-wrap">
  <table class="admin-table" data-searchable>
    <thead>
      <tr>
        <th>คำรีวิว</th>
        <th>ผู้รีวิว</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if ($items === []): ?>
        <tr><td colspan="4"><em>ยังไม่มีรีวิว — กด “เพิ่มรีวิว” เพื่อเริ่มต้น</em></td></tr>
      <?php endif; ?>
      <?php foreach ($items as $it): ?>
        <?php
          $id = (string) ($it['id'] ?? '');
          $quote = (string) ($it['quote'] ?? '');
          $author = (string) ($it['author'] ?? '');
          $visible = ($it['visible'] ?? true) !== false;
          $pinned = admin_is_pinned($it);
        ?>
        <tr data-search-text="<?= admin_h(strtolower($quote . ' ' . $author)) ?>">
          <td>
            <div class="admin-cell-quote" title="<?= admin_h($quote) ?>"><?php if ($pinned): ?><span class="admin-pin-flag" aria-label="ปักหมุด">📌</span> <?php endif; ?><?= admin_h($quote) ?></div>
          </td>
          <td><?= admin_h($author) ?></td>
          <td>
            <?php if ($visible): ?>
              <span class="admin-badge admin-badge--ok">เผยแพร่</span>
            <?php else: ?>
              <span class="admin-badge admin-badge--muted">ซ่อน</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="admin-table-actions">
              <a href="../index.html#reviews" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูหน้า</a>
              <a href="testimonial-edit.php?id=<?= admin_h($id) ?>" class="admin-btn admin-btn--primary admin-btn--sm">แก้ไข</a>
              <form method="post" class="admin-inline-form">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="toggle-pin">
                <input type="hidden" name="id" value="<?= admin_h($id) ?>">
                <button type="submit" class="admin-btn admin-btn--sm <?= $pinned ? 'admin-btn--secondary' : 'admin-btn--ghost' ?>"><?= $pinned ? 'เลิกปัก' : 'ปักหมุด' ?></button>
              </form>
              <form method="post" class="admin-inline-form">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="toggle-visible">
                <input type="hidden" name="id" value="<?= admin_h($id) ?>">
                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm"><?= $visible ? 'ซ่อน' : 'แสดง' ?></button>
              </form>
              <form method="post" class="admin-delete-form" onsubmit="return confirm('ลบรีวิวนี้?');">
                <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= admin_h($id) ?>">
                <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="admin-hint">รีวิวที่ปักหมุดจะถูกแสดงก่อน ระบบจัดกลุ่มเป็นสไลด์ละ 3 รีวิวบนหน้าแรกให้อัตโนมัติ</p>
<?php admin_card_end(); ?>

<?php admin_layout_end(); ?>
