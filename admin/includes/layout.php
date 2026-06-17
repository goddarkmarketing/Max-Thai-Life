<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/image-specs.php';

$IMAGE_SPECS = require __DIR__ . '/image-specs.php';

function admin_nav_groups(): array
{
    $newLeads = admin_count_new_leads();
    $inquiryBadge = $newLeads > 0 ? (string) $newLeads : '';

    return [
        [
            'label' => '',
            'items' => [
                ['href' => 'dashboard.php', 'label' => 'ภาพรวม', 'icon' => 'grid'],
            ],
        ],
        [
            'label' => 'หน้าเว็บ',
            'items' => [
                ['href' => 'site.php', 'label' => 'ตั้งค่าเว็บไซต์', 'icon' => 'settings'],
                ['href' => 'site-nav.php', 'label' => 'เมนูเว็บ', 'icon' => 'menu'],
                ['href' => 'site-footer.php', 'label' => 'Footer', 'icon' => 'layout'],
                ['href' => 'site-seo.php', 'label' => 'SEO', 'icon' => 'globe'],
                ['href' => 'home.php', 'label' => 'หน้าแรก', 'icon' => 'home'],
                ['href' => 'pages.php', 'label' => 'หน้าอื่นๆ', 'icon' => 'file'],
            ],
        ],
        [
            'label' => 'เนื้อหา',
            'items' => [
                ['href' => 'plans-list.php', 'label' => 'แผนประกัน', 'icon' => 'shield'],
                ['href' => 'content-list.php?type=articles', 'label' => 'บทความ', 'icon' => 'article'],
                ['href' => 'content-list.php?type=news', 'label' => 'ข่าว/กิจกรรม', 'icon' => 'news'],
                ['href' => 'content-list.php?type=careers', 'label' => 'แนะนำอาชีพ', 'icon' => 'users'],
                ['href' => 'content-list.php?type=claims', 'label' => 'รีวิวเคลม', 'icon' => 'heart'],
            ],
        ],
        [
            'label' => 'ระบบ',
            'items' => [
                ['href' => 'inquiries.php', 'label' => 'ข้อความติดต่อ', 'icon' => 'mail', 'badge' => $inquiryBadge],
                ['href' => 'media.php', 'label' => 'คลังรูป', 'icon' => 'image'],
                ['href' => 'backups.php', 'label' => 'สำรอง/กู้คืน', 'icon' => 'backup'],
                ['href' => 'account.php', 'label' => 'บัญชีผู้ใช้', 'icon' => 'user'],
            ],
        ],
    ];
}

function admin_nav_items(): array
{
    $items = [];
    foreach (admin_nav_groups() as $group) {
        foreach ($group['items'] as $item) {
            $items[] = $item;
        }
    }
    return $items;
}

function admin_nav_is_active(string $active, array $item): bool
{
    if ($active === '') {
        return false;
    }
    $href = $item['href'];
    if ($active === $href || str_starts_with($href, $active)) {
        return true;
    }
    if ($active === 'plans' && str_contains($href, 'plans')) {
        return true;
    }
    return false;
}

function admin_nav_icon_svg(string $icon): string
{
    $icons = [
        'grid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>',
        'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/></svg>',
        'file' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 5 6v6c0 4.2 3 7.5 7 9 4-1.5 7-4.8 7-9V6l-7-3z"/><path d="m9 12 2 2 4-4"/></svg>',
        'article' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h9l3 3v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M15 4v4h4"/><path d="M8 12h8M8 16h8M8 8h3"/></svg>',
        'news' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6z"/><path d="M7 10h6M7 14h10M7 6h.01"/><path d="M18 6v4h3"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M15 20c.3-2.2 2-4 4-4"/></svg>',
        'heart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>',
        'image' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 16l-5.5-5.5L5 20"/></svg>',
        'backup' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg>',
        'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
        'layout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
        'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>',
    ];

    return $icons[$icon] ?? $icons['file'];
}

