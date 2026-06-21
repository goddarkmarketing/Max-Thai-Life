<?php
declare(strict_types=1);

function admin_lucide_name(string $key): string
{
    static $map = [
        'grid' => 'layout-grid',
        'settings' => 'settings',
        'home' => 'house',
        'file' => 'file-text',
        'shield' => 'shield-check',
        'article' => 'file-text',
        'news' => 'newspaper',
        'users' => 'users',
        'heart' => 'heart',
        'image' => 'image',
        'backup' => 'download',
        'user' => 'user',
        'mail' => 'mail',
        'menu' => 'menu',
        'layout' => 'panel-left',
        'globe' => 'globe',
    ];

    return $map[$key] ?? $key;
}

function admin_lucide_icon(string $icon, int $size = 20): string
{
    $name = admin_lucide_name($icon);

    return sprintf(
        '<i data-lucide="%s" class="lucide-icon" style="width:%dpx;height:%dpx" aria-hidden="true"></i>',
        admin_h($name),
        $size,
        $size
    );
}

function admin_nav_icon_svg(string $icon): string
{
    return admin_lucide_icon($icon);
}

function admin_lucide_scripts(): void
{
    ?>
  <script src="../js/vendor/lucide.min.js"></script>
  <script src="../js/lucide-helper.js"></script>
    <?php
}
