<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/content-blocks.php';

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
    $prev = (!$isNew && $oldSlug !== '') ? ($items[$oldSlug] ?? $items[$slug] ?? []) : [];

    $entry = [
        'slug' => $slug,
        'title' => admin_post('title'),
        'description' => admin_post('description'),
        'category' => admin_post('category'),
        'datePublished' => admin_post('date_published'),
        'dateModified' => date('Y-m-d'),
        'image' => admin_post('image'),
        'sections' => $prev['sections'] ?? [],
    ];
    if ($isNew) {
        $entry['visible'] = true;
    } elseif (isset($prev['visible'])) {
        $entry['visible'] = $prev['visible'];
    }
    $entry = admin_preserve_pin($entry, $prev);

    if ($type === 'articles') {
        $entry['views'] = (int) admin_post('views');
        $entry['shares'] = (int) admin_post('shares');
        $rp = admin_post('related_plan');
        if ($rp !== '') {
            $entry['relatedPlan'] = $rp;
            $entry['relatedPlanLabel'] = admin_post('related_plan_label');
        } else {
            unset($entry['relatedPlan'], $entry['relatedPlanLabel']);
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
    } elseif ($oldSlug !== '' && $oldSlug !== $slug) {
        $cfgDir = admin_content_type_config($type);
        if ($cfgDir && $cfgDir['dir']) {
            $oldPath = ROOT_PATH . '/' . $cfgDir['dir'] . '/' . $oldSlug . '.html';
            $newPath = ROOT_PATH . '/' . $cfgDir['dir'] . '/' . $slug . '.html';
            if (file_exists($oldPath) && !file_exists($newPath)) {
                rename($oldPath, $newPath);
            }
        }
    }

    require_once __DIR__ . '/includes/generate-js.php';
    generate_all_js();
    admin_flash('success', 'บันทึกและเผยแพร่การ์ดขึ้นเว็บแล้ว');
    header('Location: content-edit.php?type=' . $type . '&id=' . urlencode($slug));
    exit;
}

$listMap = [
    'articles' => 'content-list.php?type=articles',
    'news' => 'content-list.php?type=news',
    'careers' => 'content-list.php?type=careers',
    'claims' => 'content-list.php?type=claims',
];
$baseList = $listMap[$type] ?? 'content-list.php?type=articles';
$hasVisual = in_array($type, ['articles', 'news', 'careers', 'claims'], true);

admin_layout_start(($isNew ? 'เพิ่ม' : 'แก้ไขการ์ด') . $cfg['label'], $baseList, [
    'stylesheets' => ['../css/styles.css', 'css/content-card-edit.css'],
]);
?>

<?php
$hasRichtext = in_array($type, ['articles', 'news', 'careers'], true);
$itemIsRichtext = !$isNew && ($item['editor'] ?? '') === 'richtext';
?>
<?php if (!$isNew && $hasVisual): ?>
<div class="admin-tabs">
  <?php if ($itemIsRichtext): ?>
    <a href="content-richtext.php?type=<?= admin_h($type) ?>&id=<?= admin_h($id) ?>" class="admin-tab">แก้ไขเนื้อหา (Rich Text)</a>
  <?php else: ?>
    <a href="content-visual.php?type=<?= admin_h($type) ?>&id=<?= admin_h($id) ?>" class="admin-tab">แก้ไขหน้า</a>
    <?php if ($hasRichtext): ?>
      <a href="content-richtext.php?type=<?= admin_h($type) ?>&id=<?= admin_h($id) ?>" class="admin-tab">Rich Text</a>
    <?php endif; ?>
  <?php endif; ?>
  <a href="content-edit.php?type=<?= admin_h($type) ?>&id=<?= admin_h($id) ?>" class="admin-tab is-active">การ์ด</a>
</div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="old_slug" value="<?= admin_h($isNew ? '' : $id) ?>">

  <div class="content-card-edit-layout" data-content-card-edit data-content-type="<?= admin_h($type) ?>">
    <aside class="content-card-edit-preview" aria-label="ตัวอย่างการ์ดบนหน้าเว็บ">
      <p class="content-card-edit-preview__label">ตัวอย่างการ์ดบนหน้าเว็บ</p>
      <div class="content-card-edit-preview__stage">
        <?= admin_content_card_preview_markup($item, $type, $isNew ? '' : $id) ?>
      </div>
      <p class="content-card-edit-preview__hint">อัปเดตทันทีเมื่อแก้ไขด้านขวา — แก้เนื้อหาหน้ารายละเอียดที่แท็บแก้ไขหน้า</p>
    </aside>
    <div class="content-card-edit-fields">
      <?php admin_card_start('ข้อมูลการ์ด'); ?>
      <div class="admin-grid admin-grid--2">
        <?php admin_field('หัวข้อ', 'title', $item['title'] ?? '', ['required' => true]); ?>
        <?php admin_field('Slug (URL)', 'slug', $item['slug'] ?? ($isNew ? '' : $id), ['hint' => 'เช่น tax-saving — ว่างไว้จะสร้างอัตโนมัติ']); ?>
        <?php admin_field('หมวด', 'category', $item['category'] ?? ''); ?>
        <?php admin_field('วันที่เผยแพร่', 'date_published', $item['datePublished'] ?? date('Y-m-d'), ['type' => 'date']); ?>
      </div>
      <?php admin_field('คำอธิบาย (SEO / Hero)', 'description', $item['description'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
      <?php admin_image_field('ภาพปก', 'image', $item['image'] ?? '', $cfg['coverSpec']); ?>

      <?php if ($type === 'claims'): ?>
      <?php admin_field('คำพูด (Quote)', 'quote', $item['quote'] ?? '', ['type' => 'textarea', 'rows' => 4]); ?>
      <div class="admin-grid admin-grid--2">
        <?php admin_field('ผู้รีวิว', 'author', $item['author'] ?? ''); ?>
        <?php admin_field('ผลลัพธ์', 'result', $item['result'] ?? ''); ?>
      </div>
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
      <?php admin_card_end(); ?>
    </div>
  </div>

  <?php admin_actions('content-list.php?type=' . $type, $isNew ? null : [
    'action' => 'content-delete.php',
    'label' => 'ลบรายการนี้',
    'confirm' => 'ลบรายการนี้ถาวร?',
    'fields' => ['type' => $type, 'slug' => $id],
  ]); ?>
</form>

<script src="js/content-card-preview.js"></script>
<?php admin_layout_end(); ?>
