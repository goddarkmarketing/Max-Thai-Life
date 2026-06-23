<?php
declare(strict_types=1);

require_once __DIR__ . '/contact-pickers.php';

/**
 * @param array{save?:string,toggle?:string,delete?:string,return?:string} $api
 */
function admin_inline_list_table_head(string $hrefLabel = 'URL'): void
{
    ?>
    <colgroup>
      <col class="nav-col-order">
      <col class="nav-col-label">
      <col class="nav-col-href">
      <col class="nav-col-status">
      <col class="nav-col-actions">
    </colgroup>
    <thead>
      <tr>
        <th class="nav-col-order">#</th>
        <th class="nav-col-label">ชื่อ</th>
        <th class="nav-col-href"><?= admin_h($hrefLabel) ?></th>
        <th class="nav-col-status">สถานะ</th>
        <th class="nav-col-actions"></th>
      </tr>
    </thead>
    <?php
}

/**
 * @param array{save?:string,toggle?:string,delete?:string,return?:string,reorder?:string} $api
 */
function admin_inline_list_render_view_row(
    string $section,
    int $col,
    int $index,
    array $item,
    array $api = [],
    bool $showPreview = true,
    bool $useSlug = false
): void {
    $api = array_merge([
        'save' => 'footer-save.php',
        'toggle' => 'footer-toggle.php',
        'delete' => 'footer-delete.php',
        'return' => 'site-footer.php',
    ], $api);

    $label = (string) ($item['label'] ?? '');
    $href = (string) ($item['href'] ?? '');
    $text = (string) ($item['text'] ?? '');
    $visible = admin_footer_link_visible($item);
    $displayHref = $useSlug ? admin_footer_href_slug($href) : ($href !== '' ? $href : $text);
    $search = strtolower($label . ' ' . $href . ' ' . $text . ' ' . $displayHref);
    ?>
    <tr
      class="nav-row nav-row--main footer-row footer-row--view"
      data-footer-view
      data-footer-section="<?= admin_h($section) ?>"
      data-footer-col="<?= $col ?>"
      data-footer-index="<?= $index ?>"
      data-search-text="<?= admin_h($search) ?>"
    >
      <td class="nav-col-order">
        <div class="nav-col-order-inner">
          <button type="button" class="nav-drag-handle" data-footer-drag aria-label="ลากสลับลำดับ" title="ลากเพื่อสลับลำดับ">
            <?= admin_inline_drag_icon() ?>
          </button>
          <span class="nav-order-num" data-footer-order-num><?= $index + 1 ?></span>
        </div>
      </td>
      <td class="nav-col-label">
        <button type="button" class="nav-inline-trigger" data-footer-edit>
          <strong><?= admin_h($label) ?></strong>
        </button>
        <?php if ($section === 'topCta' && ($item['variant'] ?? 'white') === 'outline'): ?>
          <span class="admin-badge admin-badge--muted">ขอบขาว</span>
        <?php endif; ?>
        <?php if (($section === 'contactDock' || $section === 'socialLink') && !empty($item['color'])): ?>
          <span class="admin-badge admin-badge--muted admin-color-swatch-badge" style="background:<?= admin_h((string) $item['color']) ?>"></span>
        <?php elseif (($section === 'contactDock' || $section === 'socialLink') && !empty($item['style'])): ?>
          <span class="admin-badge admin-badge--muted"><?= admin_h((string) $item['style']) ?></span>
        <?php endif; ?>
        <?php if (!empty($item['icon'])): ?>
          <span class="admin-badge admin-badge--muted"><?= admin_h((string) $item['icon']) ?></span>
        <?php endif; ?>
      </td>
      <td class="nav-col-href">
        <button type="button" class="nav-inline-trigger nav-inline-trigger--code" data-footer-edit>
          <code><?= admin_h($displayHref) ?></code>
        </button>
      </td>
      <td class="nav-col-status">
        <button type="button" class="nav-status-trigger" data-footer-edit>
          <?php if ($visible): ?>
            <span class="admin-badge admin-badge--ok">เผยแพร่</span>
          <?php else: ?>
            <span class="admin-badge admin-badge--muted">ซ่อน</span>
          <?php endif; ?>
        </button>
      </td>
      <td class="nav-col-actions">
        <div class="admin-table-actions admin-table-actions--tight">
          <?php if ($showPreview && $href !== ''): ?>
            <a href="<?= admin_h(admin_footer_preview_url($href)) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--sm">ดูลิงก์</a>
          <?php endif; ?>
          <form method="post" action="<?= admin_h($api['toggle']) ?>" class="admin-inline-form">
            <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
            <input type="hidden" name="section" value="<?= admin_h($section) ?>">
            <input type="hidden" name="col" value="<?= $col ?>">
            <input type="hidden" name="index" value="<?= $index ?>">
            <input type="hidden" name="return" value="<?= admin_h($api['return']) ?>">
            <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm"><?= $visible ? 'ซ่อน' : 'แสดง' ?></button>
          </form>
          <form method="post" action="<?= admin_h($api['delete']) ?>" class="admin-inline-form" onsubmit="return confirm('ลบ <?= admin_h($label) ?> ?');">
            <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
            <input type="hidden" name="section" value="<?= admin_h($section) ?>">
            <input type="hidden" name="col" value="<?= $col ?>">
            <input type="hidden" name="index" value="<?= $index ?>">
            <input type="hidden" name="return" value="<?= admin_h($api['return']) ?>">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">ลบ</button>
          </form>
        </div>
      </td>
    </tr>
    <?php
}

