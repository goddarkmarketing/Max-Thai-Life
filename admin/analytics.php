<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/analytics.php';
require_once __DIR__ . '/includes/layout.php';

admin_require_login();

$rawData = admin_analytics_read();
$range = admin_analytics_resolve_range(
    trim((string) ($_GET['preset'] ?? '')),
    trim((string) ($_GET['from'] ?? '')),
    trim((string) ($_GET['to'] ?? ''))
);
$isFiltered = ($range['mode'] ?? '') === 'range';
$data = $isFiltered
    ? admin_analytics_filter_by_range($rawData, (string) $range['from'], (string) $range['to'])
    : $rawData;
$rangeLabel = admin_analytics_range_label($range);
$updatedAt = admin_format_datetime_th($rawData['updatedAt'] ?? '');
$devices = admin_analytics_device_summary($data);

$sections = [
    ['key' => 'articles', 'label' => 'บทความ', 'icon' => 'article'],
    ['key' => 'plans', 'label' => 'แผนประกัน', 'icon' => 'shield'],
    ['key' => 'news', 'label' => 'ข่าว/กิจกรรม', 'icon' => 'news'],
    ['key' => 'careers', 'label' => 'แนะนำอาชีพ', 'icon' => 'users'],
    ['key' => 'site', 'label' => 'หน้าเว็บทั่วไป', 'icon' => 'file'],
];

$sectionData = [];
foreach ($sections as $section) {
    $key = $section['key'];
    $sectionData[$key] = [
        'rows' => admin_analytics_rows($key, $data),
        'total' => admin_analytics_type_total($key, $data),
    ];
}

$activePreset = (string) ($range['preset'] ?? 'all');
$formFrom = $isFiltered ? (string) $range['from'] : '';
$formTo = $isFiltered ? (string) $range['to'] : '';

admin_layout_start('สถิติการเข้าชม', 'analytics.php');
?>

<form method="get" class="admin-card admin-analytics-filter">
  <div class="admin-card-head">
    <h2 class="admin-card-title">ช่วงวันที่</h2>
    <p class="admin-card-sub">
      <?php if ($isFiltered): ?>
        แสดงยอดวิวช่วง <?= admin_h($rangeLabel) ?>
      <?php else: ?>
        แสดงยอดวิวทั้งหมดตั้งแต่เริ่มนับ
      <?php endif; ?>
      <?php if ($updatedAt !== ''): ?> · อัปเดตล่าสุด <?= admin_h($updatedAt) ?><?php endif; ?>
    </p>
  </div>
  <div class="admin-card-body">
    <div class="admin-analytics-filter__row">
      <div class="admin-analytics-filter__presets">
        <a href="analytics.php" class="admin-btn admin-btn--sm<?= $activePreset === 'all' ? ' admin-btn--secondary' : ' admin-btn--ghost' ?>">ทั้งหมด</a>
        <a href="analytics.php?preset=today" class="admin-btn admin-btn--sm<?= $activePreset === 'today' ? ' admin-btn--secondary' : ' admin-btn--ghost' ?>">วันนี้</a>
        <a href="analytics.php?preset=7d" class="admin-btn admin-btn--sm<?= $activePreset === '7d' ? ' admin-btn--secondary' : ' admin-btn--ghost' ?>">7 วัน</a>
        <a href="analytics.php?preset=30d" class="admin-btn admin-btn--sm<?= $activePreset === '30d' ? ' admin-btn--secondary' : ' admin-btn--ghost' ?>">30 วัน</a>
      </div>
      <div class="admin-analytics-filter__fields">
        <label class="admin-analytics-filter__field">
          <span class="admin-analytics-filter__label">จากวันที่</span>
          <input type="date" name="from" class="admin-input admin-analytics-filter__input" value="<?= admin_h($formFrom) ?>">
        </label>
        <label class="admin-analytics-filter__field">
          <span class="admin-analytics-filter__label">ถึงวันที่</span>
          <input type="date" name="to" class="admin-input admin-analytics-filter__input" value="<?= admin_h($formTo) ?>">
        </label>
        <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm admin-analytics-filter__submit">ดูสถิติ</button>
      </div>
    </div>
    <?php if ($isFiltered): ?>
      <p class="admin-hint admin-analytics-filter__hint">กรองจากข้อมูลรายวัน — ยอดก่อนเริ่มบันทึกรายวันจะไม่รวมในช่วงที่เลือก</p>
    <?php endif; ?>
  </div>
</form>

