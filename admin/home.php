<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('home.json');
$tab = $_GET['tab'] ?? 'hero';

// ย้ายการจัดการรีวิวไปหน้ารายการแยกต่างหาก
if ($tab === 'testimonials' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: testimonials-list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $tab = admin_post('tab', 'hero');

    if ($tab === 'hero') {
        $slides = admin_parse_hero_slides_from_post(admin_post_array('hero_slide'), 6);
        $first = $slides[0] ?? ['image' => '', 'alt' => ''];
        $data['hero'] = [
            'image' => $first['image'],
            'alt' => $first['alt'],
            'slides' => $slides,
            'avatar' => admin_post('hero_avatar'),
            'lead' => admin_post('hero_lead'),
            'ctaPrimary' => ['label' => admin_post('cta_primary_label'), 'href' => admin_post('cta_primary_href')],
            'ctaPhone' => ['label' => admin_post('cta_phone_label'), 'href' => admin_post('cta_phone_href')],
            'ctaContact' => ['label' => admin_post('cta_contact_label'), 'href' => admin_post('cta_contact_href')],
        ];
    }

    if ($tab === 'profile') {
        $fields = [];
        $labels = admin_post_array('profile_label');
        $values = admin_post_array('profile_value');
        $links = admin_post_array('profile_link');
        foreach ($labels as $i => $label) {
            $label = trim($label);
            $value = trim($values[$i] ?? '');
            if ($label === '' && $value === '') {
                continue;
            }
            $item = ['label' => $label, 'value' => $value];
            $link = trim($links[$i] ?? '');
            if ($link !== '') {
                $item['link'] = $link;
            }
            $fields[] = $item;
        }

        $data['profile'] = [
            'title' => admin_post('profile_title'),
            'subtitle' => admin_post('profile_subtitle'),
            'fields' => $fields,
        ];
    }

    if ($tab === 'sections') {
        $chipLabels = admin_post_array('chip_label');
        $chipHrefs = admin_post_array('chip_href');
        $chipImages = admin_post_array('chip_image');
        $goalChips = [];
        foreach ($chipLabels as $i => $label) {
            $label = trim($label);
            $href = trim($chipHrefs[$i] ?? '');
            if ($label === '' || $href === '') {
                continue;
            }
            $item = ['label' => $label, 'href' => $href];
            $image = trim($chipImages[$i] ?? '');
            if ($image !== '') {
                $item['image'] = $image;
            }
            if ($href === 'plans.html') {
                $item['all'] = true;
            }
            $goalChips[] = $item;
        }

        $data['plansSection'] = [
            'title' => admin_post('plans_title'),
            'subtitle' => admin_post('plans_subtitle'),
            'planLimit' => (int) admin_post('plans_limit') ?: 4,
            'goalChips' => $goalChips,
        ];

        $data['articlesSection'] = [
            'title' => admin_post('articles_title'),
            'subtitle' => admin_post('articles_subtitle'),
            'items' => $data['articlesSection']['items'] ?? [],
        ];

        $data['newsSection'] = [
            'title' => admin_post('news_title'),
            'subtitle' => admin_post('news_subtitle'),
        ];
    }

    if ($tab === 'testimonials') {
        $data['testimonialsSection'] = [
            'title' => admin_post('testimonials_title'),
            'subtitle' => admin_post('testimonials_subtitle'),
            'slides' => admin_parse_testimonials_from_post(admin_post_array('testimonial')),
        ];
    }

    if ($tab === 'inquiry') {
        $data['inquiry'] = [
            'title' => admin_post('inquiry_title'),
            'subtitle' => admin_post('inquiry_subtitle'),
            'points' => array_values(array_filter(array_map('trim', admin_post_array('inquiry_point')))),
            'formNote' => admin_post('inquiry_note'),
        ];
    }

    if ($tab === 'cta') {
        $data['ctaBanner'] = [
            'image' => admin_post('cta_banner_image'),
            'alt' => admin_post('cta_banner_alt'),
            'href' => admin_post('cta_banner_href'),
        ];
    }

    json_write('home.json', $data);
    require_once __DIR__ . '/includes/generate-js.php';
    generate_all_js();
    admin_flash('success', 'บันทึกและเผยแพร่หน้าแรกแล้ว');
    header('Location: home.php?tab=' . urlencode($tab));
    exit;
}

