<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/content-blocks.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$types = admin_content_types();
$type = $_GET['type'] ?? 'articles';
if (!isset($types[$type]) || !in_array($type, ['articles', 'news', 'careers'], true)) {
    admin_flash('error', 'ไม่รองรับประเภทเนื้อหานี้');
    header('Location: content-list.php');
    exit;
}
$cfg = $types[$type];

$id = trim($_GET['id'] ?? '');
if ($id === '' || $id === 'new') {
    admin_flash('error', 'กรุณาบันทึกการ์ดก่อน แล้วค่อยแก้ไขเนื้อหา');
    header('Location: content-list.php?type=' . urlencode($type));
    exit;
}

$store = json_read($cfg['file']);
$item = $store[$cfg['itemsKey']][$id] ?? null;
if ($item === null) {
    admin_flash('error', 'ไม่พบรายการ');
    header('Location: content-list.php?type=' . urlencode($type));
    exit;
}

$label = strip_tags((string) ($item['title'] ?? $id));
$typeCfg = admin_content_type_config($type);
$dir = $typeCfg['dir'] ?? $type;
$previewUrl = '../' . $dir . '/' . $id . '.html';

$bodyHtml = (string) ($item['bodyHtml'] ?? '');
if (($item['editor'] ?? '') !== 'richtext' || trim($bodyHtml) === '') {
    $bodyHtml = admin_content_sections_to_richtext_html($item['sections'] ?? []);
}

$boot = [
    'slug' => $id,
    'csrf' => admin_csrf_token(),
    'bodyHtml' => $bodyHtml,
    'saveUrl' => 'api/content-richtext-save.php?type=' . urlencode($type),
    'previewUrl' => $previewUrl,
];

admin_layout_start('แก้ไขเนื้อหา: ' . $label, 'content-list.php?type=' . $type, [
    'stylesheets' => ['vendor/quill/quill.snow.css'],
]);
?>

<div class="admin-tabs">
  <a href="content-richtext.php?type=<?= admin_h($type) ?>&id=<?= admin_h($id) ?>" class="admin-tab is-active">แก้ไขเนื้อหา (Rich Text)</a>
  <a href="content-edit.php?type=<?= admin_h($type) ?>&id=<?= admin_h($id) ?>" class="admin-tab">การ์ด</a>
</div>

<?php admin_card_start('แก้ไขเนื้อหา: ' . $label, 'แก้ไขข้อความแบบ Rich Text — หัวเรื่องด้านบนและปุ่มท้ายหน้ายังสร้างให้อัตโนมัติ ส่วนนี้แก้เฉพาะเนื้อหาบทความ'); ?>
<div class="admin-list-toolbar">
  <a href="<?= admin_h($boot['previewUrl']) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูหน้า</a>
  <span class="admin-richtext-status" data-richtext-status></span>
</div>

<div class="admin-richtext-wrap">
  <div id="pe-rich-editor"></div>
</div>

<div class="admin-form-actions">
  <button type="button" class="admin-btn admin-btn--primary" data-richtext-save>บันทึกและเผยแพร่</button>
  <a href="content-list.php?type=<?= admin_h($type) ?>" class="admin-btn admin-btn--ghost">กลับ</a>
</div>
<p class="admin-hint">หมายเหตุ: เนื้อหานี้บันทึกเป็น HTML และแสดงบนหน้าเว็บโดยตรง ควรกรอกเฉพาะเนื้อหาที่เชื่อถือได้</p>
<?php admin_card_end(); ?>

<style>
  /* ปลด overflow:hidden เพื่อให้ทูลบาร์ sticky ยึดกับขอบจอได้ (เฉพาะหน้านี้) */
  .admin-card {
    overflow: visible;
  }
  .admin-richtext-wrap {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: visible;
  }
  #pe-rich-editor {
    min-height: 460px;
    font-size: 1.02rem;
    line-height: 1.8;
  }
  .ql-toolbar.ql-snow {
    border: 0;
    border-bottom: 1px solid #e2e8f0;
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
    position: sticky;
    top: 72px;
    background: #f8fafc;
    z-index: 20;
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
  }
  .ql-container.ql-snow {
    border: 0;
  }
  .ql-editor {
    min-height: 460px;
  }
  .admin-richtext-status {
    font-size: 0.85rem;
    color: #64748b;
  }
  .admin-richtext-status.is-ok {
    color: #047857;
  }
  .admin-richtext-status.is-error {
    color: #b91c1c;
  }
  .ql-editor img {
    max-width: 100%;
    height: auto;
    cursor: pointer;
  }
  .pe-img-overlay {
    position: absolute;
    display: none;
    box-sizing: border-box;
    border: 2px solid #c8102e;
    pointer-events: none;
    z-index: 10;
  }
  .pe-img-handle {
    position: absolute;
    width: 14px;
    height: 14px;
    background: #c8102e;
    border: 2px solid #fff;
    border-radius: 50%;
    pointer-events: auto;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
  }
  .pe-img-handle--nw { left: -8px; top: -8px; cursor: nwse-resize; }
  .pe-img-handle--ne { right: -8px; top: -8px; cursor: nesw-resize; }
  .pe-img-handle--sw { left: -8px; bottom: -8px; cursor: nesw-resize; }
  .pe-img-handle--se { right: -8px; bottom: -8px; cursor: nwse-resize; }
</style>

<script>window.PAGE_RICHTEXT_DATA = <?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="vendor/quill/quill.js"></script>
<script src="js/page-richtext-editor.js"></script>

<?php admin_layout_end(); ?>