function admin_render_sidebar(string $active = ''): void
{
    ?>
  <aside class="admin-sidebar" aria-label="เมนูหลังบ้าน">
    <div class="admin-brand">
      <span class="admin-brand-mark">MTL</span>
      <div>
        <strong>Max Thai Life</strong>
        <span>ระบบจัดการเว็บไซต์</span>
      </div>
    </div>
    <nav class="admin-nav">
      <?php foreach (admin_nav_groups() as $group): ?>
        <?php if (!empty($group['label'])): ?>
          <div class="admin-nav-group-label"><?= admin_h($group['label']) ?></div>
        <?php endif; ?>
        <?php foreach ($group['items'] as $item): ?>
          <a href="<?= admin_h($item['href']) ?>" class="admin-nav-link<?= admin_nav_is_active($active, $item) ? ' is-active' : '' ?>">
            <span class="admin-nav-icon"><?= admin_nav_icon_svg($item['icon'] ?? 'file') ?></span>
            <span class="admin-nav-label"><?= admin_h($item['label']) ?></span>
            <?php if (!empty($item['badge'])): ?>
              <span class="admin-nav-badge"><?= admin_h($item['badge']) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-foot">
      <a href="../index.html" target="_blank" rel="noopener" class="admin-btn admin-btn--sidebar admin-btn--block">ดูเว็บไซต์</a>
      <a href="logout.php" class="admin-btn admin-btn--sidebar admin-btn--block">ออกจากระบบ</a>
    </div>
  </aside>
    <?php
}

function admin_layout_start(string $title, string $active = ''): void
{
    global $IMAGE_SPECS;
    $flash = admin_get_flash();
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= admin_h($title) ?> | Max Thai Life Admin</title>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">
  <?php admin_render_sidebar($active); ?>
  <div class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1 class="admin-page-title"><?= admin_h($title) ?></h1>
      </div>
      <form method="post" action="publish.php" class="admin-publish-form">
        <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
        <button type="submit" class="admin-btn admin-btn--primary">เผยแพร่ขึ้นเว็บ</button>
      </form>
    </header>
    <?php if ($flash): ?>
      <div class="admin-alert admin-alert--<?= admin_h($flash['type']) ?>" role="status">
        <?= admin_h($flash['message']) ?>
      </div>
    <?php endif; ?>
    <main class="admin-content">
    <?php
}

function admin_layout_end(): void
{
    ?>
    </main>
  </div>
  <script src="js/admin.js"></script>
</body>
</html>
    <?php
}

function admin_visual_layout_start(string $title, string $active = 'plans-list.php'): void
{
    $flash = admin_get_flash();
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= admin_h($title) ?> | Max Thai Life Admin</title>
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="css/plan-visual.css">
</head>
<body class="admin-body plan-visual-mode">
  <?php admin_render_sidebar($active); ?>
  <div class="admin-main admin-main--visual">
    <?php if ($flash): ?>
      <div class="admin-alert admin-alert--<?= admin_h($flash['type']) ?> admin-alert--visual" role="status">
        <?= admin_h($flash['message']) ?>
      </div>
    <?php endif; ?>
    <?php
}

function admin_visual_layout_end(): void
{
    ?>
  </div>
  <script src="js/admin.js"></script>
</body>
</html>
    <?php
}

function admin_card_start(string $title = '', string $subtitle = ''): void
{
    echo '<section class="admin-card">';
    if ($title !== '') {
        echo '<header class="admin-card-head">';
        echo '<h2 class="admin-card-title">' . admin_h($title) . '</h2>';
        if ($subtitle !== '') {
            echo '<p class="admin-card-sub">' . admin_h($subtitle) . '</p>';
        }
        echo '</header>';
    }
    echo '<div class="admin-card-body">';
}

function admin_card_end(): void
{
    echo '</div></section>';
}

