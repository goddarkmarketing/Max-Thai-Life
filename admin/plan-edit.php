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
    $newSlug = admin_post('plan_slug');
    if ($newSlug === '') {
        $newSlug = admin_slugify(admin_post('title'));
    }
    $oldSlug = admin_post('old_slug');

    $features = array_values(array_filter(array_map('trim', admin_post_array('features'))));
    $existingPlan = ($planIndex !== null) ? ($items[$planIndex] ?? []) : [];
    $category = admin_post('category');
    $card = [
        'category' => $category,
        'tag' => admin_post('tag'),
        'title' => admin_post('title'),
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
    admin_flash('success', 'บันทึกและเผยแพร่แผนขึ้นเว็บแล้ว');
    header('Location: plan-edit.php?slug=' . urlencode($newSlug));
    exit;
}

$plan = ($planIndex !== null) ? $items[$planIndex] : [];
$pageTitle = $isNew ? 'เพิ่มแผนประกัน' : ('แก้ไขการ์ด: ' . ($plan['title'] ?? $slug));

admin_layout_start($pageTitle, 'plans-list.php', [
    'stylesheets' => ['../css/styles.css', 'css/plan-card-edit.css'],
]);

function admin_plan_card_preview_markup(array $plan, string $slug): string
{
    $title = (string) ($plan['title'] ?? 'ชื่อแผนประกัน');
    $desc = (string) ($plan['desc'] ?? 'คำอธิบายสั้นของแผนประกัน');
    $tag = (string) ($plan['tag'] ?? 'Tag');
    $category = (string) ($plan['category'] ?? 'savings');
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

<?php $planIsRichtext = !$isNew && ($detailItems[$slug]['editor'] ?? '') === 'richtext'; ?>
<div class="admin-tabs">
  <?php if ($planIsRichtext): ?>
    <a href="plan-richtext.php?slug=<?= admin_h($slug) ?>" class="admin-tab">แก้ไขเนื้อหา (Rich Text)</a>
  <?php else: ?>
    <a href="plan-visual.php?slug=<?= admin_h($isNew ? 'new' : $slug) ?>" class="admin-tab<?= $isNew ? ' is-disabled' : '' ?>"<?= $isNew ? ' aria-disabled="true" tabindex="-1"' : '' ?>>แก้ไขหน้า (Visual)</a>
    <a href="plan-richtext.php?slug=<?= admin_h($isNew ? 'new' : $slug) ?>" class="admin-tab<?= $isNew ? ' is-disabled' : '' ?>"<?= $isNew ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Rich Text</a>
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
      <p class="plan-card-edit-preview__hint">อัปเดตทันทีเมื่อแก้ไขด้านขวา — แก้เนื้อหาหน้ารายละเอียดที่แท็บ Visual</p>
    </aside>
    <div class="plan-card-edit-fields">
      <?php admin_card_start('รายละเอียดการ์ด'); ?>
      <div class="admin-grid admin-grid--2">
        <?php admin_field('Slug (URL)', 'plan_slug', $isNew ? '' : $slug, ['hint' => 'เช่น money-fit — ว่างไว้จะสร้างจากชื่อแผน']); ?>
        <?php admin_field('ชื่อแผน', 'title', $plan['title'] ?? '', ['required' => true]); ?>
        <?php admin_field('Tag', 'tag', $plan['tag'] ?? ''); ?>
        <?php admin_field('หมวด (filter key)', 'category', $plan['category'] ?? 'savings', ['hint' => 'savings, protect, health, rider, pension, invest']); ?>
      </div>
      <?php admin_field('คำอธิบายสั้น', 'desc', $plan['desc'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
      <?php admin_render_simple_repeater('จุดเด่น (การ์ด)', 'features', $plan['features'] ?? [''], 'text', ['label' => 'จุดเด่น']); ?>
      <?php admin_image_field('ภาพปกแผน', 'image', $plan['image'] ?? '', 'plan_cover'); ?>
      <?php admin_card_end(); ?>
    </div>
  </div>

  <?php admin_actions('plans-list.php', ($isNew || $slug === 'new') ? null : [
    'action' => 'plan-delete.php',
    'label' => 'ลบแผนนี้',
    'confirm' => 'ลบแผนประกันนี้ถาวร?',
    'fields' => ['slug' => $slug],
  ]); ?>
</form>

<script src="js/plan-card-preview.js"></script>
<?php admin_layout_end(); ?>