function admin_parse_testimonials_from_post(array $raw): array
{
    $cards = [];
    foreach ($raw as $item) {
        $quote = trim($item['quote'] ?? '');
        $author = trim($item['author'] ?? '');
        if ($quote === '') {
            continue;
        }
        $cards[] = ['quote' => $quote, 'author' => $author];
    }
    $slides = [];
    for ($i = 0; $i < count($cards); $i += 3) {
        $slides[] = array_slice($cards, $i, 3);
    }
    return $slides;
}

$hero = $data['hero'] ?? [];
$heroSlides = $hero['slides'] ?? [];
if ($heroSlides === [] && ($hero['image'] ?? '') !== '') {
    $heroSlides = [
        ['image' => $hero['image'], 'alt' => $hero['alt'] ?? ''],
    ];
}
$profile = $data['profile'] ?? [];
$plansSec = $data['plansSection'] ?? [];
$articlesSec = $data['articlesSection'] ?? [];
$testSec = $data['testimonialsSection'] ?? [];
$newsSec = $data['newsSection'] ?? [];
$inquiry = $data['inquiry'] ?? [];
$ctaBanner = $data['ctaBanner'] ?? [];
$goalChips = $plansSec['goalChips'] ?? [];

$allTestimonials = [];
foreach ($testSec['slides'] ?? [] as $slide) {
    foreach ($slide as $card) {
        $allTestimonials[] = $card;
    }
}
if ($allTestimonials === []) {
    $allTestimonials[] = ['quote' => '', 'author' => ''];
}

admin_layout_start('หน้าแรก', 'home.php');
?>

<div class="admin-tabs">
  <a href="home.php?tab=hero" class="admin-tab<?= $tab === 'hero' ? ' is-active' : '' ?>">Hero</a>
  <a href="home.php?tab=profile" class="admin-tab<?= $tab === 'profile' ? ' is-active' : '' ?>">โปรไฟล์</a>
  <a href="home.php?tab=sections" class="admin-tab<?= $tab === 'sections' ? ' is-active' : '' ?>">ส่วนเนื้อหา</a>
  <a href="testimonials-list.php" class="admin-tab">รีวิว</a>
  <a href="home.php?tab=inquiry" class="admin-tab<?= $tab === 'inquiry' ? ' is-active' : '' ?>">ฟอร์ม</a>
  <a href="home.php?tab=cta" class="admin-tab<?= $tab === 'cta' ? ' is-active' : '' ?>">CTA</a>