function admin_render_quick_link(string $href, string $icon, string $title, string $desc): void
{
    ?>
    <a href="<?= admin_h($href) ?>" class="admin-quick-link">
      <span class="admin-quick-link-icon" aria-hidden="true"><?= admin_nav_icon_svg($icon) ?></span>
      <span class="admin-quick-link-text">
        <strong><?= admin_h($title) ?></strong>
        <span><?= admin_h($desc) ?></span>
      </span>
    </a>
    <?php
}

function admin_field(string $label, string $name, $value = '', array $opts = []): void
{
    $type = $opts['type'] ?? 'text';
    $id = $opts['id'] ?? $name;
    $placeholder = $opts['placeholder'] ?? '';
    $required = !empty($opts['required']);
    $hint = $opts['hint'] ?? '';
    $rows = (int) ($opts['rows'] ?? 4);
    $class = 'admin-input';
    if ($type === 'textarea') {
        $class = 'admin-textarea';
    }
    ?>
    <div class="admin-field">
      <label class="admin-label" for="<?= admin_h($id) ?>"><?= admin_h($label) ?></label>
      <?php if ($type === 'textarea'): ?>
        <textarea class="<?= $class ?>" id="<?= admin_h($id) ?>" name="<?= admin_h($name) ?>" rows="<?= $rows ?>" placeholder="<?= admin_h($placeholder) ?>"<?= $required ? ' required' : '' ?>><?= admin_h((string) $value) ?></textarea>
      <?php elseif ($type === 'select'): ?>
        <select class="admin-select" id="<?= admin_h($id) ?>" name="<?= admin_h($name) ?>"<?= $required ? ' required' : '' ?>>
          <?php foreach (($opts['options'] ?? []) as $optVal => $optLabel): ?>
            <option value="<?= admin_h((string) $optVal) ?>"<?= (string) $value === (string) $optVal ? ' selected' : '' ?>><?= admin_h((string) $optLabel) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input class="<?= $class ?>" type="<?= admin_h($type) ?>" id="<?= admin_h($id) ?>" name="<?= admin_h($name) ?>" value="<?= admin_h((string) $value) ?>" placeholder="<?= admin_h($placeholder) ?>"<?= $required ? ' required' : '' ?>>
      <?php endif; ?>
      <?php if ($hint !== ''): ?>
        <p class="admin-hint"><?= admin_h($hint) ?></p>
      <?php endif; ?>
    </div>
    <?php
}

function admin_image_field(string $label, string $name, string $value, string $specKey): void
{
    global $IMAGE_SPECS;
    $spec = $IMAGE_SPECS[$specKey] ?? null;
    $hint = $spec['hint'] ?? 'อัปโหลด JPG หรือ PNG';
    $preview = $value !== '' ? '../' . ltrim($value, '/') : '';
    ?>
    <div class="admin-field admin-field--image" data-image-field>
      <label class="admin-label"><?= admin_h($label) ?></label>
      <div class="admin-image-box">
        <div class="admin-image-preview" data-image-preview>
          <?php if ($preview !== ''): ?>
            <img src="<?= admin_h($preview) ?>" alt="">
          <?php else: ?>
            <span class="admin-image-empty">ยังไม่มีรูป</span>
          <?php endif; ?>
        </div>
        <div class="admin-image-controls">
          <input type="hidden" name="<?= admin_h($name) ?>" value="<?= admin_h($value) ?>" data-image-input>
          <input type="file" accept="image/*" data-image-upload data-spec="<?= admin_h($specKey) ?>" hidden>
          <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-image-trigger>เลือกรูป</button>
          <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-image-clear>ลบรูป</button>
          <p class="admin-hint admin-hint--spec">
            <strong>ขนาดแนะนำ:</strong> <?= admin_h($hint) ?>
          </p>
        </div>
      </div>
    </div>
    <?php
}