/**
 * @param array{save?:string,toggle?:string,delete?:string,return?:string} $api
 */
function admin_inline_list_render_edit_row(
    string $section,
    int $col,
    string $index,
    array $item = [],
    array $api = [],
    bool $hidden = true
): void {
    $api = array_merge([
        'save' => 'footer-save.php',
        'return' => 'site-footer.php',
    ], $api);

    $label = (string) ($item['label'] ?? '');
    $href = (string) ($item['href'] ?? '');
    $text = (string) ($item['text'] ?? '');
    $visible = $item === [] ? true : admin_footer_link_visible($item);
    $external = !empty($item['external']);
    $variant = ($item['variant'] ?? 'white') === 'outline' ? 'outline' : 'white';
    $icon = (string) ($item['icon'] ?? 'message-circle');
    $color = (string) ($item['color'] ?? '');
    if ($color === '' && !empty($item['style'])) {
        $color = admin_preset_color_for_style((string) $item['style']);
    }
    if ($color === '') {
        $color = '#015fd9';
    }
    $color = admin_normalize_hex_color($color);
    $isContactForm = $section === 'contactDock' || $section === 'socialLink';
    $formClass = 'nav-inline-edit' . ($isContactForm ? ' nav-inline-edit--contact' : '');
    ?>
    <tr
      class="nav-row nav-row--edit footer-row footer-row--edit"
      data-footer-edit-row
      data-footer-section="<?= admin_h($section) ?>"
      data-footer-col="<?= $col ?>"
      data-footer-index="<?= admin_h($index) ?>"
      <?= $hidden ? 'hidden' : '' ?>
    >
      <td colspan="5">
        <form method="post" action="<?= admin_h($api['save']) ?>" class="<?= admin_h($formClass) ?>">
          <input type="hidden" name="csrf" value="<?= admin_h(admin_csrf_token()) ?>">
          <input type="hidden" name="section" value="<?= admin_h($section) ?>">
          <input type="hidden" name="col" value="<?= $col ?>">
          <input type="hidden" name="index" value="<?= admin_h($index) ?>">
          <input type="hidden" name="return" value="<?= admin_h($api['return']) ?>">

          <?php if ($isContactForm): ?>
          <div class="nav-inline-edit__grid">
            <label class="nav-inline-edit__field">
              <span class="nav-inline-edit__label"><?= $section === 'socialLink' ? 'ชื่อ (aria-label)' : 'ชื่อ' ?></span>
              <input type="text" name="label" class="admin-input" value="<?= admin_h($label) ?>" required>
            </label>
            <label class="nav-inline-edit__field">
              <span class="nav-inline-edit__label">URL</span>
              <input type="text" name="href" class="admin-input" value="<?= admin_h($href) ?>" required placeholder="contact.html, tel:..., mailto:... หรือ https://...">
            </label>
            <div class="nav-inline-edit__pickers">
              <?php admin_contact_picker_render_icon_field('icon', $icon); ?>
              <?php admin_contact_picker_render_color_field('color', $color); ?>
            </div>
            <div class="nav-inline-edit__footer">
              <div class="nav-inline-edit__checks">
                <label class="admin-check">
                  <input type="checkbox" name="visible" value="1"<?= $visible ? ' checked' : '' ?>>
                  แสดงบนเว็บ
                </label>
              </div>
              <div class="nav-inline-edit__actions">
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">บันทึก</button>
                <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-footer-cancel>ยกเลิก</button>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="nav-inline-edit__fields">
            <label class="nav-inline-edit__field">
              <span class="nav-inline-edit__label"><?= $section === 'socialLink' ? 'ชื่อ (aria-label)' : 'ชื่อ' ?></span>
              <input type="text" name="label" class="admin-input" value="<?= admin_h($label) ?>" required>
            </label>
            <?php if ($section === 'agentContact'): ?>
              <label class="nav-inline-edit__field nav-inline-edit__field--grow">
                <span class="nav-inline-edit__label">ข้อความแสดง</span>
                <input type="text" name="text" class="admin-input" value="<?= admin_h($text) ?>" required placeholder="@lineid หรือ 085-xxx-xxxx">
              </label>
              <label class="nav-inline-edit__field nav-inline-edit__field--grow">
                <span class="nav-inline-edit__label">ลิงก์ (ไม่บังคับ)</span>
                <input type="text" name="href" class="admin-input" value="<?= admin_h($href) ?>" placeholder="https://... หรือ tel:...">
              </label>
            <?php else: ?>
              <label class="nav-inline-edit__field nav-inline-edit__field--grow">
                <span class="nav-inline-edit__label">URL</span>
                <input type="text" name="href" class="admin-input" value="<?= admin_h($href) ?>" required placeholder="contact.html, tel:..., mailto:... หรือ https://...">
              </label>
            <?php endif; ?>
            <?php if ($section === 'topCta'): ?>
              <label class="nav-inline-edit__field">
                <span class="nav-inline-edit__label">สไตล์</span>
                <select name="variant" class="admin-input">
                  <option value="white"<?= $variant === 'white' ? ' selected' : '' ?>>ขาว</option>
                  <option value="outline"<?= $variant === 'outline' ? ' selected' : '' ?>>ขอบขาว</option>
                </select>
              </label>
            <?php endif; ?>
            <div class="nav-inline-edit__checks">
              <label class="admin-check">
                <input type="checkbox" name="visible" value="1"<?= $visible ? ' checked' : '' ?>>
                แสดงบนเว็บ
              </label>
              <?php if ($section === 'link' || $section === 'bottom'): ?>
                <label class="admin-check">
                  <input type="checkbox" name="external" value="1"<?= $external ? ' checked' : '' ?>>
                  เปิดแท็บใหม่
                </label>
              <?php endif; ?>
            </div>
          </div>
          <div class="nav-inline-edit__actions">
            <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">บันทึก</button>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-footer-cancel>ยกเลิก</button>
          </div>
          <?php endif; ?>
        </form>
      </td>
    </tr>
    <?php
}

