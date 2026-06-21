<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/generate-seo.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$data = json_read('site.json');
$brand = $data['brand'] ?? [];
$meta = admin_normalize_meta($data['meta'] ?? [], $brand);

if (($data['meta'] ?? []) !== $meta) {
    $data['meta'] = $meta;
    json_write('site.json', $data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_verify_csrf($_POST['csrf'] ?? null)) {
    $data = admin_apply_site_seo_post($data);
    json_write('site.json', $data);
    admin_flash('success', 'บันทึก SEO แล้ว — กดเผยแพร่ขึ้นเว็บเพื่ออัปเดต sitemap และหน้าเว็บ');
    header('Location: site-seo.php');
    exit;
}

$meta = admin_normalize_meta($data['meta'] ?? [], $brand);
$local = $meta['localBusiness'] ?? [];
$hasSiteUrl = admin_seo_base_url($meta) !== '';
$sitemapExists = is_file(ROOT_PATH . '/sitemap.xml');
$robotsExists = is_file(ROOT_PATH . '/robots.txt');
$urlCount = $hasSiteUrl ? count(admin_seo_collect_urls($meta)) : 0;

admin_layout_start('SEO', 'site-seo.php');
?>

<form method="post" class="admin-seo-form">
  <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">

  <?php admin_card_start('ข้อมูลพื้นฐาน SEO', 'ใช้เป็นค่าเริ่มต้นทั้งเว็บ'); ?>
  <?php admin_field('URL เว็บไซต์ (โดเมนจริง)', 'meta_site_url', $meta['siteUrl'] ?? '', [
      'hint' => 'เช่น https://www.maxthailife.com — จำเป็นสำหรับ sitemap และ canonical',
      'placeholder' => 'https://www.example.com',
  ]); ?>
  <?php admin_field('คำอธิบายเริ่มต้น (Meta Description)', 'meta_description', $meta['description'] ?? '', [
      'type' => 'textarea',
      'rows' => 3,
      'hint' => 'แนะนำ 120–160 ตัวอักษร',
  ]); ?>
  <?php admin_card_end(); ?>

  <?php admin_card_start('Open Graph & การแชร์'); ?>
  <?php admin_field('OG Title', 'meta_og_title', $meta['ogTitle'] ?? '', ['hint' => 'หัวข้อเมื่อแชร์ใน Facebook / LINE']); ?>
  <?php admin_field('OG Description', 'meta_og_description', $meta['ogDescription'] ?? '', [
      'type' => 'textarea',
      'rows' => 2,
  ]); ?>
  <?php admin_image_field('รูป Open Graph', 'meta_og_image', $meta['ogImage'] ?? '', 'logo'); ?>
  <?php admin_card_end(); ?>

  <?php admin_card_start('การติดตาม & Search Console'); ?>
  <?php admin_field('Google Analytics ID', 'meta_analytics_id', $meta['analyticsId'] ?? '', ['hint' => 'เช่น G-XXXXXXXXXX · เว้นว่างถ้าไม่ใช้']); ?>
  <?php admin_field('Google Search Console Verification', 'meta_google_verification', $meta['googleSiteVerification'] ?? '', [
      'hint' => 'ค่า content จาก meta tag google-site-verification',
  ]); ?>
  <?php admin_card_end(); ?>

  <?php admin_card_start('Local SEO', 'ช่วยให้ Google เข้าใจธุรกิจในพื้นที่'); ?>
  <label class="admin-check">
    <input type="checkbox" name="local_enabled" value="1"<?= !empty($local['enabled']) ? ' checked' : '' ?>>
    เปิดใช้ Schema LocalBusiness บนหน้าหลัก
  </label>
  <div class="admin-grid admin-grid--2">
    <?php admin_field('ที่อยู่ / พื้นที่', 'local_address', $local['address'] ?? ''); ?>
    <?php admin_field('พื้นที่ให้บริการ', 'local_area', $local['areaServed'] ?? ''); ?>
  </div>
  <?php admin_field('ลิงก์ Google Business Profile', 'local_gbp_url', $local['googleBusinessUrl'] ?? '', [
      'placeholder' => 'https://maps.google.com/...',
  ]); ?>
  <p class="admin-hint">ชื่อ โทร และใบอนุญาตดึงจากเมนู <a href="site.php">ข้อมูลเว็บไซต์</a> อัตโนมัติ</p>
  <?php admin_card_end(); ?>

  <?php admin_card_start('SEO รายหน้า', 'ตั้ง Title และ Description ของหน้าคงที่'); ?>
  <div class="admin-table-wrap">
    <table class="admin-table admin-seo-pages-table">
      <thead>
        <tr>
          <th>หน้า</th>
          <th>Title</th>
          <th>Meta Description</th>
          <th>Index</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (admin_seo_static_pages() as $slug => $defaults): ?>
          <?php
          $page = $meta['pages'][$slug] ?? $defaults;
          $fieldKey = str_replace('.', '_', $slug);
          ?>
          <tr>
            <td>
              <strong><?= admin_h($defaults['label']) ?></strong>
              <div class="admin-hint"><code><?= admin_h($slug) ?></code></div>
            </td>
            <td>
              <input
                class="admin-input"
                type="text"
                name="page_title_<?= admin_h($fieldKey) ?>"
                value="<?= admin_h($page['title'] ?? '') ?>"
              >
            </td>
            <td>
              <textarea
                class="admin-textarea admin-seo-page-desc"
                name="page_desc_<?= admin_h($fieldKey) ?>"
                rows="2"
                data-seo-count
                maxlength="320"
              ><?= admin_h($page['description'] ?? '') ?></textarea>
            </td>
            <td>
              <label class="admin-check admin-check--center">
                <input
                  type="checkbox"
                  name="page_index_<?= admin_h($fieldKey) ?>"
                  value="1"
                  <?= !empty($page['indexable']) ? ' checked' : '' ?>
                >
                แสดงใน Google
              </label>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php admin_card_end(); ?>

  <?php admin_card_start('ไฟล์ SEO', 'สร้างอัตโนมัติเมื่อกดเผยแพร่ขึ้นเว็บ'); ?>
  <ul class="admin-seo-checklist">
    <li class="<?= $hasSiteUrl ? 'is-ok' : 'is-warn' ?>">
      <?= $hasSiteUrl ? '✓' : '!' ?> ตั้ง URL เว็บไซต์แล้ว
    </li>
    <li class="<?= $sitemapExists && $hasSiteUrl ? 'is-ok' : 'is-warn' ?>">
      <?= $sitemapExists && $hasSiteUrl ? '✓' : '!' ?>
      sitemap.xml
      <?php if ($sitemapExists && $hasSiteUrl): ?>
        — <?= (int) $urlCount ?> URL
        · <a href="../sitemap.xml" target="_blank" rel="noopener">ดูไฟล์</a>
      <?php endif; ?>
    </li>
    <li class="<?= $robotsExists ? 'is-ok' : 'is-warn' ?>">
      <?= $robotsExists ? '✓' : '!' ?>
      robots.txt
      <?php if ($robotsExists): ?>
        · <a href="../robots.txt" target="_blank" rel="noopener">ดูไฟล์</a>
      <?php endif; ?>
    </li>
  </ul>
  <?php if (!$hasSiteUrl): ?>
    <p class="admin-hint admin-hint--warn">กรอก URL เว็บไซต์แล้วกดเผยแพร่ เพื่อสร้าง sitemap.xml</p>
  <?php endif; ?>
  <?php admin_card_end(); ?>

  <?php admin_actions('dashboard.php'); ?>
</form>

<script src="js/seo-admin.js"></script>
<?php admin_layout_end(); ?>
