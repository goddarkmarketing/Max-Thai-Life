<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$slug = $_GET['slug'] ?? '';
$tab = $_GET['tab'] ?? 'card';
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
$detail = $isNew ? [] : ($detailItems[$slug] ?? []);

if (!$isNew && $planIndex === null && $detail === []) {
    admin_flash('error', 'ไม่พบแผน');
    header('Location: plans-list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $saveTab = admin_post('save_tab');
    $newSlug = admin_post('plan_slug');
    if ($newSlug === '') {
        $newSlug = admin_slugify(admin_post('title'));
    }
    $oldSlug = admin_post('old_slug');

    if ($saveTab === 'card') {
        $features = array_values(array_filter(array_map('trim', admin_post_array('features'))));
        $card = [
            'category' => admin_post('category'),
            'tag' => admin_post('tag'),
            'title' => admin_post('title'),
            'desc' => admin_post('desc'),
            'features' => $features,
            'href' => 'plans/' . $newSlug . '.html',
            'image' => admin_post('image'),
            'theme' => admin_post('theme'),
        ];

        if ($isNew || $planIndex === null) {
            $card['visible'] = true;
            $items[] = $card;
        } else {
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

        $slug = $newSlug;
        $isNew = false;
    }

    if ($saveTab === 'detail') {
        $benefits = array_values(array_filter(array_map('trim', admin_post_array('benefits'))));
        $specLabels = admin_post_array('spec_label');
        $specValues = admin_post_array('spec_value');
        $specs = [];
        foreach ($specLabels as $i => $label) {
            $label = trim($label);
            $value = trim($specValues[$i] ?? '');
            if ($label === '' && $value === '') continue;
            $specs[] = [$label, $value];
        }

        $faqQ = admin_post_array('faq_q');
        $faqA = admin_post_array('faq_a');
        $faq = [];
        foreach ($faqQ as $i => $q) {
            $q = trim($q);
            $a = trim($faqA[$i] ?? '');
            if ($q === '') continue;
            $faq[] = ['q' => $q, 'a' => $a];
        }

        $whoTitles = admin_post_array('who_title');
        $whoTexts = admin_post_array('who_text');
        $whoBlocks = [];
        foreach ($whoTitles as $i => $title) {
            $title = trim($title);
            $text = trim($whoTexts[$i] ?? '');
            if ($title === '' && $text === '') continue;
            $whoBlocks[] = ['title' => $title, 'text' => $text];
        }

        $brochure = array_values(array_filter(array_map('trim', admin_post_array('brochure'))));

        $detailSlug = admin_post('plan_slug') ?: $slug;
        if ($detailSlug === 'new') {
            $detailSlug = admin_slugify(admin_post('detail_title'));
        }

    $entry = [
        'title' => admin_post('detail_title'),
            'breadcrumb' => admin_post('breadcrumb'),
            'description' => admin_post('detail_description'),
            'heroLead' => admin_post('hero_lead'),
            'overview' => admin_post('overview'),
            'highlight' => admin_post('highlight'),
            'benefits' => $benefits,
            'specs' => $specs,
            'whoBlocks' => $whoBlocks,
            'faq' => $faq,
            'disclaimer' => admin_post('disclaimer'),
            'ctaTitle' => admin_post('cta_title'),
            'ctaLead' => admin_post('cta_lead'),
        ];
        if ($brochure !== []) {
            $entry['brochureImages'] = $brochure;
        }

        $detailItems[$detailSlug] = $entry;
        $details['items'] = $detailItems;
        json_write('plans-detail.json', $details);

        if ($isNew || !file_exists(ROOT_PATH . '/plans/' . $detailSlug . '.html')) {
            admin_create_content_shell('plans', $detailSlug);
        }

        $slug = $detailSlug;
        $isNew = false;
    }

    admin_flash('success', 'บันทึกแผนประกันแล้ว');
    header('Location: plan-edit.php?slug=' . urlencode($slug) . '&tab=' . urlencode($saveTab));
    exit;
}

$plan = ($planIndex !== null) ? $items[$planIndex] : [];
$detail = $detailItems[$slug] ?? [];
$tab = in_array($tab, ['card', 'detail'], true) ? $tab : 'card';
$pageTitle = $isNew ? 'เพิ่มแผนประกัน' : ('แก้ไขแผน: ' . ($plan['title'] ?? $detail['title'] ?? $slug));

admin_layout_start($pageTitle, 'plans-list.php');
?>

<div class="admin-tabs">
  <a href="plan-visual.php?slug=<?= admin_h($isNew ? 'new' : $slug) ?>" class="admin-tab<?= $isNew ? ' is-disabled' : '' ?>"<?= $isNew ? ' aria-disabled="true" tabindex="-1"' : '' ?>>แก้ไขหน้า (Visual)</a>
  <a href="plan-edit.php?slug=<?= admin_h($isNew ? 'new' : $slug) ?>&tab=card" class="admin-tab<?= $tab === 'card' ? ' is-active' : '' ?>">การ์ดแผน</a>
  <a href="plan-edit.php?slug=<?= admin_h($isNew ? 'new' : $slug) ?>&tab=detail" class="admin-tab<?= $tab === 'detail' ? ' is-active' : '' ?>">ฟอร์มรายละเอียด</a>
</div>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="save_tab" value="<?= admin_h($tab) ?>">
  <input type="hidden" name="old_slug" value="<?= admin_h($isNew ? '' : $slug) ?>">

  <?php if ($tab === 'card'): ?>
    <?php admin_card_start('การ์ดแผน (หน้ารายการ)'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('Slug (URL)', 'plan_slug', $isNew ? '' : $slug, ['hint' => 'เช่น money-fit — ว่างไว้จะสร้างจากชื่อแผน']); ?>
      <?php admin_field('ชื่อแผน', 'title', $plan['title'] ?? '', ['required' => true]); ?>
      <?php admin_field('Tag', 'tag', $plan['tag'] ?? ''); ?>
      <?php admin_field('หมวด (filter key)', 'category', $plan['category'] ?? 'savings', ['hint' => 'savings, protect, health, rider, pension, invest']); ?>
      <?php admin_field('Theme CSS', 'theme', $plan['theme'] ?? 'money'); ?>
    </div>
    <?php admin_field('คำอธิบายสั้น', 'desc', $plan['desc'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
    <?php admin_render_simple_repeater('จุดเด่น (การ์ด)', 'features', $plan['features'] ?? [''], 'text', ['label' => 'จุดเด่น']); ?>
    <?php admin_image_field('ภาพปกแผน', 'image', $plan['image'] ?? '', 'plan_cover'); ?>
    <?php admin_card_end(); ?>
  <?php else: ?>
    <?php admin_card_start('Hero & ภาพรวม'); ?>
    <?php if ($isNew): ?>
      <?php admin_field('Slug (URL)', 'plan_slug', '', ['hint' => 'ต้องตรงกับการ์ดแผน']); ?>
    <?php endif; ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('ชื่อแผน', 'detail_title', $detail['title'] ?? ''); ?>
      <?php admin_field('Breadcrumb', 'breadcrumb', $detail['breadcrumb'] ?? ''); ?>
    </div>
    <?php admin_field('Meta Description', 'detail_description', $detail['description'] ?? '', ['type' => 'textarea', 'rows' => 2]); ?>
    <?php admin_field('Hero Lead', 'hero_lead', $detail['heroLead'] ?? '', ['type' => 'textarea', 'rows' => 2]); ?>
    <?php admin_field('ภาพรวมแผน', 'overview', $detail['overview'] ?? '', ['type' => 'textarea', 'rows' => 4]); ?>
    <?php admin_field('จุดขายหลัก (Highlight)', 'highlight', $detail['highlight'] ?? ''); ?>
    <?php admin_card_end(); ?>

    <?php admin_card_start('จุดเด่นและผลประโยชน์'); ?>
    <?php admin_render_simple_repeater('', 'benefits', $detail['benefits'] ?? [''], 'textarea', ['label' => 'ข้อ', 'rows' => 2]); ?>
    <?php admin_card_end(); ?>

    <?php admin_card_start('ข้อมูลแผน (ตาราง)'); ?>
    <?php admin_render_spec_repeater('', $detail['specs'] ?? [['', '']]); ?>
    <?php admin_card_end(); ?>

    <?php admin_card_start('เหมาะกับใคร'); ?>
    <?php admin_render_who_repeater($detail['whoBlocks'] ?? [['title' => '', 'text' => '']]); ?>
    <?php admin_card_end(); ?>

    <?php admin_card_start('FAQ'); ?>
    <?php admin_render_faq_repeater($detail['faq'] ?? [['q' => '', 'a' => '']]); ?>
    <?php admin_card_end(); ?>

    <?php admin_card_start('รูปภาพเพิ่มเติม', 'ใส่รูปประกอบเนื้อหาได้หลายรูป — ว่างได้หากไม่ต้องการ'); ?>
    <div class="admin-repeater" data-repeater="brochure" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">รูปภาพ</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มรูป</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php
          $brochure = $detail['brochureImages'] ?? [];
          if ($brochure === []) $brochure = [''];
          foreach ($brochure as $i => $img):
        ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="รูป">รูป <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <?php admin_image_field('ภาพ', "brochure[{$i}]", $img, 'plan_content'); ?>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="หน้า">หน้า</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-field admin-field--image" data-image-field>
            <label class="admin-label">ภาพ</label>
            <div class="admin-image-box">
              <div class="admin-image-preview" data-image-preview><span class="admin-image-empty">ยังไม่มีรูป</span></div>
              <div class="admin-image-controls">
                <input type="hidden" name="brochure[__INDEX__]" value="" data-image-input>
                <input type="file" accept="image/*" data-image-upload data-spec="plan_content" hidden>
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-image-trigger>เลือกรูป</button>
                <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-image-clear>ลบรูป</button>
                <p class="admin-hint admin-hint--spec"><strong>ขนาดแนะนำ:</strong> 1200 × 1697 px · PNG/JPG · อัตราส่วน A4 แนวตั้ง</p>
              </div>
            </div>
          </div>
        </article>
      </template>
    </div>
    <?php admin_card_end(); ?>

    <?php admin_card_start('CTA & Disclaimer'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('CTA หัวข้อ', 'cta_title', $detail['ctaTitle'] ?? ''); ?>
      <?php admin_field('CTA คำบรรยาย', 'cta_lead', $detail['ctaLead'] ?? ''); ?>
    </div>
    <?php admin_field('Disclaimer', 'disclaimer', $detail['disclaimer'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
    <?php admin_card_end(); ?>
  <?php endif; ?>

  <?php admin_actions('plans-list.php', ($isNew || $slug === 'new') ? null : [
    'action' => 'plan-delete.php',
    'label' => 'ลบแผนนี้',
    'confirm' => 'ลบแผนประกันนี้ถาวร?',
    'fields' => ['slug' => $slug],
  ]); ?>
</form>

<?php admin_layout_end(); ?>