<div class="admin-stats admin-stats--4">
  <div class="admin-stat">
    <div class="admin-stat-value"><?= admin_h(admin_analytics_format_number((int) ($data['totalViews'] ?? 0))) ?></div>
    <div class="admin-stat-label"><?= $isFiltered ? 'ยอดในช่วงที่เลือก' : 'ยอดเข้าชมรวมทั้งเว็บ' ?></div>
  </div>
  <?php foreach (array_slice($sections, 0, 4) as $section): ?>
    <div class="admin-stat">
      <div class="admin-stat-value"><?= admin_h(admin_analytics_format_number($sectionData[$section['key']]['total'])) ?></div>
      <div class="admin-stat-label"><?= admin_h($section['label']) ?><?= $isFiltered ? '' : ' (รวม)' ?></div>
    </div>
  <?php endforeach; ?>
</div>

<?php admin_card_start('อุปกรณ์ที่ใช้เข้าชม', $isFiltered ? 'ในช่วง ' . $rangeLabel : 'นับจากยอดวิวจริงบนเว็บ'); ?>
<?php if ($devices === []): ?>
  <p class="admin-hint">ยังไม่มีข้อมูล<?= $isFiltered ? 'ในช่วงที่เลือก' : ' — รอผู้เข้าชมเว็บจริงสักครู่' ?></p>
<?php else: ?>
  <div class="admin-analytics-devices">
    <?php foreach ($devices as $device): ?>
      <div class="admin-analytics-device">
        <div class="admin-analytics-device__head">
          <strong><?= admin_h($device['label']) ?></strong>
          <span><?= admin_h(admin_analytics_format_number($device['count'])) ?> · <?= admin_h((string) $device['percent']) ?>%</span>
        </div>
        <div class="admin-analytics-device__bar" aria-hidden="true">
          <span style="width: <?= min(100, (float) $device['percent']) ?>%"></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_card_end(); ?>

<?php admin_card_start('รายละเอียดแต่ละหน้า', 'เลือกหมวดจากแถบด้านบน'); ?>

<div class="admin-tabs admin-analytics-tabs" role="tablist" aria-label="หมวดสถิติ">
  <?php foreach ($sections as $i => $section):
      $stats = $sectionData[$section['key']];
  ?>
  <button
    type="button"
    class="admin-tab<?= $i === 0 ? ' is-active' : '' ?>"
    role="tab"
    aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
    data-analytics-tab="<?= admin_h($section['key']) ?>"
  >
    <?= admin_h($section['label']) ?> (<?= admin_h((string) count($stats['rows'])) ?>)
  </button>
  <?php endforeach; ?>
</div>

<?php foreach ($sections as $i => $section):
    $key = $section['key'];
    $rows = $sectionData[$key]['rows'];
    $total = $sectionData[$key]['total'];
?>
<div
  class="admin-analytics-panel<?= $i === 0 ? ' is-active' : '' ?>"
  data-analytics-panel="<?= admin_h($key) ?>"
  role="tabpanel"
  <?= $i === 0 ? '' : 'hidden' ?>
>
  <p class="admin-hint admin-analytics-panel__summary">
    รวม <strong><?= admin_h(admin_analytics_format_number($total)) ?></strong> ครั้ง · <?= admin_h((string) count($rows)) ?> หน้า
    <?php if ($isFiltered): ?> · <?= admin_h($rangeLabel) ?><?php endif; ?>
  </p>

  <?php if ($rows === []): ?>
    <p class="admin-hint">ยังไม่มีการเข้าชม<?= admin_h($section['label']) ?><?= $isFiltered ? 'ในช่วงที่เลือก' : '' ?></p>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>หน้า</th>
            <th style="width: 8rem; text-align: right;">ยอดวิว</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
          <tr>
            <td>
              <a href="<?= admin_h($row['href']) ?>" target="_blank" rel="noopener"><?= admin_h($row['title']) ?></a>
              <div class="admin-hint"><?= admin_h($row['id']) ?></div>
            </td>
            <td style="text-align: right; font-variant-numeric: tabular-nums;"><?= admin_h(admin_analytics_format_number($row['views'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php admin_card_end(); ?>

<?php admin_card_start('หมายเหตุ'); ?>
<ul class="admin-steps-list">
  <li>นับเฉพาะการเข้าชมจริงจากหน้าเว็บ — ไม่ใช้ตัวเลข Views ที่ตั้งเองใน Admin อีกต่อไป</li>
  <li>นับซ้ำจาก IP เดิมหน้าเดิมไม่เกิน 1 ครั้ง / 30 นาที</li>
  <li>เลือกช่วงวันที่ได้จากข้อมูลรายวัน — ยอดเก่าก่อนอัปเดตระบบจะยังแสดงในโหมด "ทั้งหมด" เท่านั้น</li>
  <li>ข้อมูลเก็บใน <code>data/analytics.json</code> บน server — ไม่ถูกเขียนทับเมื่อ deploy โค้ดอย่างเดียว</li>
</ul>
<?php admin_card_end(); ?>

<script src="js/analytics-dashboard.js"></script>
<?php admin_layout_end(); ?>
