<?php

declare(strict_types=1);



require_once __DIR__ . '/includes/auth.php';

require_once __DIR__ . '/includes/helpers.php';

require_once __DIR__ . '/includes/layout.php';



admin_require_login();



$slug = trim($_GET['slug'] ?? '');

if ($slug === '' || $slug === 'new') {

    admin_flash('error', 'กรุณาบันทึกการ์ดแผนก่อน แล้วค่อยแก้ไขแบบ visual');

    header('Location: plans-list.php');

    exit;

}



$details = json_read('plans-detail.json');

$detail = $details['items'][$slug] ?? null;



$plans = json_read('plans.json');

$card = null;

foreach ($plans['items'] ?? [] as $item) {

    $href = $item['href'] ?? '';

    if (preg_replace('#^plans/|\.html$#', '', $href) === $slug) {

        $card = $item;

        break;

    }

}



if ($detail === null) {

    $detail = [

        'title' => $card['title'] ?? $slug,

        'breadcrumb' => $card['title'] ?? $slug,

        'description' => $card['desc'] ?? '',

        'heroLead' => $card['desc'] ?? '',

        'overview' => '',

        'highlight' => '',

        'benefits' => [],

        'specs' => [],

        'whoBlocks' => [],

        'faq' => [],

        'disclaimer' => '',

        'ctaTitle' => 'สนใจแผนนี้?',

        'ctaLead' => '',

        'image' => $card['image'] ?? '',

    ];

}



if (!isset($detail['sectionOrder'])) {
    $detail['sectionOrder'] = ['overview', 'benefits', 'specs', 'who', 'faq'];
} else {
    $detail['sectionOrder'] = array_values(array_filter(
        $detail['sectionOrder'],
        static fn (string $id): bool => $id !== 'brochure'
    ));
}



$payload = [

    'slug' => $slug,

    'csrf' => admin_csrf_token(),

    'detail' => $detail,

    'card' => $card,

    'imageSpec' => [

        'cover' => '960 × 540 px · JPG/PNG · 16:9',

        'gallery' => '960 × 540 px · JPG/PNG · ใส่ได้หลายรูป',

    ],

];



$pageTitle = 'แก้ไขแบบหน้าจริง · ' . ($detail['title'] ?? $slug);

admin_visual_layout_start($pageTitle, 'plans-list.php');

?>



<div class="pe-admin-bar">

  <div class="pe-admin-bar-brand">

    แก้ไขแบบหน้าจริง <span>· <?= admin_h($detail['title'] ?? $slug) ?></span>

  </div>

  <div class="pe-admin-bar-actions">

    <span class="pe-status" id="pe-status"></span>

    <a href="plan-edit.php?slug=<?= admin_h($slug) ?>&tab=card" class="pe-btn pe-btn--ghost">การ์ดแผน</a>

    <a href="plans-list.php" class="pe-btn pe-btn--ghost">กลับรายการ</a>

    <button type="button" class="pe-btn pe-btn--ghost" id="pe-save">บันทึก</button>

    <button type="button" class="pe-btn pe-btn--primary" id="pe-publish">บันทึก + เผยแพร่</button>

  </div>

</div>



<div class="pe-workspace">

  <div class="pe-canvas-wrap" id="pe-canvas-wrap" data-plan-slug="<?= admin_h($slug) ?>">

    <header class="site-header">

      <div class="header-inner">

        <a href="../index.html" class="brand">

          <img src="../images/logo/LOGO-THAILIFE.png" alt="" class="brand-logo" width="46" height="46">

          <span class="brand-text">

            <span class="brand-name">Max Thai Life</span>

            <span class="brand-sub">กระดาษ — คลิกแก้ไขข้อความได้เลย</span>

          </span>

        </a>

      </div>

    </header>



    <header class="page-hero page-hero--plan">

      <div class="page-hero-inner" id="plan-hero-inner"></div>

    </header>



    <section class="section">

      <div class="section-inner">

        <div id="plan-detail-root"></div>

      </div>

    </section>



    <section class="cta-band reveal" id="plan-cta"></section>

  </div>

</div>



<input type="file" accept="image/*" id="pe-file-input" hidden>



<script>

  window.PLAN_VISUAL_DATA = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

</script>

<script src="js/plan-rich-editor.js"></script>

<script src="js/plan-visual-editor.js"></script>



<?php admin_visual_layout_end(); ?>

