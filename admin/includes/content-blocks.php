<?php
declare(strict_types=1);

require_once __DIR__ . '/landing-blocks.php';

function admin_content_sections_are_blocks(array $sections): bool
{
    foreach ($sections as $sec) {
        if (is_array($sec) && !empty($sec['type'])) {
            return true;
        }
    }

    return false;
}

/**
 * Convert a content item's detail sections into a single Rich Text HTML body.
 * Handles the legacy {heading, paragraphs[], list[]} shape and falls back to a
 * basic conversion for block-format sections. Output uses only Quill-friendly tags.
 */
function admin_content_sections_to_richtext_html(array $sections): string
{
    $html = '';

    foreach ($sections as $sec) {
        if (!is_array($sec)) {
            continue;
        }
        if (isset($sec['visible']) && $sec['visible'] === false) {
            continue;
        }
        if (isset($sec['isVisible']) && $sec['isVisible'] === false) {
            continue;
        }

        if (!empty($sec['type'])) {
            $html .= admin_content_block_to_richtext_html($sec);
            continue;
        }

        $heading = trim((string) ($sec['heading'] ?? ''));
        if ($heading !== '') {
            $html .= '<h2>' . $heading . '</h2>';
        }
        foreach (($sec['paragraphs'] ?? []) as $p) {
            $p = trim((string) $p);
            if ($p !== '') {
                $html .= '<p>' . $p . '</p>';
            }
        }
        $list = $sec['list'] ?? [];
        if (is_array($list) && $list !== []) {
            $html .= '<ul>';
            foreach ($list as $li) {
                $li = trim((string) $li);
                if ($li !== '') {
                    $html .= '<li>' . $li . '</li>';
                }
            }
            $html .= '</ul>';
        }
    }

    if (trim($html) === '') {
        $html = '<p></p>';
    }

    return $html;
}

/** Basic block-format section → Quill-friendly HTML (safety net for block content) */
function admin_content_block_to_richtext_html(array $sec): string
{
    $type = (string) ($sec['type'] ?? '');
    $html = '';

    $title = trim((string) ($sec['title'] ?? ''));
    $subtitle = trim((string) ($sec['subtitle'] ?? ''));
    $description = trim((string) ($sec['description'] ?? ''));
    $items = is_array($sec['items'] ?? null) ? $sec['items'] : [];

    if ($type === 'heading') {
        if ($title !== '') {
            $html .= '<h2>' . $title . '</h2>';
        }
        if ($subtitle !== '') {
            $html .= '<p>' . $subtitle . '</p>';
        }
        return $html;
    }

    if ($type === 'image') {
        $src = trim((string) ($sec['image']['src'] ?? ''));
        $alt = trim((string) ($sec['image']['alt'] ?? $title));
        if ($src !== '') {
            $html .= '<p><img src="' . $src . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '"></p>';
        }
        return $html;
    }

    if ($type === 'video') {
        $url = trim((string) ($sec['videoUrl'] ?? $sec['videoSrc'] ?? ''));
        if ($url !== '') {
            $html .= '<p><a href="' . htmlspecialchars($url, ENT_QUOTES) . '">' . htmlspecialchars($url, ENT_QUOTES) . '</a></p>';
        }
        return $html;
    }

    if ($type === 'customHtml') {
        return trim((string) ($sec['customHtml'] ?? ''));
    }

    // Generic blocks with a title/description + item list
    if ($title !== '') {
        $html .= '<h2>' . $title . '</h2>';
    }
    if ($subtitle !== '') {
        $html .= '<p>' . $subtitle . '</p>';
    }
    if ($description !== '') {
        $html .= '<p>' . $description . '</p>';
    }

    if ($items !== []) {
        if ($type === 'faq' || $type === 'infoBlocks') {
            foreach ($items as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $t = trim((string) ($it['title'] ?? ''));
                $d = trim((string) ($it['description'] ?? ''));
                if ($t !== '') {
                    $html .= '<h3>' . $t . '</h3>';
                }
                if ($d !== '') {
                    $html .= '<p>' . $d . '</p>';
                }
            }
        } else {
            $html .= '<ul>';
            foreach ($items as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $t = trim((string) ($it['title'] ?? ''));
                $d = trim((string) ($it['description'] ?? ''));
                $line = $t !== '' && $d !== '' ? ('<strong>' . $t . ':</strong> ' . $d) : ($t . $d);
                $line = trim($line);
                if ($line !== '') {
                    $html .= '<li>' . $line . '</li>';
                }
            }
            $html .= '</ul>';
        }
    }

    return $html;
}

/** Convert legacy {heading, paragraphs, list} → landing block sections */
function admin_content_legacy_to_sections(array $sections): array
{
    $blocks = [];

    foreach ($sections as $sec) {
        if (!is_array($sec)) {
            continue;
        }
        if (!empty($sec['type'])) {
            $blocks[] = admin_normalize_block_section($sec, count($blocks));
            continue;
        }

        $heading = trim((string) ($sec['heading'] ?? ''));
        if ($heading !== '') {
            $blocks[] = admin_normalize_block_section([
                'type' => 'heading',
                'title' => $heading,
            ], count($blocks));
        }

        foreach ($sec['paragraphs'] ?? [] as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph === '') {
                continue;
            }
            $blocks[] = admin_normalize_block_section([
                'type' => 'text',
                'description' => $paragraph,
            ], count($blocks));
        }

        $list = array_values(array_filter(array_map(
            static fn ($li): string => trim((string) $li),
            $sec['list'] ?? []
        )));
        if ($list !== []) {
            $items = [];
            foreach ($list as $i => $line) {
                $items[] = array_merge(admin_landing_default_block_item(), [
                    'title' => $line,
                    'description' => '',
                    'sortOrder' => $i,
                ]);
            }
            $blocks[] = admin_normalize_block_section([
                'type' => 'bulletList',
                'title' => '',
                'showIcon' => false,
                'items' => $items,
            ], count($blocks));
        }
    }

    if ($blocks === []) {
        $blocks[] = admin_landing_default_block('text');
    }

    return $blocks;
}