function admin_actions(string $backHref = 'dashboard.php', ?array $delete = null): void
{
    ?>
    <div class="admin-actions">
      <?php if ($delete): ?>
        <form method="post" action="<?= admin_h($delete['action']) ?>" class="admin-delete-form admin-actions-left" onsubmit="return confirm('<?= admin_h($delete['confirm'] ?? 'ลบรายการนี้?') ?>');">
          <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
          <?php foreach ($delete['fields'] ?? [] as $k => $v): ?>
            <input type="hidden" name="<?= admin_h($k) ?>" value="<?= admin_h($v) ?>">
          <?php endforeach; ?>
          <button type="submit" class="admin-btn admin-btn--danger"><?= admin_h($delete['label'] ?? 'ลบรายการ') ?></button>
        </form>
      <?php endif; ?>
      <a href="<?= admin_h($backHref) ?>" class="admin-btn admin-btn--ghost">ยกเลิก</a>
      <button type="submit" class="admin-btn admin-btn--primary">บันทึก</button>
    </div>
    <?php
}

function admin_render_sections_editor(array $sections, string $prefix = 'sections'): void
{
    ?>
    <div class="admin-repeater" data-repeater="<?= admin_h($prefix) ?>" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">เนื้อหาแบ่งส่วน</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มส่วน</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($sections as $i => $section): ?>
          <?php admin_render_section_item($prefix, $i, $section); ?>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <?php admin_render_section_item($prefix, '__INDEX__', ['heading' => '', 'paragraphs' => [''], 'list' => []]); ?>
      </template>
    </div>
    <?php
}

function admin_render_section_item(string $prefix, $index, array $section): void
{
    $paragraphs = $section['paragraphs'] ?? [];
    if ($paragraphs === []) {
        $paragraphs = [''];
    }
    $list = $section['list'] ?? [];
    $listText = implode("\n", $list);
    ?>
    <article class="admin-repeater-item" data-repeater-item>
      <header class="admin-repeater-item-head">
        <strong>ส่วนที่ <?= is_numeric($index) ? ((int) $index + 1) : '' ?></strong>
        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
      </header>
      <div class="admin-grid admin-grid--2">
        <?php admin_field('หัวข้อ', "{$prefix}[{$index}][heading]", $section['heading'] ?? ''); ?>
      </div>
      <?php admin_field('ย่อหน้า (แยกด้วยบรรทัดว่าง)', "{$prefix}[{$index}][paragraphs_text]", implode("\n\n", $paragraphs), ['type' => 'textarea', 'rows' => 5]); ?>
      <?php admin_field('รายการ bullet (บรรทัดละ 1 ข้อ)', "{$prefix}[{$index}][list_text]", $listText, ['type' => 'textarea', 'rows' => 4, 'hint' => 'เว้นว่างได้หากไม่ใช้รายการ']); ?>
    </article>
    <?php
}

function admin_parse_sections_from_post(array $raw): array
{
    $sections = [];
    foreach ($raw as $section) {
        if (!is_array($section)) {
            continue;
        }
        $heading = trim($section['heading'] ?? '');
        $paragraphsText = trim($section['paragraphs_text'] ?? '');
        $listText = trim($section['list_text'] ?? '');
        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $paragraphsText) ?: [])));
        $list = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $listText) ?: [])));
        if ($heading === '' && $paragraphs === [] && $list === []) {
            continue;
        }
        $item = [];
        if ($heading !== '') {
            $item['heading'] = $heading;
        }
        if ($paragraphs !== []) {
            $item['paragraphs'] = $paragraphs;
        }
        if ($list !== []) {
            $item['list'] = $list;
        }
        $sections[] = $item;
    }
    return $sections;
}

