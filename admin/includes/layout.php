<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/image-specs.php';
require_once __DIR__ . '/lucide.php';

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
                ['href' => 'analytics.php', 'label' => 'สถิติการเข้าชม', 'icon' => 'eye'],
            ],
        ],
        [
            'label' => 'หน้าเว็บ',
            'items' => [
                ['href' => 'site.php', 'label' => 'ตั้งค่าเว็บไซต์', 'icon' => 'settings'],
                ['href' => 'site-nav.php', 'label' => 'เมนูเว็บ', 'icon' => 'menu'],
                ['href' => 'site-footer.php', 'label' => 'Footer', 'icon' => 'layout'],
                ['href' => 'site-social.php', 'label' => 'โซเชียล', 'icon' => 'share'],
                ['href' => 'site-contact-dock.php', 'label' => 'ปุ่มลอย', 'icon' => 'message-circle'],
                ['href' => 'site-seo.php', 'label' => 'SEO', 'icon' => 'globe'],
                ['href' => 'home.php', 'label' => 'หน้าแรก', 'icon' => 'home'],
                ['href' => 'landing-pages.php', 'label' => 'หน้าเว็บ', 'icon' => 'file'],
            ],
        ],
        [
            'label' => 'เนื้อหา',
            'items' => [
                [
                    'href' => 'plans-list.php',
                    'label' => 'แผนประกัน',
                    'icon' => 'shield',
                    'children' => admin_plan_nav_children(),
                ],
                ['href' => 'content-list.php?type=articles', 'label' => 'บทความ', 'icon' => 'article'],
                ['href' => 'content-list.php?type=news', 'label' => 'ข่าว/กิจกรรม', 'icon' => 'news'],
                ['href' => 'content-list.php?type=careers', 'label' => 'แนะนำอาชีพ', 'icon' => 'users'],
                ['href' => 'content-list.php?type=claims', 'label' => 'รีวิวเคลม', 'icon' => 'heart'],
                ['href' => 'testimonials-list.php', 'label' => 'รีวิวลูกค้า', 'icon' => 'star'],
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
    $href = (string) ($item['href'] ?? '');
    if ($active === $href) {
        return true;
    }
    if ($active === 'plans' && str_contains($href, 'plans')) {
        return true;
    }
    return false;
}

function admin_nav_item_is_open(string $active, array $item): bool
{
    if (admin_nav_is_active($active, $item)) {
        return true;
    }
    foreach ($item['children'] ?? [] as $child) {
        if (admin_nav_is_active($active, $child)) {
            return true;
        }
    }
    return false;
}

function admin_render_sidebar(string $active = ''): void
{
    $brand = admin_brand_meta();
    ?>
  <aside class="admin-sidebar" aria-label="เมนูหลังบ้าน">
    <div class="admin-brand">
      <img src="<?= admin_h(admin_brand_logo_url()) ?>" alt="<?= admin_h($brand['name']) ?>" class="admin-brand-logo" width="46" height="46">
      <div>
        <strong><?= admin_h($brand['name']) ?></strong>
        <span>ระบบจัดการเว็บไซต์</span>
      </div>
    </div>
    <nav class="admin-nav">
      <?php foreach (admin_nav_groups() as $group): ?>
        <?php if (!empty($group['label'])): ?>
          <div class="admin-nav-group-label"><?= admin_h($group['label']) ?></div>
        <?php endif; ?>
        <?php foreach ($group['items'] as $item): ?>
          <?php
            $children = $item['children'] ?? [];
            $hasChildren = $children !== [];
            $itemActive = admin_nav_is_active($active, $item);
            $groupOpen = $hasChildren && admin_nav_item_is_open($active, $item);
          ?>
          <div class="admin-nav-group<?= $hasChildren ? ' has-children' : '' ?><?= $groupOpen ? ' is-open' : '' ?>">
            <a href="<?= admin_h($item['href']) ?>" class="admin-nav-link<?= $itemActive ? ' is-active' : '' ?>">
              <span class="admin-nav-icon"><?= admin_nav_icon_svg($item['icon'] ?? 'file') ?></span>
              <span class="admin-nav-label"><?= admin_h($item['label']) ?></span>
              <?php if (!empty($item['badge'])): ?>
                <span class="admin-nav-badge"><?= admin_h($item['badge']) ?></span>
              <?php endif; ?>
            </a>
            <?php if ($hasChildren): ?>
              <div class="admin-nav-sub">
                <?php foreach ($children as $child): ?>
                  <a href="<?= admin_h($child['href']) ?>" class="admin-nav-sublink<?= admin_nav_is_active($active, $child) ? ' is-active' : '' ?>">
                    <?= admin_h($child['label']) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
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

function admin_layout_start(string $title, string $active = '', array $opts = []): void
{
    global $IMAGE_SPECS;
    $flash = admin_get_flash();
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= admin_h($title) ?> | Wealth Agent TL Admin</title>
  <link rel="stylesheet" href="css/admin.css">
  <?php foreach ($opts['stylesheets'] ?? [] as $cssHref): ?>
  <link rel="stylesheet" href="<?= admin_h($cssHref) ?>">
  <?php endforeach; ?>
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
  <?php admin_lucide_scripts(); ?>
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
  <title><?= admin_h($title) ?> | Wealth Agent TL Admin</title>
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="css/plan-visual.css">
  <link rel="stylesheet" href="css/page-visual-editor.css">
  <?php admin_lucide_scripts(); ?>
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

function admin_render_detail_table(array $rows): void
{
    echo '<div class="admin-table-wrap"><table class="admin-table admin-table--details"><tbody>';
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $label = (string) ($row['label'] ?? '');
        $value = (string) ($row['value'] ?? '—');
        echo '<tr><th scope="row">' . admin_h($label) . '</th><td>';
        if (($row['badge'] ?? '') === 'ok') {
            echo '<span class="admin-badge admin-badge--ok">' . admin_h($value) . '</span>';
        } else {
            echo admin_h($value);
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
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

function admin_render_nav_children_repeater(array $children, array $opts = []): void
{
    $title = (string) ($opts['title'] ?? 'เมนูย่อย');
    $min = (int) ($opts['min'] ?? 0);
    $emptyRow = !empty($opts['emptyRow']);
    if ($children === [] && $emptyRow) {
        $children = [['label' => '', 'href' => '', 'category' => '']];
    }
    ?>
    <div class="admin-repeater" data-repeater="navChildren" data-repeater-min="<?= $min ?>">
      <div class="admin-repeater-head">
        <h3 class="admin-subtitle"><?= admin_h($title) ?></h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มรายการ</button>
      </div>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($children as $i => $child): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="รายการ">รายการ <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <div class="admin-grid admin-grid--3">
              <?php admin_field('ชื่อ', "nav_child_label[{$i}]", $child['label'] ?? ''); ?>
              <?php admin_field('ลิงก์', "nav_child_href[{$i}]", $child['href'] ?? ''); ?>
              <?php admin_field('หมวด (category)', "nav_child_category[{$i}]", $child['category'] ?? '', ['hint' => 'เช่น savings, protect — ว่างได้']); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="รายการ">รายการ</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-grid admin-grid--3">
            <div class="admin-field">
              <label class="admin-label">ชื่อ</label>
              <input class="admin-input" type="text" name="nav_child_label[__INDEX__]" value="">
            </div>
            <div class="admin-field">
              <label class="admin-label">ลิงก์</label>
              <input class="admin-input" type="text" name="nav_child_href[__INDEX__]" value="">
            </div>
            <div class="admin-field">
              <label class="admin-label">หมวด (category)</label>
              <input class="admin-input" type="text" name="nav_child_category[__INDEX__]" value="">
            </div>
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

function admin_render_hero_slides_repeater(array $slides, int $max = 6): void
{
    global $IMAGE_SPECS;
    $spec = $IMAGE_SPECS['hero_banner'] ?? null;
    $hint = $spec['hint'] ?? '1920 × 800 px · JPG/PNG';
    if ($slides === []) {
        $slides = [['image' => '', 'alt' => '']];
    }
    $slides = array_slice(array_values($slides), 0, $max);
    ?>
    <div class="admin-repeater" data-repeater="heroSlides" data-repeater-min="1" data-repeater-max="<?= (int) $max ?>">
      <div class="admin-repeater-head">
        <h3 class="admin-repeater-title">สไลด์แบนเนอร์ (สูงสุด <?= (int) $max ?> ภาพ)</h3>
        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-repeater-add>+ เพิ่มสไลด์</button>
      </div>
      <p class="admin-hint admin-hint--tight">ภาพแรกจะแสดงก่อน · ขนาดแนะนำ <?= admin_h($hint) ?></p>
      <div class="admin-repeater-list" data-repeater-list>
        <?php foreach ($slides as $i => $slide): ?>
          <article class="admin-repeater-item" data-repeater-item>
            <header class="admin-repeater-item-head">
              <strong data-repeater-label data-label-prefix="สไลด์">สไลด์ <?= (int) $i + 1 ?></strong>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
            </header>
            <?php admin_image_field('ภาพแบนเนอร์', "hero_slide[{$i}][image]", $slide['image'] ?? '', 'hero_banner'); ?>
            <?php admin_field('Alt text', "hero_slide[{$i}][alt]", $slide['alt'] ?? ''); ?>
          </article>
        <?php endforeach; ?>
      </div>
      <template data-repeater-template>
        <article class="admin-repeater-item" data-repeater-item>
          <header class="admin-repeater-item-head">
            <strong data-repeater-label data-label-prefix="สไลด์">สไลด์</strong>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-repeater-remove>ลบ</button>
          </header>
          <div class="admin-field admin-field--image" data-image-field>
            <label class="admin-label">ภาพแบนเนอร์</label>
            <div class="admin-image-box">
              <div class="admin-image-preview" data-image-preview>
                <span class="admin-image-empty">ยังไม่มีรูป</span>
              </div>
              <div class="admin-image-controls">
                <input type="hidden" name="hero_slide[__INDEX__][image]" value="" data-image-input>
                <input type="file" accept="image/*" data-image-upload data-spec="hero_banner" hidden>
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-image-trigger>เลือกรูป</button>
                <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-image-clear>ลบรูป</button>
                <p class="admin-hint admin-hint--spec">
                  <strong>ขนาดแนะนำ:</strong> <?= admin_h($hint) ?>
                </p>
              </div>
            </div>
          </div>
          <div class="admin-field">
            <label class="admin-label">Alt text</label>
            <input class="admin-input" type="text" name="hero_slide[__INDEX__][alt]" value="">
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
    $max = (int) ($opts['max'] ?? 0);
    if ($items === []) {
        $items = [''];
    }
    if ($max > 0) {
        $items = array_slice(array_values($items), 0, $max);
    }
    ?>
    <div class="admin-repeater" data-repeater="<?= admin_h($prefix) ?>" data-repeater-min="<?= $min ?>"<?= $max > 0 ? ' data-repeater-max="' . $max . '"' : '' ?>>
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
