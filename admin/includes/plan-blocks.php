<?php
declare(strict_types=1);

require_once __DIR__ . '/landing-blocks.php';

function admin_plan_clean_text(string $text): string
{
    $text = trim(strip_tags($text));
    if (function_exists('iconv')) {
        $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($fixed !== false) {
            $text = $fixed;
        }
    }

    return trim($text);
}

function admin_plan_default_cta_buttons(): array
{
    return [
        ['label' => 'ขอใบเสนอเบี้ย', 'href' => '../contact.html', 'variant' => 'white'],
        ['label' => 'โทร 085-292-5320', 'href' => 'tel:0852925320', 'variant' => 'outline'],
    ];
}

/** Card thumbnail from plans.json */
function admin_plan_card_image_for_slug(string $slug): string
{
    $plans = json_read('plans.json');
    foreach ($plans['items'] ?? [] as $item) {
        $href = (string) ($item['href'] ?? '');
        $itemSlug = preg_replace('#^plans/|\.html$#', '', $href);
        if ($itemSlug === $slug && !empty($item['image'])) {
            return (string) $item['image'];
        }
    }

    return '';
}

function admin_plan_first_image_src(array $detail): string
{
    foreach ($detail['sections'] ?? [] as $sec) {
        if (!is_array($sec) || ($sec['type'] ?? '') !== 'image') {
            continue;
        }
        if (isset($sec['isVisible']) && !$sec['isVisible']) {
            continue;
        }
        $src = trim((string) ($sec['image']['src'] ?? ''));
        if ($src !== '') {
            return $src;
        }
    }

    $legacy = $detail['image'] ?? '';
    return is_string($legacy) ? trim($legacy) : '';
}

/** Align first cover image block with plan card thumbnail */
function admin_plan_sync_card_image_to_sections(array $detail, string $cardImage): array
{
    if ($cardImage === '' || empty($detail['sections']) || !is_array($detail['sections'])) {
        return $detail;
    }

    foreach ($detail['sections'] as &$sec) {
        if (!is_array($sec) || ($sec['type'] ?? '') !== 'image') {
            continue;
        }
        if (isset($sec['isVisible']) && !$sec['isVisible']) {
            continue;
        }
        if (!isset($sec['image']) || !is_array($sec['image'])) {
            $sec['image'] = ['src' => '', 'alt' => ''];
        }
        $sec['image']['src'] = $cardImage;
        if (empty($sec['image']['alt'])) {
            $sec['image']['alt'] = strip_tags((string) ($detail['title'] ?? ''));
        }
        break;
    }
    unset($sec);

    $detail['image'] = $cardImage;

    return $detail;
}

/** Keep plans.json card thumbnail in sync with detail cover */
function admin_plan_sync_plans_json_card_image(string $slug, string $imageSrc): void
{
    if ($slug === '' || $imageSrc === '') {
        return;
    }

    $plans = json_read('plans.json');
    $updated = false;
    foreach ($plans['items'] ?? [] as &$item) {
        $href = (string) ($item['href'] ?? '');
        $itemSlug = preg_replace('#^plans/|\.html$#', '', $href);
        if ($itemSlug === $slug) {
            $item['image'] = $imageSrc;
            $updated = true;
            break;
        }
    }
    unset($item);

    if ($updated) {
        json_write('plans.json', $plans);
    }
}

