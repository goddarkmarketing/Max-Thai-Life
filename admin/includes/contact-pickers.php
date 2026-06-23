<?php
declare(strict_types=1);

require_once __DIR__ . '/lucide.php';

/** @return array<string, string> */
function admin_contact_icon_options(): array
{
    return [
        'phone' => 'โทร',
        'message-circle' => 'แชท / LINE',
        'mail' => 'อีเมล',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'send' => 'ส่งข้อความ',
        'map-pin' => 'ที่อยู่',
        'file-text' => 'เอกสาร',
        'user-round' => 'ผู้ติดต่อ',
        'globe' => 'เว็บไซต์',
    ];
}

function admin_preset_color_for_style(string $style): string
{
    return match ($style) {
        'facebook' => '#1877f2',
        'line' => '#06c755',
        'email' => '#015fd9',
        'phone' => '#015fd9',
        'quote' => '#38bdf8',
        'blue' => '#015fd9',
        default => '#015fd9',
    };
}

function admin_normalize_hex_color(string $hex, string $fallback = '#015fd9'): string
{
    $hex = trim($hex);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
        return strtolower($hex);
    }
    if (preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return '#' . strtolower($hex);
    }
    return $fallback;
}

function admin_normalize_contact_item(array $item): array
{
    $icons = array_keys(admin_contact_icon_options());
    $icon = (string) ($item['icon'] ?? 'message-circle');
    if (!in_array($icon, $icons, true)) {
        $icon = 'message-circle';
    }

    $color = (string) ($item['color'] ?? '');
    if ($color === '' && !empty($item['style'])) {
        $color = admin_preset_color_for_style((string) $item['style']);
    }
    if ($color === '') {
        $color = '#015fd9';
    }

    $item['icon'] = $icon;
    $item['color'] = admin_normalize_hex_color($color);
    unset($item['style']);

    return $item;
}

function admin_contact_picker_render_icon_field(string $name, string $value): void
{
    $value = $value !== '' ? $value : 'message-circle';
    $options = admin_contact_icon_options();
    if (!isset($options[$value])) {
        $value = 'message-circle';
    }
    ?>
    <div class="nav-inline-edit__field admin-icon-picker-field">
      <span class="nav-inline-edit__label">ไอคอน</span>
      <div class="admin-icon-picker" data-icon-picker>
        <input type="hidden" name="<?= admin_h($name) ?>" value="<?= admin_h($value) ?>" data-icon-input>
        <div class="admin-icon-picker__grid" role="radiogroup" aria-label="เลือกไอคอน">
          <?php foreach ($options as $icon => $label): ?>
            <button
              type="button"
              class="admin-icon-picker__btn<?= $icon === $value ? ' is-selected' : '' ?>"
              data-icon="<?= admin_h($icon) ?>"
              aria-label="<?= admin_h($label) ?>"
              aria-pressed="<?= $icon === $value ? 'true' : 'false' ?>"
              title="<?= admin_h($label) ?>"
            >
              <?= admin_lucide_icon($icon, 18) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php
}

function admin_contact_picker_render_color_field(string $name, string $value): void
{
    $value = admin_normalize_hex_color($value !== '' ? $value : '#015fd9');
    ?>
    <div class="nav-inline-edit__field admin-color-picker-field">
      <span class="nav-inline-edit__label">สีปุ่ม</span>
      <div class="admin-color-picker" data-color-picker data-initial="<?= admin_h($value) ?>">
        <div class="admin-color-picker__panel">
          <div class="admin-color-picker__sat" data-color-sat>
            <div class="admin-color-picker__sat-cursor" data-color-sat-cursor></div>
          </div>
          <input type="range" class="admin-color-picker__hue" data-color-hue min="0" max="360" value="0" aria-label="เลือกเฉดสี">
        </div>
        <div class="admin-color-picker__hex-row">
          <span class="admin-color-picker__swatch" data-color-swatch style="background:<?= admin_h($value) ?>"></span>
          <label class="admin-color-picker__hex-label">
            <span>HEX</span>
            <input
              type="text"
              name="<?= admin_h($name) ?>"
              class="admin-input admin-color-picker__hex"
              value="<?= admin_h($value) ?>"
              data-color-hex
              maxlength="7"
              spellcheck="false"
              autocomplete="off"
            >
          </label>
          <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-color-picker__copy" data-color-copy title="คัดลอก HEX">
            <?= admin_lucide_icon('copy', 16) ?>
          </button>
        </div>
      </div>
    </div>
    <?php
}