function admin_content_item_sections(array $item): array
{
    $sections = $item['sections'] ?? [];
    if (!is_array($sections) || $sections === []) {
        return [admin_landing_default_block('text')];
    }
    if (!admin_content_sections_are_blocks($sections)) {
        return admin_content_legacy_to_sections($sections);
    }

    return array_values(array_map(
        static fn (array $sec, int $i): array => admin_normalize_block_section($sec, $i),
        $sections,
        array_keys($sections)
    ));
}

function admin_content_item_to_page_data(array $item): array
{
    return [
        'sections' => admin_content_item_sections($item),
    ];
}

function admin_content_page_data_to_item(array $pageData, array $existing = []): array
{
    $sections = $pageData['sections'] ?? [];
    if (!is_array($sections)) {
        $sections = [];
    }

    $sections = array_values(array_map(
        static fn (array $sec, int $i): array => admin_normalize_block_section($sec, $i),
        $sections,
        array_keys($sections)
    ));

    return array_merge($existing, [
        'sections' => $sections,
    ]);
}

function admin_content_visual_boot(string $type, string $slug, array $item, string $csrf): array
{
    $types = admin_content_types();
    $cfg = $types[$type] ?? null;
    $listMap = [
        'articles' => ['label' => 'บทความ', 'url' => '../products.html', 'previewDir' => 'articles'],
        'news' => ['label' => 'ข่าว/กิจกรรม', 'url' => '../news.html', 'previewDir' => 'news'],
        'careers' => ['label' => 'แนะนำอาชีพ', 'url' => '../career.html', 'previewDir' => 'careers'],
        'claims' => ['label' => 'รีวิวเคลม', 'url' => '../claim-reviews.html', 'previewDir' => null],
    ];
    $listInfo = $listMap[$type] ?? $listMap['articles'];
    $previewFile = $listInfo['previewDir']
        ? $listInfo['previewDir'] . '/' . $slug . '.html'
        : '../claim-reviews.html';

    return [
        'editorKind' => 'content',
        'contentType' => $type,
        'slug' => $slug,
        'page' => $slug,
        'csrf' => $csrf,
        'pageData' => admin_content_item_to_page_data($item),
        'item' => $item,
        'coverSpec' => $cfg['coverSpec'] ?? 'article_cover',
        'meta' => [
            'label' => strip_tags((string) ($item['title'] ?? $slug)),
            'file' => $previewFile,
        ],
        'listLabel' => $listInfo['label'],
        'listUrl' => $listInfo['url'],
        'agent' => [],
        'brand' => json_read('site.json')['brand'] ?? [],
        'sectionCatalog' => admin_landing_block_catalog(),
        'previewUrl' => '../' . ltrim($previewFile, '/'),
    ];
}

function admin_content_card_preview_markup(array $item, string $type, string $slug): string
{
    $title = (string) ($item['title'] ?? 'หัวข้อ');
    $desc = (string) ($item['description'] ?? 'คำอธิบายสั้น');
    $category = (string) ($item['category'] ?? '');
    $image = (string) ($item['image'] ?? 'images/cover%20cart/istockphoto-1350164916-612x612.jpg');
    $views = (int) ($item['views'] ?? 0);
    $shares = (int) ($item['shares'] ?? 0);

    $dirMap = [
        'articles' => 'articles',
        'news' => 'news',
        'careers' => 'careers',
    ];
    $hrefDir = $dirMap[$type] ?? 'articles';
    $href = $hrefDir . '/' . ($slug !== '' ? $slug : 'example') . '.html';

    $imgSrc = admin_h('../' . ltrim($image, '/'));
    $stats = '';
    if ($views > 0) {
        $stats = '<p class="product-card-stats" data-preview-stats>'
            . number_format($views, 0, '.', ',') . ' views';
        if ($shares > 0) {
            $stats .= ' · ' . $shares . ' shares';
        }
        $stats .= '</p>';
    }

    return '<article class="product-card" data-content-card-preview>'
        . '<span class="product-card-media" tabindex="-1" aria-hidden="true" data-preview-media>'
        . '<img src="' . $imgSrc . '" alt="' . admin_h($title) . '" data-preview-image loading="lazy" decoding="async">'
        . '</span>'
        . '<div class="product-card-body">'
        . '<p class="product-card-meta" data-preview-category>' . admin_h($category !== '' ? $category : 'รีวิวเคลม') . '</p>'
        . '<h3 data-preview-title>' . admin_h($title) . '</h3>'
        . '<p class="product-card-excerpt" data-preview-desc>' . admin_h($desc) . '</p>'
        . $stats
        . '<span class="product-card-link" data-preview-link>อ่านต่อ →</span>'
        . '</div></article>';
}
