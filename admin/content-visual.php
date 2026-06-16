<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$type = $_GET['type'] ?? 'articles';
if (!in_array($type, ['articles', 'news'], true)) {
    admin_flash('error', 'Visual editor รองรับเฉพาะบทความและข่าว');
    header('Location: content-list.php');
    exit;
}

$id = trim($_GET['id'] ?? '');
if ($id === '' || $id === 'new') {
    admin_flash('error', 'กรุณาบันทึกรายการก่อน แล้วค่อยแก้ไขแบบ visual');
    header('Location: content-list.php?type=' . urlencode($type));
    exit;
}

$types = admin_content_types();
$cfg = $types[$type];
$store = json_read($cfg['file']);
$item = $store[$cfg['itemsKey']][$id] ?? null;
if ($item === null) {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: content-list.php?type=' . urlencode($type));
    exit;
}

$coverSpec = $cfg['coverSpec'];
$dataAttr = $type === 'news' ? 'data-news-id' : 'data-article-id';
$baseList = $type === 'news' ? 'content-list.php?type=news' : 'content-list.php?type=articles';
$listLabel = $type === 'news' ? 'ข่าว/กิจกรรม' : 'บทความ';
$listUrl = $type === 'news' ? '../news.html' : '../products.html';

$payload = [
    'type' => $type,
    'slug' => $id,
    'csrf' => admin_csrf_token(),
    'item' => $item,
    'coverSpec' => $coverSpec,
    'listLabel' => $listLabel,
    'listUrl' => $listUrl,
];

$pageTitle = 'แก้ไขแบบหน้าจริง · ' . ($item['title'] ?? $id);
admin_visual_layout_start($pageTitle, $baseList);
?>

<div class="pe-admin-bar">
  <div class="pe-admin-bar-brand">
    แก้ไข<?= admin_h($listLabel) ?> <span>· <?= admin_h($item['title'] ?? $id) ?></span>
  </div>
  <div class="pe-admin-bar-actions">
    <span class="pe-status" id="pe-status"></span>
    <a href="content-edit.php?type=<?= admin_h($type) ?>&id=<?= admin_h($id) ?>" class="pe-btn pe-btn--ghost">ฟอร์มเต็ม</a>
    <a href="<?= admin_h($baseList) ?>" class="pe-btn pe-btn--ghost">กลับรายการ</a>
    <a href="<?= admin_h(admin_content_preview_url($type, $id)) ?>" target="_blank" rel="noopener" class="pe-btn pe-btn--ghost">ดูหน้า</a>
    <button type="button" class="pe-btn pe-btn--ghost" id="pe-save">บันทึก</button>
    <button type="button" class="pe-btn pe-btn--primary" id="pe-publish">บันทึก + เผยแพร่</button>
  </div>
</div>

<div class="pe-workspace">
  <div class="pe-canvas-wrap" id="pe-canvas-wrap">
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

    <header class="page-hero">
      <div class="page-hero-inner" id="content-hero-inner"></div>
    </header>

    <section class="section">
      <div class="section-inner">
        <div id="content-visual-root"></div>
      </div>
    </section>
  </div>
</div>

<input type="file" accept="image/*" id="pe-file-input" hidden>

<script>
  window.CONTENT_VISUAL_DATA = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="js/plan-rich-editor.js"></script>
<script src="js/content-visual-editor.js"></script>

<?php admin_visual_layout_end(); ?>
