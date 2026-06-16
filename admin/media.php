<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/image-specs.php';

admin_require_login();

global $IMAGE_SPECS;
$mediaSpec = $IMAGE_SPECS['media_library'] ?? null;

$files = admin_scan_media_files();
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $files = array_values(array_filter($files, static function ($f) use ($q) {
        return stripos($f['path'], $q) !== false;
    }));
}

admin_layout_start('คลังรูป', 'media.php');
?>

<?php admin_card_start('อัปโหลดรูปใหม่'); ?>
<div class="admin-media-upload" id="media-upload-zone">
  <p class="admin-media-upload-text">ลากรูปมาวางที่นี่ หรือ</p>
  <button type="button" class="admin-btn admin-btn--primary" id="media-upload-btn">เลือกไฟล์</button>
  <p class="admin-hint"><?= admin_h($mediaSpec['hint'] ?? 'JPG, PNG, WEBP · ไม่เกิน 8 MB') ?></p>
  <p class="admin-hint" id="media-upload-status"></p>
</div>
<input type="file" accept="image/*" id="media-file-input" multiple hidden>
<?php admin_card_end(); ?>

<?php admin_card_start('คลังรูปภาพ', count($files) . ' รูป'); ?>
<form method="get" class="admin-search-bar">
  <input class="admin-input" type="search" name="q" value="<?= admin_h($q) ?>" placeholder="ค้นหาชื่อไฟล์…">
  <button type="submit" class="admin-btn admin-btn--secondary">ค้นหา</button>
  <?php if ($q !== ''): ?><a href="media.php" class="admin-btn admin-btn--ghost">ล้าง</a><?php endif; ?>
</form>

<?php if ($files === []): ?>
  <p class="admin-hint">ยังไม่มีรูป — อัปโหลดด้านบนได้เลย</p>
<?php else: ?>
  <div class="admin-media-grid">
    <?php foreach ($files as $f): ?>
      <figure class="admin-media-item">
        <img src="../<?= admin_h($f['path']) ?>" alt="" loading="lazy">
        <figcaption>
          <span class="admin-media-group"><?= admin_h($f['group']) ?></span>
          <code class="admin-media-path" title="<?= admin_h($f['path']) ?>"><?= admin_h(basename($f['path'])) ?></code>
          <div class="admin-media-item-actions">
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-copy-path="<?= admin_h($f['path']) ?>">คัดลอก path</button>
            <form method="post" action="media-delete.php" class="admin-inline-form" onsubmit="return confirm('ลบรูปนี้ถาวร?');">
              <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
              <input type="hidden" name="path" value="<?= admin_h($f['path']) ?>">
              <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
            </form>
          </div>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_card_end(); ?>

<script>
(function () {
  var csrf = <?= json_encode(admin_csrf_token()) ?>;
  var input = document.getElementById('media-file-input');
  var btn = document.getElementById('media-upload-btn');
  var zone = document.getElementById('media-upload-zone');
  var status = document.getElementById('media-upload-status');

  function uploadFile(file) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('spec', 'media_library');
    fd.append('csrf', csrf);
    return fetch('api/upload.php', { method: 'POST', body: fd }).then(function (r) { return r.json(); });
  }

  function handleFiles(fileList) {
    var files = Array.prototype.slice.call(fileList || []);
    if (!files.length) return;
    status.textContent = 'กำลังอัปโหลด...';
    var chain = Promise.resolve();
    var ok = 0;
    files.forEach(function (file) {
      chain = chain.then(function () {
        return uploadFile(file).then(function (data) {
          if (!data.ok) throw new Error(data.error || 'อัปโหลดไม่สำเร็จ');
          ok++;
        });
      });
    });
    chain
      .then(function () {
        status.textContent = 'อัปโหลด ' + ok + ' รูปแล้ว — กำลังรีเฟรช...';
        setTimeout(function () { location.href = 'media.php'; }, 600);
      })
      .catch(function (err) {
        status.textContent = err.message || 'อัปโหลดไม่สำเร็จ';
      });
  }

  btn.addEventListener('click', function () { input.click(); });
  input.addEventListener('change', function () { handleFiles(input.files); input.value = ''; });

  zone.addEventListener('dragover', function (e) {
    e.preventDefault();
    zone.classList.add('is-dragover');
  });
  zone.addEventListener('dragleave', function () { zone.classList.remove('is-dragover'); });
  zone.addEventListener('drop', function (e) {
    e.preventDefault();
    zone.classList.remove('is-dragover');
    handleFiles(e.dataTransfer.files);
  });

  document.querySelectorAll('[data-copy-path]').forEach(function (el) {
    el.addEventListener('click', function () {
      var path = el.getAttribute('data-copy-path');
      navigator.clipboard.writeText(path).then(function () {
        el.textContent = 'คัดลอกแล้ว';
        setTimeout(function () { el.textContent = 'คัดลอก path'; }, 1500);
      });
    });
  });
})();
</script>

<?php admin_layout_end(); ?>