/**
 * @param array{save?:string,toggle?:string,delete?:string,return?:string} $api
 */
function admin_inline_list_render_item_rows(
    string $section,
    int $col,
    int $index,
    array $item,
    array $api = [],
    bool $showPreview = true,
    bool $useSlug = false
): void {
    admin_inline_list_render_view_row($section, $col, $index, $item, $api, $showPreview, $useSlug);
    admin_inline_list_render_edit_row($section, $col, (string) $index, $item, $api, true);
}

function admin_inline_list_render_more_link_rows(int $col, array $more, array $api = []): void
{
    $api = array_merge([
        'save' => 'footer-save.php',
        'return' => 'site-footer.php',
    ], $api);

    $label = (string) ($more['label'] ?? '');
    $href = (string) ($more['href'] ?? '');
    $visible = admin_footer_link_visible($more);
    $slug = admin_footer_href_slug($href);
    ?>
    <tr
      class="nav-row nav-row--main footer-row footer-row--view footer-row--more"
      data-footer-view
      data-footer-section="moreLink"
      data-footer-col="<?= $col ?>"
      data-footer-index="more"
      data-search-text="<?= admin_h(strtolower($label . ' ' . $href . ' more')) ?>"
    >
      <td class="nav-col-order"><span class="nav-cell-empty" aria-hidden="true">—</span></td>
      <td class="nav-col-label">
        <button type="button" class="nav-inline-trigger" data-footer-edit>
          <strong><?= admin_h($label) ?></strong>
        </button>
        <span class="admin-badge admin-badge--muted">ลิงก์ท้ายคอลัมน์</span>
      </td>
      <td class="nav-col-href">
        <button type="button" class="nav-inline-trigger nav-inline-trigger--code" data-footer-edit>
          <code><?= admin_h($slug) ?></code>
        </button>
      </td>
      <td class="nav-col-status">
        <button type="button" class="nav-status-trigger" data-footer-edit>
          <?php if ($visible): ?>
            <span class="admin-badge admin-badge--ok">เผยแพร่</span>
          <?php else: ?>
            <span class="admin-badge admin-badge--muted">ซ่อน</span>
          <?php endif; ?>
        </button>
      </td>
      <td class="nav-col-actions"></td>
    </tr>
    <?php admin_inline_list_render_edit_row('moreLink', $col, 'more', $more, $api, true); ?>
    <?php
}