function admin_render_link_repeater(string $title, string $prefix, array $links): void
{
    if ($links === []) {
        $links = [['label' => '', 'href' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="<?= admin_h($prefix) ?>" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title"><?= admin_h($title) ?></h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่ม</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($links as $i => $link): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="ลิงก์">ลิงก์ <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--2">
              <?php admin_field('ชื่อ', "{$prefix}_label[{$i}]", $link['label'] ?? ''); ?>
              <?php admin_field('URL', "{$prefix}_href[{$i}]", $link['href'] ?? ''); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="ลิงก์">ลิงก์</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--2">
            <div class="admin-field">
              <label class="admin-label">ชื่อ</label>
              <input class="admin-input" type="text" name="<?= admin_h($prefix) ?>_label[__INDEX__]" value="">
            </div>
            <div class="admin-field">
              <label class="admin-label">URL</label>
              <input class="admin-input" type="text" name="<?= admin_h($prefix) ?>_href[__INDEX__]" value="">
            </div>
          </div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_nav_repeater(array $items): void
{
    if ($items === []) {
        $items = admin_default_navigation();
    }
    ?>
    <div class="admin-repeater" data-repeater="nav" data-repeater-min="1">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">รายการเมนู</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มเมนู</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($items as $i => $item): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="เมนู">เมนู <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--2">
              <?php admin_field('ชื่อเมนู', "nav_label[{$i}]", $item['label'] ?? ''); ?>
              <?php admin_field('ลิงก์', "nav_href[{$i}]", $item['href'] ?? ''); ?>
            </div>
            <div class="admin-grid admin-grid--2">
              <label class="admin-check">
                <input type="checkbox" name="nav_visible[<?= (int) $i ?>]" value="1"<?= !isset($item['visible']) || $item['visible'] ? ' checked' : '' ?>>
                แสดงในเมนู
              </label>
              <label class="admin-check">
                <input type="checkbox" name="nav_cta[<?= (int) $i ?>]" value="1"<?= !empty($item['cta']) ? ' checked' : '' ?>>
                เป็นปุ่ม CTA (เน้นสี)
              </label>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="เมนู">เมนู</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--2">
            <div class="admin-field">
              <label class="admin-label">ชื่อเมนู</label>
              <input class="admin-input" type="text" name="nav_label[__INDEX__]" value="">
            </div>
            <div class="admin-field">
              <label class="admin-label">ลิงก์</label>
              <input class="admin-input" type="text" name="nav_href[__INDEX__]" value="">
            </div>
          </div>
          <div class="admin-grid admin-grid--2">
            <label class="admin-check">
              <input type="checkbox" name="nav_visible[__INDEX__]" value="1" checked>
              แสดงในเมนู
            </label>
            <label class="admin-check">
              <input type="checkbox" name="nav_cta[__INDEX__]" value="1">
              เป็นปุ่ม CTA (เน้นสี)
            </label>
          </div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_goal_chip_repeater(array $chips): void
{
    if ($chips === []) {
        $chips = [['label' => '', 'href' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="goalChips" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">การ์ดเลือกหมวดแผน (ภาพ)</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่ม</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($chips as $i => $chip): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="ปุ่ม">ปุ่ม <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--2">
              <?php admin_field('ชื่อ', "chip_label[{$i}]", $chip['label'] ?? ''); ?>
              <?php admin_field('ลิงก์', "chip_href[{$i}]", $chip['href'] ?? ''); ?>
            </div>
            <?php admin_field('รูป (ว่าง = ใช้รูปการ์ดแผน)', "chip_image[{$i}]", $chip['image'] ?? '', ['hint' => 'เช่น images/plan-cards/card-savings.png — ลิงก์ plans.html จะไม่แสดงบนหน้าแรก']); ?>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="ปุ่ม">ปุ่ม</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--2">
            <div class="admin-field">
              <label class="admin-label">ชื่อ</label>
              <input class="admin-input" type="text" name="chip_label[__INDEX__]" value="">
            </div>
            <div class="admin-field">
              <label class="admin-label">ลิงก์</label>
              <input class="admin-input" type="text" name="chip_href[__INDEX__]" value="">
            </div>
          </div>
          <div class="admin-field">
            <label class="admin-label">รูป (ว่าง = ใช้รูปการ์ดแผน)</label>
            <input class="admin-input" type="text" name="chip_image[__INDEX__]" value="">
          </div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_simple_repeater(string $title, string $prefix, array $items, string $fieldType = 'text', array $opts = []): void
{
    $labelPrefix = $opts['label'] ?? 'รายการ';
    $rows = (int) ($opts['rows'] ?? 2);
    $min = (int) ($opts['min'] ?? 0);
    if ($items === []) {
        $items = [''];
    }
    ?>
    <div class="admin-repeater" data-repeater="<?= admin_h($prefix) ?>" data-repeater-min="<?= $min ?>">
      <div class="admin-repeater-head">
        <?php if ($title !== ''): ?><h3 class="admin-repeater-title"><?= admin_h($title) ?></h3><?php endif; ?>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่ม</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($items as $i => $val): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="<?= admin_h($labelPrefix) ?>"><?= admin_h($labelPrefix) ?> <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <?php if ($fieldType === 'textarea'): ?>
              <?php admin_field($labelPrefix, "{$prefix}[{$i}]", $val, ['type' => 'textarea', 'rows' => $rows]); ?>
            <?php else: ?>
              <?php admin_field($labelPrefix, "{$prefix}[{$i}]", $val); ?>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="<?= admin_h($labelPrefix) ?>"><?= admin_h($labelPrefix) ?></strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <?php if ($fieldType === 'textarea'): ?>
            <div class="admin-field">
              <label class="admin-label"><?= admin_h($labelPrefix) ?></label>
              <textarea class="admin-textarea" name="<?= admin_h($prefix) ?>[__INDEX__]" rows="<?= $rows ?>"></textarea>
            </div>
          <?php else: ?>
            <div class="admin-field">
              <label class="admin-label"><?= admin_h($labelPrefix) ?></label>
              <input class="admin-input" type="text" name="<?= admin_h($prefix) ?>[__INDEX__]" value="">
            </div>
          <?php endif; ?>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_spec_repeater(string $title, array $specs): void
{
    if ($specs === []) {
        $specs = [['', '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="specs" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title"><?= admin_h($title) ?></h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มแถว</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($specs as $i => $row): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="แถว">แถว <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--2">
              <?php admin_field('หัวข้อ', "spec_label[{$i}]", $row[0] ?? ''); ?>
              <?php admin_field('รายละเอียด', "spec_value[{$i}]", $row[1] ?? ''); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="แถว">แถว</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--2">
            <div class="admin-field">
              <label class="admin-label">หัวข้อ</label>
              <input class="admin-input" type="text" name="spec_label[__INDEX__]" value="">
            </div>
            <div class="admin-field">
              <label class="admin-label">รายละเอียด</label>
              <input class="admin-input" type="text" name="spec_value[__INDEX__]" value="">
            </div>
          </div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_faq_repeater(array $faq): void
{
    if ($faq === []) {
        $faq = [['q' => '', 'a' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="faq" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">คำถามที่พบบ่อย</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มคำถาม</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($faq as $i => $item): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="คำถาม">คำถาม <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <?php admin_field('คำถาม', "faq_q[{$i}]", $item['q'] ?? ''); ?>
            <?php admin_field('คำตอบ', "faq_a[{$i}]", $item['a'] ?? '', ['type' => 'textarea', 'rows' => 2]); ?>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="คำถาม">คำถาม</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-field">
            <label class="admin-label">คำถาม</label>
            <input class="admin-input" type="text" name="faq_q[__INDEX__]" value="">
          </div>
          <div class="admin-field">
            <label class="admin-label">คำตอบ</label>
            <textarea class="admin-textarea" name="faq_a[__INDEX__]" rows="2"></textarea>
          </div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_who_repeater(array $blocks): void
{
    if ($blocks === []) {
        $blocks = [['title' => '', 'text' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="who" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">เหมาะกับใคร</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มบล็อก</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($blocks as $i => $block): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="บล็อก">บล็อก <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--2">
              <?php admin_field('หัวข้อ', "who_title[{$i}]", $block['title'] ?? ''); ?>
              <?php admin_field('เนื้อหา', "who_text[{$i}]", $block['text'] ?? '', ['type' => 'textarea', 'rows' => 2]); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="บล็อก">บล็อก</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--2">
            <div class="admin-field">
              <label class="admin-label">หัวข้อ</label>
              <input class="admin-input" type="text" name="who_title[__INDEX__]" value="">
            </div>
            <div class="admin-field">
              <label class="admin-label">เนื้อหา</label>
              <textarea class="admin-textarea" name="who_text[__INDEX__]" rows="2"></textarea>
            </div>
          </div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_profile_repeater(array $fields): void
{
    if ($fields === []) {
        $fields = [['label' => '', 'value' => '', 'link' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="profile" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">ฟิลด์โปรไฟล์</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มฟิลด์</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($fields as $i => $field): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="ฟิลด์">ฟิลด์ <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--3">
              <?php admin_field('Label', "profile_label[{$i}]", $field['label'] ?? ''); ?>
              <?php admin_field('Value', "profile_value[{$i}]", $field['value'] ?? ''); ?>
              <?php admin_field('Link (ถ้ามี)', "profile_link[{$i}]", $field['link'] ?? ''); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="ฟิลด์">ฟิลด์</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--3">
            <div class="admin-field"><label class="admin-label">Label</label><input class="admin-input" type="text" name="profile_label[__INDEX__]" value=""></div>
            <div class="admin-field"><label class="admin-label">Value</label><input class="admin-input" type="text" name="profile_value[__INDEX__]" value=""></div>
            <div class="admin-field"><label class="admin-label">Link (ถ้ามี)</label><input class="admin-input" type="text" name="profile_link[__INDEX__]" value=""></div>
          </div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_testimonial_repeater(array $items): void
{
    if ($items === []) {
        $items = [['quote' => '', 'author' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="testimonial" data-repeater-min="0">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">รีวิวลูกค้า</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มรีวิว</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($items as $i => $t): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="รีวิว">รีวิว <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <?php admin_field('ข้อความรีวิว', "testimonial[{$i}][quote]", $t['quote'] ?? '', ['type' => 'textarea', 'rows' => 3]); ?>
            <?php admin_field('ผู้รีวิว', "testimonial[{$i}][author]", $t['author'] ?? ''); ?>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="รีวิว">รีวิว</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-field"><label class="admin-label">ข้อความรีวิว</label><textarea class="admin-textarea" name="testimonial[__INDEX__][quote]" rows="3"></textarea></div>
          <div class="admin-field"><label class="admin-label">ผู้รีวิว</label><input class="admin-input" type="text" name="testimonial[__INDEX__][author]" value=""></div>
        </article>
      </template>
    </div>
    <?php
}

function admin_render_category_repeater(array $categories): void
{
    if ($categories === []) {
        $categories = [['filter' => '', 'label' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="categories" data-repeater-min="1">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">หมวดแผนประกัน (แท็บกรอง)</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มหมวด</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($categories as $i => $cat): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="หมวด">หมวด <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--2">
              <?php admin_field('Filter key', "cat_filter[{$i}]", $cat['filter'] ?? ''); ?>
              <?php admin_field('ชื่อแสดง', "cat_label[{$i}]", $cat['label'] ?? ''); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="หมวด">หมวด</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--2">
            <div class="admin-field"><label class="admin-label">Filter key</label><input class="admin-input" type="text" name="cat_filter[__INDEX__]" value=""></div>
            <div class="admin-field"><label class="admin-label">ชื่อแสดง</label><input class="admin-input" type="text" name="cat_label[__INDEX__]" value=""></div>
          </div>
        </article>
      </template>
    </div>
    <?php
}
