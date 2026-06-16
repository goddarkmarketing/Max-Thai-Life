<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$types = admin_content_types();
$type = $_GET['type'] ?? 'articles';
if (!isset($types[$type])) {
    header('Location: content-list.php');
    exit;
}
$cfg = $types[$type];
$store = json_read($cfg['file']);
$itemsKey = $cfg['itemsKey'];
$items = $store[$itemsKey] ?? [];

$id = $_GET['id'] ?? '';
$isNew = $id === 'new';
$item = $isNew ? [] : ($items[$id] ?? null);
if (!$isNew && $item === null) {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: content-list.php?type=' . $type);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $slug = admin_post('slug');
    if ($slug === '') {
        $slug = admin_slugify(admin_post('title'));
    }
    $oldSlug = admin_post('old_slug');

    $entry = [
        'slug' => $slug,
        'title' => admin_post('title'),
        'description' => admin_post('description'),
        'category' => admin_post('category'),
        'datePublished' => admin_post('date_published'),
        'dateModified' => date('Y-m-d'),
        'image' => admin_post('image'),
        'sections' => admin_parse_sections_from_post(admin_post_array('sections')),
    ];
    if ($isNew) {
        $entry['visible'] = true;
    } elseif (isset($items[$oldSlug]['visible'])) {
        $entry['visible'] = $items[$oldSlug]['visible'];
    } elseif (isset($items[$slug]['visible'])) {
        $entry['visible'] = $items[$slug]['visible'];
    }

    if ($type === 'articles') {
        $entry['views'] = (int) admin_post('views');
        $entry['shares'] = (int) admin_post('shares');
        $rp = admin_post('related_plan');
        if ($rp !== '') {
            $entry['relatedPlan'] = $rp;
            $entry['relatedPlanLabel'] = admin_post('related_plan_label');
        }
    } elseif ($type === 'news') {
        $entry['views'] = (int) admin_post('views');
        $entry['shares'] = (int) admin_post('shares');
    } elseif ($type === 'claims') {
        $entry['quote'] = admin_post('quote');
        $entry['author'] = admin_post('author');
        $entry['result'] = admin_post('result');
    } elseif ($type === 'careers') {
        $entry['views'] = (int) admin_post('views');
        $entry['shares'] = (int) admin_post('shares');
    }

    if ($oldSlug !== '' && $oldSlug !== $slug && isset($items[$oldSlug])) {
        unset($items[$oldSlug]);
        if (isset($store['list'])) {
            $store['list'] = array_map(fn($s) => $s === $oldSlug ? $slug : $s, $store['list']);
        }
    }

    $items[$slug] = $entry;
    $store[$itemsKey] = $items;

    if ($isNew && isset($store['list']) && !in_array($slug, $store['list'], true)) {
        array_unshift($store['list'], $slug);
    }

    json_write($cfg['file'], $store);
    if ($isNew) {
        admin_create_content_shell($type, $slug);
    }
    admin_flash('success', 'บันทึกแล้ว');
    header('Location: content-edit.php?type=' . $type . '&id=' . urlencode($slug));
    exit;
}

$sections = $item['sections'] ?? [['heading' => '', 'paragraphs' => [''], 'list' => []]];
if ($sections === []) {
    $sections = [['heading' => '', 'paragraphs' => [''], 'list' => []]];
}

admin_layout_start(($isNew ? 'เพิ่ม' : 'แก้ไข') . $cfg['label'], 'content-list.php?type=' . $type);
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="old_slug" value="<?= admin_h($isNew ? '' : $id) ?>">

  <?php admin_card_start('ข้อมูลหลัก'); ?>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('หัวข้อ', 'title', $item['title'] ?? '', ['required' => true]); ?>
    <?php admin_field('Slug (URL)', 'slug', $item['slug'] ?? ($isNew ? '' : $id), ['hint' => 'เช่น tax-saving — ว่างไว้จะสร้างอัตโนมัติ']); ?>
    <?php admin_field('หมวด', 'category', $item['category'] ?? ''); ?>
    <?php admin_field('วันที่เผยแพร่', 'date_published', $item['datePublished'] ?? date('Y-m-d'), ['type' => 'date']); ?>
  </div>
  <?php admin_field('คำอธิบาย (SEO / Hero)', 'description', $item['description'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
  <?php admin_image_field('ภาพปก', 'image', $item['image'] ?? '', $cfg['coverSpec']); ?>
  <?php admin_card_end(); ?>

  <?php if ($type === 'claims'): ?>
  <?php admin_card_start('รีวิวเคลม'); ?>
  <?php admin_field('คำพูด (Quote)', 'quote', $item['quote'] ?? '', ['type' => 'textarea', 'rows' => 4]); ?>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('ผู้รีวิว', 'author', $item['author'] ?? ''); ?>
    <?php admin_field('ผลลัพธ์', 'result', $item['result'] ?? ''); ?>
  </div>
  <?php admin_card_end(); ?>
  <?php else: ?>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('Views', 'views', (string) ($item['views'] ?? 0), ['type' => 'number']); ?>
    <?php admin_field('Shares', 'shares', (string) ($item['shares'] ?? 0), ['type' => 'number']); ?>
  </div>
  <?php if ($type === 'articles'): ?>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('แผนที่เกี่ยวข้อง (URL)', 'related_plan', $item['relatedPlan'] ?? ''); ?>
    <?php admin_field('ชื่อแผนที่เกี่ยวข้อง', 'related_plan_label', $item['relatedPlanLabel'] ?? ''); ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <?php admin_card_start('เนื้อหา'); ?>
  <?php admin_render_sections_editor($sections); ?>
  <?php admin_card_end(); ?>

  <?php admin_actions('content-list.php?type=' . $type, $isNew ? null : [
    'action' => 'content-delete.php',
    'label' => 'ลบรายการนี้',
    'confirm' => 'ลบรายการนี้ถาวร?',
    'fields' => ['type' => $type, 'slug' => $id],
  ]); ?>
</form>

<?php admin_layout_end(); ?>
