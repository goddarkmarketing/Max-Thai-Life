<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('pages.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $page = admin_post('page');
    if ($page === 'about') {
        $data['about'] = [
            'title' => admin_post('about_title'),
            'lead' => admin_post('about_lead'),
            'quote' => admin_post('about_quote'),
            'bio' => admin_post('about_bio'),
        ];
    } elseif ($page === 'contact') {
        $data['contact'] = [
            'title' => admin_post('contact_title'),
            'lead' => admin_post('contact_lead'),
        ];
    } elseif ($page === 'plans') {
        $data['plans'] = [
            'title' => admin_post('plans_title'),
            'lead' => admin_post('plans_lead'),
            'categories' => $data['plans']['categories'] ?? [],
        ];
        $filters = admin_post_array('cat_filter');
        $labels = admin_post_array('cat_label');
        $categories = [];
        foreach ($filters as $i => $filter) {
            $filter = trim($filter);
            $label = trim($labels[$i] ?? '');
            if ($filter === '' || $label === '') continue;
            $categories[] = ['filter' => $filter, 'label' => $label];
        }
        if ($categories !== []) {
            $data['plans']['categories'] = $categories;
        }
    }

    json_write('pages.json', $data);
    admin_flash('success', 'บันทึกแล้ว');
    header('Location: pages.php?page=' . urlencode($page));
    exit;
}

$page = $_GET['page'] ?? 'about';
$about = $data['about'] ?? [];
$contact = $data['contact'] ?? [];
$plans = $data['plans'] ?? [];

admin_layout_start('หน้าอื่นๆ', 'pages.php');
?>

<div class="admin-tabs">
  <a href="pages.php?page=about" class="admin-tab<?= $page === 'about' ? ' is-active' : '' ?>">เกี่ยวกับเรา</a>
  <a href="pages.php?page=contact" class="admin-tab<?= $page === 'contact' ? ' is-active' : '' ?>">ติดต่อ</a>
  <a href="pages.php?page=plans" class="admin-tab<?= $page === 'plans' ? ' is-active' : '' ?>">แผนประกัน</a>
</div>

<form method="post">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
  <input type="hidden" name="page" value="<?= admin_h($page) ?>">

  <?php if ($page === 'about'): ?>
    <?php admin_card_start('หน้าเกี่ยวกับเรา'); ?>
    <?php admin_field('หัวข้อ', 'about_title', $about['title'] ?? ''); ?>
    <?php admin_field('คำนำ', 'about_lead', $about['lead'] ?? ''); ?>
    <?php admin_field('คำคม (Quote)', 'about_quote', $about['quote'] ?? '', ['type' => 'textarea', 'rows' => 4]); ?>
    <?php admin_field('ประวัติ', 'about_bio', $about['bio'] ?? '', ['type' => 'textarea', 'rows' => 5]); ?>
    <?php admin_card_end(); ?>
  <?php elseif ($page === 'contact'): ?>
    <?php admin_card_start('หน้าติดต่อ'); ?>
    <?php admin_field('หัวข้อ', 'contact_title', $contact['title'] ?? ''); ?>
    <?php admin_field('คำนำ', 'contact_lead', $contact['lead'] ?? ''); ?>
    <?php admin_card_end(); ?>
  <?php else: ?>
    <?php admin_card_start('หน้าแผนประกัน'); ?>
    <?php admin_field('หัวข้อ', 'plans_title', $plans['title'] ?? ''); ?>
    <?php admin_field('คำนำ', 'plans_lead', $plans['lead'] ?? ''); ?>
    <?php admin_render_category_repeater($plans['categories'] ?? []); ?>
    <?php admin_card_end(); ?>
  <?php endif; ?>

  <?php admin_actions('dashboard.php'); ?>
</form>

<?php admin_layout_end(); ?>
