<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$slug = $_GET['slug'] ?? '';
if (($_GET['tab'] ?? '') === 'detail') {
    header('Location: plan-edit.php?slug=' . urlencode($slug));
    exit;
}

$isNew = $slug === 'new';

$plans = json_read('plans.json');
$items = $plans['items'] ?? [];
$planIndex = null;

if (!$isNew) {
    foreach ($items as $i => $plan) {
        $href = $plan['href'] ?? '';
        if (preg_replace('#^plans/|\.html$#', '', $href) === $slug) {
            $planIndex = $i;
            break;
        }
    }
}

$details = json_read('plans-detail.json');
$detailItems = $details['items'] ?? [];

if (!$isNew && $planIndex === null) {
    admin_flash('error', 'ไม่พบแผน');
    header('Location: plans-list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $oldSlug = admin_post('old_slug');
    $existingPlan = ($planIndex !== null) ? ($items[$planIndex] ?? []) : [];

    $categoryIds = array_column(admin_plan_categories(), 'id');
    $category = admin_post('category');
    if (!in_array($category, $categoryIds, true)) {
        $category = (string) ($existingPlan['category'] ?? 'savings');
    }
    $tag = admin_plan_category_label($category);

    $title = admin_post('title');
    $baseSlug = admin_slugify($title);
    if ($baseSlug === '') {
        $baseSlug = 'plan';
    }
    if ($isNew || $oldSlug === '') {
        $existingSlugs = [];
        foreach ($items as $p) {
            $s = preg_replace('#^plans/|\.html$#', '', (string) ($p['href'] ?? ''));
            if ($s !== '') {
                $existingSlugs[] = $s;
            }
        }
        $newSlug = $baseSlug;
        $n = 2;
        while (in_array($newSlug, $existingSlugs, true)) {
            $newSlug = $baseSlug . '-' . $n;
            $n++;
        }
    } else {
        $newSlug = $oldSlug;
    }

    $features = array_values(array_slice(array_filter(array_map('trim', admin_post_array('features'))), 0, 3));
    $card = [
        'category' => $category,
        'tag' => $tag,
        'title' => $title,
        'desc' => admin_post('desc'),
        'features' => $features,
        'href' => 'plans/' . $newSlug . '.html',
        'image' => admin_post('image'),
        'theme' => (string) ($existingPlan['theme'] ?? admin_plan_default_theme_for_category($category)),
    ];

    if ($isNew || $planIndex === null) {
        $card['visible'] = true;
        $items[] = $card;
    } else {
        if (array_key_exists('visible', $existingPlan)) {
            $card['visible'] = $existingPlan['visible'];
        }
        $card = admin_preserve_pin($card, $existingPlan);
        if ($oldSlug !== '' && $oldSlug !== $newSlug) {
            array_splice($items, $planIndex, 1);
            $items[] = $card;
        } else {
            $items[$planIndex] = $card;
        }
    }
    $plans['items'] = $items;
    json_write('plans.json', $plans);

    if ($isNew) {
        admin_create_content_shell('plans', $newSlug);
        admin_plan_init_richtext_detail($newSlug, $card);
    } elseif ($oldSlug !== '' && $oldSlug !== $newSlug) {
        $oldPath = ROOT_PATH . '/plans/' . $oldSlug . '.html';
        $newPath = ROOT_PATH . '/plans/' . $newSlug . '.html';
        if (file_exists($oldPath) && !file_exists($newPath)) {
            rename($oldPath, $newPath);
        }
        if (isset($detailItems[$oldSlug])) {
            $detailItems[$newSlug] = $detailItems[$oldSlug];
            unset($detailItems[$oldSlug]);
            $details['items'] = $detailItems;
            json_write('plans-detail.json', $details);
        }
    }

    require_once __DIR__ . '/includes/generate-js.php';
    generate_all_js();
    admin_flash('success', $isNew ? 'สร้างแผนแล้ว — แก้ไขเนื้อหาต่อได้เลย' : 'บันทึกและเผยแพร่แผนขึ้นเว็บแล้ว');
    header('Location: ' . ($isNew ? 'plan-richtext.php?slug=' . urlencode($newSlug) : 'plan-edit.php?slug=' . urlencode($newSlug)));
    exit;
}

$plan = ($planIndex !== null) ? $items[$planIndex] : [];
if ($isNew) {
    $newCategory = trim($_GET['category'] ?? '');
    $categoryIds = array_column(admin_plan_categories(), 'id');
    if ($newCategory !== '' && in_array($newCategory, $categoryIds, true)) {
        $plan['category'] = $newCategory;
    }
}
$planCategory = (string) ($plan['category'] ?? 'savings');
if (!in_array($planCategory, array_column(admin_plan_categories(), 'id'), true)) {
    $planCategory = 'savings';
}
$planTag = admin_plan_category_label($planCategory);
$plan['category'] = $planCategory;
$pageTitle = $isNew ? 'เพิ่มแผนประกัน' : ('แก้ไขการ์ด: ' . ($plan['title'] ?? $slug));
$listUrl = admin_plans_list_url($planCategory !== '' ? $planCategory : null);
$activeNav = admin_plans_active_nav($planCategory !== '' ? $planCategory : null);

admin_layout_start($pageTitle, $activeNav, [
    'stylesheets' => ['../css/styles.css', 'css/plan-card-edit.css'],
]);

function admin_plan_card_preview_markup(array $plan, string $slug): string
{
    $title = (string) ($plan['title'] ?? 'ชื่อแผนประกัน');
    $desc = (string) ($plan['desc'] ?? 'คำอธิบายสั้นของแผนประกัน');
    $category = (string) ($plan['category'] ?? 'savings');
    $tag = (string) ($plan['tag'] ?? admin_plan_category_label($category));
    $theme = (string) ($plan['theme'] ?? 'money');
    $image = (string) ($plan['image'] ?? 'images/plan-cards/card-savings.png');
    $features = array_values(array_filter($plan['features'] ?? [], static fn($f) => trim((string) $f) !== ''));
    if ($features === []) {
        $features = ['จุดเด่น 1'];
    }
    $features = array_slice($features, 0, 3);

    $featureHtml = '';
    foreach ($features as $feature) {
        $featureHtml .= '<li>' . admin_h((string) $feature) . '</li>';
    }

    $imgSrc = admin_h('../' . ltrim($image, '/'));
    $themeClass = admin_h(preg_replace('/[^a-z0-9_-]/i', '', $theme) ?: 'money');

    return '<div class="plan-grid plan-grid--category">'
        . '<article class="plan-card" data-plan-card-preview data-category="' . admin_h($category) . '">'
        . '<div class="plan-card-media plan-card-media--' . $themeClass . '" data-preview-media>'
        . '<img src="' . $imgSrc . '" alt="' . admin_h($title) . '" class="plan-card-img" data-preview-image decoding="async">'
        . '<span class="plan-card-tag" data-preview-tag>' . admin_h($tag) . '</span>'
        . '</div>'
        . '<div class="plan-card-body">'
        . '<h3 data-preview-title>' . admin_h($title) . '</h3>'
        . '<p data-preview-desc>' . admin_h($desc) . '</p>'
        . '<ul class="plan-card-features" data-preview-features>' . $featureHtml . '</ul>'
        . '<span class="btn btn-plan-detail" data-preview-link>ดูรายละเอียด</span>'
        . '</div>'
        . '</article>'
        . '</div>';
}
?>

<?php $planIsRichtext = !$isNew && admin_plan_uses_richtext($slug); ?>
<div class="admin-tabs">
  <?php if (!$isNew): ?>
    <a href="<?= admin_h(admin_plan_edit_content_url($slug)) ?>" class="admin-tab"><?= admin_plan_uses_richtext($slug) ? 'แก้ไขเนื้อหา (Rich Text)' : 'แก้ไขหน้า (Visual)' ?></a>
  <?php endif; ?>
  <a href="plan-edit.php?slug=<?= admin_h($isNew ? 'new' : $slug) ?>" class="admin-tab is-active">การ์ดแผน</a>
</div>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="old_slug" value="<?= admin_h($isNew ? '' : $slug) ?>">

  <div class="plan-card-edit-layout" data-plan-card-edit>
    <aside class="plan-card-edit-preview" aria-label="ตัวอย่างการ์ดบนหน้าเว็บ">
      <p class="plan-card-edit-preview__label">ตัวอย่างการ์ดบนหน้าเว็บ</p>
      <div class="plan-card-edit-preview__stage">
        <?= admin_plan_card_preview_markup($plan, $isNew ? '' : $slug) ?>
      </div>
      <p class="plan-card-edit-preview__hint">อัปเดตทันทีเมื่อแก้ไขด้านขวา — บันทึกแล้วจะไปแก้เนื้อหาหน้ารายละเอียดด้วย Rich Text</p>
    </aside>
    <div class="plan-card-edit-fields">
      <?php admin_card_start('รายละเอียดการ์ด'); ?>
      <input type="hidden" name="category" value="<?= admin_h($planCategory) ?>">
      <?php if (!$isNew): ?>
        <p class="admin-hint" style="margin-top:0">หมวด: <?= admin_h($planTag) ?> · URL: plans/<?= admin_h($slug) ?>.html (สร้างอัตโนมัติจากชื่อแผน)</p>
      <?php else: ?>
        <p class="admin-hint" style="margin-top:0">หมวด: <?= admin_h($planTag) ?> · URL จะสร้างอัตโนมัติจากชื่อแผนเมื่อบันทึก</p>
      <?php endif; ?>
      <?php admin_field('ชื่อแผน', 'title', $plan['title'] ?? '', ['required' => true]); ?>
      <?php admin_field('คำอธิบายสั้น', 'desc', $plan['desc'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
      <?php
        $featureItems = array_values($plan['features'] ?? ['']);
        $featureItems = array_slice($featureItems === [] ? [''] : $featureItems, 0, 3);
        admin_render_simple_repeater('จุดเด่น (การ์ด)', 'features', $featureItems, 'text', ['label' => 'จุดเด่น', 'max' => 3]);
      ?>
      <?php admin_image_field('ภาพปกแผน', 'image', $plan['image'] ?? '', 'plan_cover'); ?>
      <?php admin_card_end(); ?>
    </div>
  </div>

  <?php admin_actions($listUrl, ($isNew || $slug === 'new') ? null : [
    'action' => 'plan-delete.php',
    'label' => 'ลบแผนนี้',
    'confirm' => 'ลบแผนประกันนี้ถาวร?',
    'fields' => ['slug' => $slug],
  ]); ?>
</form>

<script src="js/plan-card-preview.js"></script>
<?php admin_layout_end(); ?>
