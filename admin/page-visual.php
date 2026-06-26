<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/landing-pages.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$pageKey = $_GET['page'] ?? 'about';
if (!in_array($pageKey, admin_landing_page_keys(), true)) {
    admin_flash('error', 'ไม่พบหน้า');
    header('Location: landing-pages.php');
    exit;
}

// หน้าที่ใช้ Text Editor (Rich Text) ไม่ใช้ Block Builder — ส่งต่อไปยังเอดิเตอร์ที่ถูกต้อง
if ((admin_landing_page_meta()[$pageKey]['editor'] ?? '') === 'richtext') {
    header('Location: page-richtext.php?page=' . urlencode($pageKey));
    exit;
}

$data = json_read('pages.json');
$data = admin_normalize_pages_data($data);
json_write('pages.json', $data);

$site = json_read('site.json');
$pageData = $data[$pageKey] ?? [];
if (!is_array($pageData) || $pageData === []) {
    $pageData = admin_default_landing_page($pageKey);
} else {
    $pageData = admin_normalize_landing_page($pageKey, $pageData);
}
$meta = admin_landing_page_meta()[$pageKey];

$payload = [
    'page' => $pageKey,
    'csrf' => admin_csrf_token(),
    'pageData' => $pageData,
    'meta' => $meta,
    'agent' => $site['agent'] ?? [],
    'brand' => $site['brand'] ?? [],
    'sectionCatalog' => admin_landing_section_catalog(),
    'blockImageHints' => array_map(
        static fn (string $t): string => admin_landing_block_image_hint($t),
        array_keys(admin_landing_block_catalog())
    ),
    'previewUrl' => '../' . $meta['file'],
];

$pageTitle = 'แก้ไขหน้า · ' . ($meta['label'] ?? $pageKey);
admin_visual_layout_start($pageTitle, 'landing-pages.php');
?>

<div class="pe-admin-bar">
  <div class="pe-admin-bar-brand">
    แก้ไขหน้า <span>· <?= admin_h($meta['label'] ?? $pageKey) ?></span>
  </div>
  <div class="pe-admin-bar-actions">
    <span class="pe-status" id="pe-status"></span>
    <a href="landing-pages.php" class="pe-btn pe-btn--ghost">กลับรายการ</a>
    <a href="<?= admin_h($payload['previewUrl']) ?>" target="_blank" rel="noopener" class="pe-btn pe-btn--ghost">ดูหน้า</a>
    <button type="button" class="pe-btn pe-btn--ghost" id="pe-save">บันทึก</button>
    <button type="button" class="pe-btn pe-btn--primary" id="pe-publish">บันทึก + เผยแพร่</button>
  </div>
</div>

<div class="pe-workspace pe-workspace--panels pe-workspace--builder">
  <div class="pe-preview-stage" id="pe-preview-stage">
    <div class="pe-preview-scroll" id="pe-preview-scroll">
      <div class="pe-preview-frame" id="pe-preview-frame"></div>
    </div>
  </div>

  <div class="pe-edit-backdrop" id="pe-edit-backdrop" hidden></div>
  <aside class="pe-edit-panel pe-edit-panel--builder is-open" id="pe-edit-panel" aria-label="Block Builder">
    <header class="pe-edit-panel-head">
      <div>
        <p class="pe-edit-panel-kicker" id="pe-panel-kicker">Block Builder</p>
        <h2 class="pe-edit-panel-title" id="pe-panel-title">จัดการหน้า</h2>
      </div>
      <button type="button" class="pe-edit-panel-close" id="pe-panel-close" title="ปิดการแก้ไข" aria-label="ปิดการแก้ไข"><span aria-hidden="true">&times;</span></button>
    </header>
    <nav class="pe-panel-tabs" role="tablist" aria-label="แท็บแก้ไข">
      <button type="button" class="pe-panel-tab is-active" role="tab" id="pe-tab-btn-tools" data-pe-tab="tools" aria-selected="true" aria-controls="pe-tab-tools">เครื่องมือ</button>
      <button type="button" class="pe-panel-tab" role="tab" id="pe-tab-btn-layers" data-pe-tab="layers" aria-selected="false" aria-controls="pe-tab-layers">เลเยอร์</button>
      <button type="button" class="pe-panel-tab" role="tab" id="pe-tab-btn-edit" data-pe-tab="edit" aria-selected="false" aria-controls="pe-tab-edit">แก้ไข</button>
    </nav>
    <div class="pe-edit-panel-body">
      <div class="pe-tab-panel is-active" id="pe-tab-tools" role="tabpanel" aria-labelledby="pe-tab-btn-tools">
        <div class="pe-tools-palette" id="pe-tools-palette" aria-label="บล็อกเครื่องมือ"></div>
      </div>
      <div class="pe-tab-panel" id="pe-tab-layers" role="tabpanel" aria-labelledby="pe-tab-btn-layers" hidden>
        <div class="pe-section-rail" id="pe-section-rail" aria-label="รายการ Section"></div>
      </div>
      <div class="pe-tab-panel" id="pe-tab-edit" role="tabpanel" aria-labelledby="pe-tab-btn-edit" hidden>
        <div class="pe-tab-edit-scroll">
          <p class="pe-edit-form-label" id="pe-edit-form-label" hidden>แก้ไข Section</p>
          <div class="pe-edit-panel-empty" id="pe-edit-panel-empty">
            <p>เลือก Section จากแท็บ <strong>เลเยอร์</strong> หรือลากบล็อกจาก <strong>เครื่องมือ</strong></p>
          </div>
          <form class="pe-edit-form" id="pe-edit-form" hidden novalidate></form>
        </div>
        <footer class="pe-edit-panel-foot pe-edit-panel-foot--sticky" id="pe-edit-panel-foot" hidden>
          <button type="button" class="admin-btn admin-btn--ghost" id="pe-panel-cancel">ยกเลิก</button>
          <button type="button" class="admin-btn admin-btn--primary" id="pe-panel-apply">บันทึก</button>
        </footer>
      </div>
    </div>
  </aside>
</div>

<input type="file" accept="image/*" id="pe-file-input" hidden>

<script>
  window.PAGE_VISUAL_DATA = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="../js/page-block-render.js"></script>
<script src="../js/section-headers.js"></script>
<?php if ($pageKey === 'claimReviews'): ?>
<script src="../js/claim-reviews-data.js"></script>
<?php endif; ?>
<script src="js/page-block-builder.js"></script>
<script src="js/page-visual-editor.js"></script>

<?php admin_visual_layout_end(); ?>