/** Convert legacy flat plan detail → block editor pageData */
function admin_plan_detail_to_page_data(array $detail, ?array $card = null, string $slug = ''): array
{
    $cardImage = (string) ($card['image'] ?? '');
    if ($cardImage === '' && $slug !== '') {
        $cardImage = admin_plan_card_image_for_slug($slug);
    }

    $sections = $detail['sections'] ?? null;
    if (!is_array($sections) || $sections === []) {
        $sections = admin_plan_legacy_to_sections($detail, $cardImage);
    } else {
        $sections = array_values(array_map(
            static fn (array $sec, int $i): array => admin_normalize_block_section($sec, $i),
            $sections,
            array_keys($sections)
        ));
    }

    $sections = admin_plan_sanitize_sections($sections);
    $sections = admin_plan_upgrade_sections($sections);

    $title = (string) ($detail['title'] ?? $card['title'] ?? '');
    if ($cardImage !== '') {
        $synced = admin_plan_sync_card_image_to_sections([
            'sections' => $sections,
            'title' => $title,
        ], $cardImage);
        $sections = $synced['sections'];
    }
    $lead = (string) ($detail['heroLead'] ?? $detail['description'] ?? $card['desc'] ?? '');
    $ctaButtons = $detail['ctaButtons'] ?? null;
    if (!is_array($ctaButtons) || $ctaButtons === []) {
        $ctaButtons = admin_plan_default_cta_buttons();
    }

    return [
        'hero' => [
            'title' => $title,
            'lead' => $lead,
            'breadcrumb' => (string) ($detail['breadcrumb'] ?? strip_tags($title)),
            'isVisible' => true,
            'visible' => true,
        ],
        'sections' => $sections,
        'cta' => [
            'title' => (string) ($detail['ctaTitle'] ?? 'สนใจแผนนี้?'),
            'lead' => (string) ($detail['ctaLead'] ?? ''),
            'isVisible' => true,
            'visible' => true,
            'buttons' => $ctaButtons,
        ],
        'disclaimer' => (string) ($detail['disclaimer'] ?? ''),
    ];
}

/** Upgrade legacy customHtml tables/lists → structured blocks */
function admin_plan_upgrade_section(array $sec): array
{
    $type = (string) ($sec['type'] ?? '');
    if ($type !== 'customHtml') {
        return $sec;
    }

    $html = trim((string) ($sec['customHtml'] ?? ''));
    if ($html === '') {
        return $sec;
    }

    if (preg_match('/<table[^>]*class="[^"]*plan-spec-table[^"]*"[^>]*>(.*)<\/table>/is', $html, $tableMatch)) {
        preg_match_all('/<tr>\s*<th[^>]*>(.*?)<\/th>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is', $tableMatch[1], $rows, PREG_SET_ORDER);
        if ($rows !== []) {
            $items = [];
            foreach ($rows as $row) {
                $label = admin_plan_clean_text((string) $row[1]);
                $value = admin_plan_clean_text((string) $row[2]);
                if ($label === '' && $value === '') {
                    continue;
                }
                $items[] = array_merge(admin_landing_default_block_item(), [
                    'title' => $label,
                    'description' => $value,
                ]);
            }
            if ($items !== []) {
                $sec['type'] = 'specTable';
                $sec['items'] = $items;
                $sec['showIcon'] = false;
                $sec['customHtml'] = '';
            }
        }

        return $sec;
    }

    if (preg_match('/<ul[^>]*>(.*)<\/ul>/is', $html, $listMatch)) {
        preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $listMatch[1], $lis, PREG_SET_ORDER);
        if ($lis !== []) {
            $items = [];
            foreach ($lis as $li) {
                $inner = trim((string) $li[1]);
                if ($inner === '') {
                    continue;
                }
                $title = '';
                $description = '';
                if (preg_match('/<strong[^>]*>(.*?)<\/strong>\s*(?:[—–\-]\s*)?(.*)/is', $inner, $parts)) {
                    $title = admin_plan_clean_text((string) $parts[1]);
                    $description = admin_plan_clean_text((string) $parts[2]);
                } else {
                    $description = admin_plan_clean_text($inner);
                }
                if ($title === '' && $description === '') {
                    continue;
                }
                $items[] = array_merge(admin_landing_default_block_item(), [
                    'title' => $title,
                    'description' => $description,
                ]);
            }
            if ($items !== []) {
                $sec['type'] = 'bulletList';
                $sec['items'] = $items;
                $sec['showIcon'] = false;
                $sec['customHtml'] = '';
            }
        }
    }

    return $sec;
}

function admin_plan_upgrade_sections(array $sections): array
{
    return array_values(array_map(
        static fn (array $sec, int $i): array => admin_normalize_block_section(admin_plan_upgrade_section($sec), $i),
        $sections,
        array_keys($sections)
    ));
}

