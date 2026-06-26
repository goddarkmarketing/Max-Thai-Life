<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/plan-blocks.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$slug = trim($_GET['slug'] ?? '');
if ($slug === '' || $slug === 'new') {
    admin_flash('error', 'กรุณาบันทึกการ์ดแผนก่อน แล้วค่อยแก้ไขเนื้อหา');
    header('Location: plans-list.php');
    exit;
}

$details = json_read('plans-detail.json');
$detail = $details['items'][$slug] ?? null;

$plans = json_read('plans.json');
$card = null;
$previewFile = 'plans/' . $slug . '.html';
foreach ($plans['items'] ?? [] as $item) {
    $href = (string) ($item['href'] ?? '');
    if (preg_replace('#^plans/|\.html$#', '', $href) === $slug) {
        $card = $item;
        if ($href !== '') {
            $previewFile = ltrim($href, '/');
        }
        break;
    }
}

if ($detail === null) {
    admin_flash('error', 'ไม่พบแผน');
    header('Location: plans-list.php');
    exit;
}

$label = strip_tags((string) ($detail['title'] ?? $slug));

$bodyHtml = (string) ($detail['bodyHtml'] ?? '');
if (($detail['editor'] ?? '') !== 'richtext' || trim($bodyHtml) === '') {
    // นำเข้าเนื้อหาเดิม (โครงสร้างเดิม) มาเป็น HTML สำหรับแก้ไขครั้งแรก
    $bodyHtml = admin_plan_detail_to_richtext_html($detail);
}

$boot = [
    'slug' => $slug,
    'csrf' => admin_csrf_token(),
    'bodyHtml' => $bodyHtml,
    'saveUrl' => 'api/plan-richtext-save.php',
    'previewUrl' => '../' . $previewFile,
];

admin_layout_start('แก้ไขเนื้อหาแผน: ' . $label, 'plans-list.php', [
    'stylesheets' => ['vendor/quill/quill.snow.css'],
]);
?>

<div class="admin-tabs">
  <a href="plan-richtext.php?slug=<?= admin_h($slug) ?>" class="admin-tab is-active">แก้ไขเนื้อหา (Rich Text)</a>
  <a href="plan-edit.php?slug=<?= admin_h($slug) ?>" class="admin-tab">การ์ดแผน</a>
</div>

<?php admin_card_start('แก้ไขเนื้อหา: ' . $label, 'แก้ไขข้อความแบบ Rich Text — หัวเรื่องด้านบนและปุ่มท้ายหน้ายังสร้างให้อัตโนมัติ ส่วนนี้แก้เฉพาะเนื้อหาตรงกลางของหน้ารายละเอียด'); ?>
<div class="admin-list-toolbar">
  <a href="<?= admin_h($boot['previewUrl']) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูหน้า</a>
  <span class="admin-richtext-status" data-richtext-status></span>
</div>

<div class="admin-richtext-wrap">
  <div id="pe-rich-editor"></div>
</div>

<div class="admin-form-actions">
  <button type="button" class="admin-btn admin-btn--primary" data-richtext-save>บันทึกและเผยแพร่</button>
  <a href="plans-list.php" class="admin-btn admin-btn--ghost">กลับ</a>
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