</div>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="tab" value="<?= admin_h($tab) ?>">

  <?php if ($tab === 'hero'): ?>
    <?php admin_card_start('Hero แบนเนอร์'); ?>
    <?php admin_render_hero_slides_repeater($heroSlides, 6); ?>
    <?php admin_image_field('รูปโปรไฟล์ (Hero)', 'hero_avatar', $hero['avatar'] ?? '', 'agent_profile'); ?>
    <?php admin_field('ข้อความแนะนำ', 'hero_lead', $hero['lead'] ?? '', ['type' => 'textarea', 'rows' => 2]); ?>
    <div class="admin-grid admin-grid--3">
      <?php admin_field('ปุ่มหลัก', 'cta_primary_label', $hero['ctaPrimary']['label'] ?? ''); ?>
      <?php admin_field('ลิงก์ปุ่มหลัก', 'cta_primary_href', $hero['ctaPrimary']['href'] ?? ''); ?>
      <?php admin_field('ปุ่มโทร', 'cta_phone_label', $hero['ctaPhone']['label'] ?? ''); ?>
      <?php admin_field('ลิงก์โทร', 'cta_phone_href', $hero['ctaPhone']['href'] ?? ''); ?>
      <?php admin_field('ปุ่มติดต่อ', 'cta_contact_label', $hero['ctaContact']['label'] ?? ''); ?>
      <?php admin_field('ลิงก์ติดต่อ', 'cta_contact_href', $hero['ctaContact']['href'] ?? ''); ?>
    </div>
    <?php admin_card_end(); ?>

  <?php elseif ($tab === 'profile'): ?>
    <?php admin_card_start('ข้อมูลตัวแทน (Panel)'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('หัวข้อ', 'profile_title', $profile['title'] ?? ''); ?>
      <?php admin_field('คำบรรยาย', 'profile_subtitle', $profile['subtitle'] ?? ''); ?>
    </div>
    <?php admin_render_profile_repeater($profile['fields'] ?? []); ?>
    <?php admin_card_end(); ?>

  <?php elseif ($tab === 'sections'): ?>
    <?php admin_card_start('ส่วนแผนประกันแนะนำ'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('หัวข้อ', 'plans_title', $plansSec['title'] ?? ''); ?>
      <?php admin_field('คำบรรยาย', 'plans_subtitle', $plansSec['subtitle'] ?? ''); ?>
      <?php admin_field('จำนวนแผนแสดง', 'plans_limit', (string) ($plansSec['planLimit'] ?? 4), ['type' => 'number']); ?>
    </div>
    <?php admin_render_goal_chip_repeater($goalChips); ?>
    <?php admin_card_end(); ?>

    <?php admin_card_start('ส่วนบทความ'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('หัวข้อ', 'articles_title', $articlesSec['title'] ?? ''); ?>
      <?php admin_field('คำบรรยาย', 'articles_subtitle', $articlesSec['subtitle'] ?? ''); ?>
    </div>
    <?php admin_card_end(); ?>

    <?php admin_card_start('ส่วนข่าว'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('หัวข้อ', 'news_title', $newsSec['title'] ?? ''); ?>
      <?php admin_field('คำบรรยาย', 'news_subtitle', $newsSec['subtitle'] ?? ''); ?>
    </div>
    <?php admin_card_end(); ?>

  <?php elseif ($tab === 'testimonials'): ?>
    <?php admin_card_start('รีวิวลูกค้า'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('หัวข้อ', 'testimonials_title', $testSec['title'] ?? ''); ?>
      <?php admin_field('คำบรรยาย', 'testimonials_subtitle', $testSec['subtitle'] ?? ''); ?>
    </div>
    <?php admin_render_testimonial_repeater($allTestimonials); ?>
    <?php admin_card_end(); ?>

  <?php elseif ($tab === 'inquiry'): ?>
    <?php admin_card_start('ฟอร์มขอใบเสนอเบี้ย'); ?>
    <div class="admin-grid admin-grid--2">
      <?php admin_field('หัวข้อ', 'inquiry_title', $inquiry['title'] ?? ''); ?>
      <?php admin_field('คำบรรยาย', 'inquiry_subtitle', $inquiry['subtitle'] ?? '', ['type' => 'textarea', 'rows' => 2]); ?>
    </div>
    <?php admin_render_simple_repeater('จุดเด่นฟอร์ม', 'inquiry_point', $inquiry['points'] ?? [''], 'text', ['label' => 'จุดเด่น']); ?>
    <?php admin_field('หมายเหตุใต้ฟอร์ม', 'inquiry_note', $inquiry['formNote'] ?? ''); ?>
    <?php admin_card_end(); ?>

  <?php elseif ($tab === 'cta'): ?>
    <?php admin_card_start('แบนเนอร์ CTA ท้ายหน้า'); ?>
    <?php admin_image_field('ภาพแบนเนอร์ CTA', 'cta_banner_image', $ctaBanner['image'] ?? '', 'cta_banner'); ?>
    <?php admin_field('Alt text', 'cta_banner_alt', $ctaBanner['alt'] ?? ''); ?>
    <?php admin_field('ลิงก์เมื่อคลิก', 'cta_banner_href', $ctaBanner['href'] ?? ''); ?>
    <?php admin_card_end(); ?>
  <?php endif; ?>

  <?php admin_actions('dashboard.php'); ?>
</form>

<?php admin_layout_end(); ?>