/** Clean placeholder values from migrated / default blocks */
function admin_plan_sanitize_sections(array $sections): array
{
    return array_map(static function (array $sec): array {
        if (($sec['type'] ?? '') === 'heading' && ($sec['subtitle'] ?? '') === 'คำอธิบายย่อย') {
            $sec['subtitle'] = '';
        }
        if (($sec['type'] ?? '') === 'image' || ($sec['type'] ?? '') === 'gallery') {
            $sec['showIcon'] = false;
        }
        return $sec;
    }, $sections);
}

/** Merge block editor pageData back into plan detail for JSON storage */
function admin_plan_page_data_to_detail(array $pageData, array $existing = []): array
{
    $hero = $pageData['hero'] ?? [];
    $cta = $pageData['cta'] ?? [];
    $sections = $pageData['sections'] ?? [];

    if (is_array($sections)) {
        $sections = admin_plan_sanitize_sections(array_values(array_map(
            static fn (array $sec, int $i): array => admin_normalize_block_section($sec, $i),
            $sections,
            array_keys($sections)
        )));
        $sections = admin_plan_upgrade_sections($sections);
    } else {
        $sections = [];
    }

    $title = (string) ($hero['title'] ?? $existing['title'] ?? '');
    $lead = (string) ($hero['lead'] ?? $hero['description'] ?? $existing['heroLead'] ?? '');

    $detail = array_merge($existing, [
        'title' => $title,
        'breadcrumb' => (string) ($hero['breadcrumb'] ?? $existing['breadcrumb'] ?? strip_tags($title)),
        'heroLead' => $lead,
        'description' => $lead !== '' ? $lead : (string) ($existing['description'] ?? ''),
        'ctaTitle' => (string) ($cta['title'] ?? $existing['ctaTitle'] ?? ''),
        'ctaLead' => (string) ($cta['lead'] ?? $cta['description'] ?? $existing['ctaLead'] ?? ''),
        'ctaButtons' => !empty($cta['buttons']) && is_array($cta['buttons'])
            ? $cta['buttons']
            : ($existing['ctaButtons'] ?? admin_plan_default_cta_buttons()),
        'disclaimer' => (string) ($pageData['disclaimer'] ?? $existing['disclaimer'] ?? ''),
        'sections' => $sections,
    ]);

    // Cover image follows first image block
    $firstImage = admin_plan_first_image_src(['sections' => $sections]);
    if ($firstImage !== '') {
        $detail['image'] = $firstImage;
    }

    return $detail;
}

function admin_plan_legacy_to_sections(array $detail, string $cardImage = ''): array
{
    $sections = [];
    $i = 0;

    $imagePath = $cardImage;
    if ($imagePath === '') {
        $imagePath = $detail['image'] ?? '';
        if (is_array($imagePath)) {
            $imagePath = (string) ($imagePath['src'] ?? '');
        }
    }

    if ($imagePath !== '') {
        $sections[] = admin_normalize_block_section([
            'type' => 'image',
            'title' => '',
            'subtitle' => '',
            'showIcon' => false,
            'image' => ['src' => $imagePath, 'alt' => strip_tags((string) ($detail['title'] ?? ''))],
        ], $i++);
    }

    foreach ($detail['overviewBlocks'] ?? [] as $block) {
        if (!is_array($block)) {
            continue;
        }
        if (($block['type'] ?? '') === 'text' && !empty($block['html'])) {
            $sections[] = admin_normalize_block_section([
                'type' => 'text',
                'description' => (string) $block['html'],
                'showIcon' => false,
            ], $i++);
        } elseif (($block['type'] ?? '') === 'image' && !empty($block['src'])) {
            $sections[] = admin_normalize_block_section([
                'type' => 'image',
                'showIcon' => false,
                'image' => ['src' => (string) $block['src'], 'alt' => ''],
            ], $i++);
        }
    }

    if (!empty($detail['overview']) || !empty($detail['highlight'])) {
        $sections[] = admin_normalize_block_section([
            'type' => 'heading',
            'title' => 'ภาพรวมแผน',
            'subtitle' => '',
            'anchor' => 'overview',
            'showIcon' => false,
        ], $i++);

        $body = (string) ($detail['overview'] ?? '');
        if (!empty($detail['highlight'])) {
            $body .= '<div class="plan-highlight-box"><strong>จุดขายหลัก:</strong> ' . $detail['highlight'] . '</div>';
        }
        $sections[] = admin_normalize_block_section([
            'type' => 'text',
            'description' => $body,
            'showIcon' => false,
        ], $i++);
    }

    if (!empty($detail['benefits']) && is_array($detail['benefits'])) {
        $sections[] = admin_normalize_block_section([
            'type' => 'heading',
            'title' => 'จุดเด่นและผลประโยชน์',
            'subtitle' => '',
            'anchor' => 'benefits',
            'showIcon' => false,
        ], $i++);

        $sections[] = admin_normalize_block_section([
            'type' => 'bulletList',
            'items' => array_map(static function ($b): array {
                $raw = (string) $b;
                $title = '';
                $description = $raw;
                if (preg_match('/<strong[^>]*>(.*?)<\/strong>\s*(?:[—–\-]\s*)?(.*)/is', $raw, $parts)) {
                    $title = admin_plan_clean_text((string) $parts[1]);
                    $description = admin_plan_clean_text((string) $parts[2]);
                } else {
                    $description = admin_plan_clean_text($raw);
                }

                return array_merge(admin_landing_default_block_item(), [
                    'title' => $title,
                    'description' => $description,
                ]);
            }, $detail['benefits']),
            'showIcon' => false,
        ], $i++);
    }

    if (!empty($detail['specs']) && is_array($detail['specs'])) {
        $sections[] = admin_normalize_block_section([
            'type' => 'heading',
            'title' => 'ข้อมูลแผน (ภาพรวม)',
            'subtitle' => '',
            'anchor' => 'specs',
            'showIcon' => false,
        ], $i++);

        $items = array_map(
            static fn ($row): array => array_merge(admin_landing_default_block_item(), [
                'title' => is_array($row) ? (string) ($row[0] ?? '') : '',
                'description' => is_array($row) ? (string) ($row[1] ?? '') : '',
            ]),
            $detail['specs']
        );
        $sections[] = admin_normalize_block_section([
            'type' => 'specTable',
            'items' => $items,
            'showIcon' => false,
        ], $i++);
    }

    if (!empty($detail['whoBlocks']) && is_array($detail['whoBlocks'])) {
        $items = array_map(static function ($block): array {
            return array_merge(admin_landing_default_block_item(), [
                'title' => (string) ($block['title'] ?? ''),
                'description' => (string) ($block['text'] ?? $block['description'] ?? ''),
            ]);
        }, $detail['whoBlocks']);

        $sections[] = admin_normalize_block_section([
            'type' => 'infoBlocks',
            'title' => 'เหมาะกับใคร',
            'anchor' => 'who',
            'showIcon' => false,
            'items' => $items,
        ], $i++);
    }

    if (!empty($detail['faq']) && is_array($detail['faq'])) {
        $items = array_map(static function ($item): array {
            return array_merge(admin_landing_default_block_item(), [
                'title' => (string) ($item['q'] ?? $item['title'] ?? ''),
                'description' => (string) ($item['a'] ?? $item['description'] ?? ''),
            ]);
        }, $detail['faq']);

        $sections[] = admin_normalize_block_section([
            'type' => 'faq',
            'title' => 'คำถามที่พบบ่อย',
            'anchor' => 'faq',
            'showIcon' => false,
            'items' => $items,
        ], $i++);
    }

    if ($sections === []) {
        $sections[] = admin_normalize_block_section([
            'type' => 'text',
            'description' => 'เพิ่มเนื้อหาแผนประกันที่นี่',
            'showIcon' => false,
        ], 0);
    }

    return $sections;
}

/**
 * Convert a plan detail (structured fields) into a single Rich Text HTML body.
 * Output uses only Quill-supported tags (h2/h3, p, ul/li, blockquote, strong/em)
 * so it survives loading into the editor without distortion.
 */
function admin_plan_detail_to_richtext_html(array $detail): string
{
    $html = '';

    $overview = trim((string) ($detail['overview'] ?? ''));
    $highlight = trim((string) ($detail['highlight'] ?? ''));
    if ($overview !== '' || $highlight !== '') {
        $html .= '<h2>ภาพรวมแผน</h2>';
        if ($overview !== '') {
            $html .= '<p>' . $overview . '</p>';
        }
        if ($highlight !== '') {
            $html .= '<blockquote><strong>จุดขายหลัก:</strong> ' . $highlight . '</blockquote>';
        }
    }

    $benefits = $detail['benefits'] ?? [];
    if (is_array($benefits) && $benefits !== []) {
        $html .= '<h2>จุดเด่นและผลประโยชน์</h2><ul>';
        foreach ($benefits as $b) {
            $b = trim((string) $b);
            if ($b === '') {
                continue;
            }
            $html .= '<li>' . $b . '</li>';
        }
        $html .= '</ul>';
    }

    $specs = $detail['specs'] ?? [];
    if (is_array($specs) && $specs !== []) {
        $html .= '<h2>ข้อมูลแผน (ภาพรวม)</h2><ul>';
        foreach ($specs as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = trim((string) ($row[0] ?? ''));
            $value = trim((string) ($row[1] ?? ''));
            if ($label === '' && $value === '') {
                continue;
            }
            $html .= '<li><strong>' . $label . ':</strong> ' . $value . '</li>';
        }
        $html .= '</ul>';
    }

    $whoBlocks = $detail['whoBlocks'] ?? [];
    $whoText = trim((string) ($detail['whoText'] ?? ''));
    if ((is_array($whoBlocks) && $whoBlocks !== []) || $whoText !== '') {
        $html .= '<h2>เหมาะกับใคร</h2>';
        if (is_array($whoBlocks) && $whoBlocks !== []) {
            foreach ($whoBlocks as $blk) {
                if (!is_array($blk)) {
                    continue;
                }
                $t = trim((string) ($blk['title'] ?? ''));
                $x = trim((string) ($blk['text'] ?? $blk['description'] ?? ''));
                if ($t !== '') {
                    $html .= '<h3>' . $t . '</h3>';
                }
                if ($x !== '') {
                    $html .= '<p>' . $x . '</p>';
                }
            }
        } else {
            $html .= '<p>' . $whoText . '</p>';
        }
    }

    $faq = $detail['faq'] ?? [];
    if (is_array($faq) && $faq !== []) {
        $html .= '<h2>คำถามที่พบบ่อย</h2>';
        foreach ($faq as $item) {
            if (!is_array($item)) {
                continue;
            }
            $q = trim((string) ($item['q'] ?? $item['title'] ?? ''));
            $a = trim((string) ($item['a'] ?? $item['description'] ?? ''));
            if ($q !== '') {
                $html .= '<h3>' . $q . '</h3>';
            }
            if ($a !== '') {
                $html .= '<p>' . $a . '</p>';
            }
        }
    }

    $disclaimer = trim((string) ($detail['disclaimer'] ?? ''));
    if ($disclaimer !== '') {
        $html .= '<p><em>' . $disclaimer . '</em></p>';
    }

    if (trim($html) === '') {
        $html = '<p></p>';
    }

    return $html;
}

function admin_plan_visual_boot(string $slug, array $detail, ?array $card, string $csrf): array
{
    $pageData = admin_plan_detail_to_page_data($detail, $card, $slug);
    $previewFile = 'plans/' . $slug . '.html';
    if (is_array($card) && !empty($card['href'])) {
        $previewFile = ltrim((string) $card['href'], '/');
    }

    return [
        'editorKind' => 'plan',
        'slug' => $slug,
        'page' => $slug,
        'csrf' => $csrf,
        'pageData' => $pageData,
        'meta' => [
            'label' => strip_tags((string) ($detail['title'] ?? $slug)),
            'breadcrumb' => (string) ($detail['breadcrumb'] ?? strip_tags((string) ($detail['title'] ?? $slug))),
            'file' => $previewFile,
        ],
        'agent' => [],
        'brand' => json_read('site.json')['brand'] ?? [],
        'sectionCatalog' => admin_landing_block_catalog(),
        'card' => $card,
        'previewUrl' => '../' . $previewFile,
    ];
}
