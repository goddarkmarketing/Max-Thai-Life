<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/generate-seo.php';

function admin_share_plain_text(string $html): string
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
}

function admin_share_truncate(string $text, int $max = 200): string
{
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $max - 1)) . '…';
}

function admin_share_encode_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $parts = explode('/', $path);
    $encoded = [];
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        try {
            $part = rawurldecode($part);
        } catch (Throwable $e) {
        }
        $encoded[] = rawurlencode($part);
    }
    return implode('/', $encoded);
}

function admin_share_absolute_asset(string $baseUrl, string $assetPath): string
{
    $assetPath = ltrim(str_replace('\\', '/', $assetPath), '/');
    if ($assetPath === '') {
        return '';
    }
    $encoded = admin_share_encode_path($assetPath);
    if ($baseUrl !== '') {
        return admin_seo_url($baseUrl, $encoded);
    }
    return '/' . $encoded;
}

function admin_share_normalize_asset_path(string $assetPath): string
{
    $assetPath = ltrim(str_replace('\\', '/', $assetPath), '/');
    if ($assetPath === '') {
        return '';
    }

    $candidates = [$assetPath];
    if (preg_match('#^images/cover[\s_%]?(?:แผนประกัน|cart)/#u', $assetPath)) {
        $basename = basename($assetPath);
        $candidates[] = 'images/plan-covers/' . $basename;
        if (preg_match('#^images/cover[\s_%]?(?:แผนประกัน|cart)/(.+)$#u', $assetPath, $m)) {
            $candidates[] = 'images/plan-covers/' . $m[1];
        }
    }

    foreach ($candidates as $candidate) {
        $full = ROOT_PATH . '/' . $candidate;
        if (is_file($full)) {
            return $candidate;
        }
    }

    return $assetPath;
}

function admin_share_url_is_reachable(string $url): bool
{
    static $cache = [];

    if ($url === '') {
        return false;
    }
    if (isset($cache[$url])) {
        return $cache[$url];
    }

    $ok = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_USERAGENT => 'MaxThaiLifeShareMeta/1.0',
            ]);
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $ok = $code >= 200 && $code < 400;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 4,
                'follow_location' => 1,
                'max_redirects' => 3,
                'user_agent' => 'MaxThaiLifeShareMeta/1.0',
            ],
        ]);
        $headers = @get_headers($url, true, $context);
        if (is_array($headers)) {
            $status = (string) ($headers[0] ?? '');
            $ok = str_contains($status, '200') || str_contains($status, '301') || str_contains($status, '302');
        }
    }

    $cache[$url] = $ok;
    return $ok;
}

function admin_share_resolve_asset_url(array $meta, string $assetPath): string
{
    $assetPath = admin_share_normalize_asset_path($assetPath);
    if ($assetPath === '') {
        return '';
    }

    $bases = [];
    $primary = admin_seo_share_asset_base_url($meta);
    $fallback = admin_seo_share_asset_fallback_url($meta);
    if ($primary !== '') {
        $bases[] = $primary;
    }
    if ($fallback !== '' && $fallback !== $primary) {
        $bases[] = $fallback;
    }

    foreach ($bases as $base) {
        $url = admin_share_absolute_asset($base, $assetPath);
        if (admin_share_url_is_reachable($url)) {
            return $url;
        }
    }

    $base = $primary !== '' ? $primary : $fallback;
    return $base !== '' ? admin_share_absolute_asset($base, $assetPath) : '';
}

function admin_share_meta_html(array $opts): string
{
    $title = htmlspecialchars((string) ($opts['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = htmlspecialchars(
        admin_share_truncate((string) ($opts['description'] ?? '')),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $ogType = htmlspecialchars((string) ($opts['ogType'] ?? 'website'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $ogTitle = htmlspecialchars((string) ($opts['ogTitle'] ?? $opts['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $ogDescription = htmlspecialchars(
        admin_share_truncate((string) ($opts['ogDescription'] ?? $opts['description'] ?? '')),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $ogImage = htmlspecialchars((string) ($opts['ogImage'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $ogUrl = htmlspecialchars((string) ($opts['ogUrl'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $siteName = htmlspecialchars((string) ($opts['siteName'] ?? 'Max Thai Life'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $indexable = ($opts['indexable'] ?? true) !== false;
    $twitterCard = $ogImage !== '' ? 'summary_large_image' : 'summary';

    $lines = [];
    if ($description !== '') {
        $lines[] = '  <meta name="description" content="' . $description . '">';
    }
    $lines[] = '  <title>' . $title . '</title>';
    if (!$indexable) {
        $lines[] = '  <meta name="robots" content="noindex, nofollow">';
    }
    if ($ogUrl !== '') {
        $lines[] = '  <link rel="canonical" href="' . $ogUrl . '">';
    }
    $lines[] = '  <meta property="og:type" content="' . $ogType . '">';
    $lines[] = '  <meta property="og:title" content="' . $ogTitle . '">';
    if ($ogDescription !== '') {
        $lines[] = '  <meta property="og:description" content="' . $ogDescription . '">';
    }
    if ($ogImage !== '') {
        $lines[] = '  <meta property="og:image" content="' . $ogImage . '">';
    }
    if ($ogUrl !== '') {
        $lines[] = '  <meta property="og:url" content="' . $ogUrl . '">';
    }
    $lines[] = '  <meta property="og:locale" content="th_TH">';
    $lines[] = '  <meta property="og:site_name" content="' . $siteName . '">';
    $lines[] = '  <meta name="twitter:card" content="' . $twitterCard . '">';
    $lines[] = '  <meta name="twitter:title" content="' . $ogTitle . '">';
    if ($ogDescription !== '') {
        $lines[] = '  <meta name="twitter:description" content="' . $ogDescription . '">';
    }
    if ($ogImage !== '') {
        $lines[] = '  <meta name="twitter:image" content="' . $ogImage . '">';
    }

    return implode("\n", $lines);
}

function admin_share_apply_head(string $html, array $meta): string
{
    $block = admin_share_meta_html($meta);

    $strip = [
        '/\s*<meta name="description"[^>]*>/i',
        '/\s*<meta name="robots"[^>]*>/i',
        '/\s*<meta property="og:[^"]+"[^>]*>/i',
        '/\s*<meta name="twitter:[^"]+"[^>]*>/i',
        '/\s*<link rel="canonical"[^>]*>/i',
        '/\s*<title>[^<]*<\/title>/i',
    ];
    foreach ($strip as $pattern) {
        $html = preg_replace($pattern, '', $html) ?? $html;
    }

    $replaced = preg_replace(
        '/(<meta name="viewport"[^>]*>)/i',
        '$1' . "\n" . $block,
        $html,
        1
    );

    return $replaced ?? $html;
}

function admin_share_write_page(string $relPath, array $meta): bool
{
    $full = ROOT_PATH . '/' . ltrim(str_replace('\\', '/', $relPath), '/');
    if (!is_file($full)) {
        return false;
    }
    $html = file_get_contents($full);
    if ($html === false) {
        return false;
    }
    $updated = admin_share_apply_head($html, $meta);
    if ($updated === $html) {
        return false;
    }
    file_put_contents($full, $updated, LOCK_EX);
    return true;
}

function admin_share_default_image(array $meta, array $brand): string
{
    $img = (string) ($meta['ogImage'] ?? '');
    if ($img === '') {
        $img = (string) ($brand['logo'] ?? 'images/logo/LOGO-THAILIFE.png');
    }
    return $img;
}

function admin_share_plan_description(array $detail): string
{
    $desc = admin_share_plain_text((string) ($detail['description'] ?? ''));
    if ($desc !== '') {
        return $desc;
    }
    return admin_share_plain_text((string) ($detail['heroLead'] ?? ''));
}

function admin_share_plan_image(string $slug, array $detail, array $imageMap, string $fallback): string
{
    if (!empty($imageMap[$slug])) {
        return (string) $imageMap[$slug];
    }
    if (!empty($detail['image'])) {
        return (string) $detail['image'];
    }
    return $fallback;
}

function generate_share_meta_all(): array
{
    $site = json_read('site.json');
    $brand = $site['brand'] ?? [];
    $meta = admin_normalize_meta($site['meta'] ?? [], $brand);
    $baseUrl = admin_seo_base_url($meta);
    $defaultImage = admin_share_default_image($meta, $brand);
    $siteName = (string) ($brand['name'] ?? 'Max Thai Life');
    $updated = 0;

    foreach ($meta['pages'] ?? [] as $slug => $page) {
        $title = (string) ($page['title'] ?? '');
        if ($title === '') {
            continue;
        }
        $description = (string) ($page['description'] ?? $meta['description'] ?? '');
        $path = $slug;
        if (admin_share_write_page($path, [
            'title' => $title,
            'description' => $description,
            'ogTitle' => $title,
            'ogDescription' => $description,
            'ogType' => 'website',
            'ogImage' => admin_share_resolve_asset_url($meta, $defaultImage),
            'ogUrl' => $baseUrl !== '' ? admin_seo_url($baseUrl, $path) : '',
            'siteName' => $siteName,
            'indexable' => ($page['indexable'] ?? true) !== false,
        ])) {
            $updated++;
        }
    }

    $plans = json_read('plans.json');
    $planItems = admin_filter_visible_list($plans['items'] ?? []);
    $plansDetail = json_read('plans-detail.json');
    $detailItems = $plansDetail['items'] ?? [];
    $imageMap = [];
    foreach ($planItems as $plan) {
        $href = (string) ($plan['href'] ?? '');
        $slug = preg_replace('#^plans/|\.html$#', '', $href);
        if ($slug && !empty($plan['image'])) {
            $imageMap[$slug] = (string) $plan['image'];
        }
    }

    foreach ($planItems as $plan) {
        $href = (string) ($plan['href'] ?? '');
        if ($href === '') {
            continue;
        }
        $slug = preg_replace('#^plans/|\.html$#', '', $href);
        $detail = is_array($detailItems[$slug] ?? null) ? $detailItems[$slug] : [];
        $title = admin_share_plain_text((string) ($detail['title'] ?? $plan['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $pageTitle = $title . ' | แผนประกัน';
        $description = admin_share_plan_description($detail);
        if ($description === '') {
            $description = admin_share_plain_text((string) ($plan['description'] ?? ''));
        }
        $image = admin_share_plan_image($slug, $detail, $imageMap, $defaultImage);
        if (admin_share_write_page($href, [
            'title' => $pageTitle,
            'description' => $description,
            'ogTitle' => $title,
            'ogDescription' => $description,
            'ogType' => 'website',
            'ogImage' => admin_share_resolve_asset_url($meta, $image),
            'ogUrl' => $baseUrl !== '' ? admin_seo_url($baseUrl, $href) : '',
            'siteName' => $siteName,
            'indexable' => true,
        ])) {
            $updated++;
        }
    }

    $contentSets = [
        ['file' => 'articles.json', 'prefix' => 'articles/', 'titleSuffix' => ' | บทความ | Max Thai Life', 'ogType' => 'article'],
        ['file' => 'news.json', 'prefix' => 'news/', 'titleSuffix' => ' | ข่าว/กิจกรรม | Max Thai Life', 'ogType' => 'article'],
        ['file' => 'careers.json', 'prefix' => 'careers/', 'titleSuffix' => ' | แนะนำอาชีพ | Max Thai Life', 'ogType' => 'article'],
    ];

    foreach ($contentSets as $set) {
        $data = json_read($set['file']);
        $items = admin_filter_visible_map($data['items'] ?? []);
        foreach ($items as $slug => $item) {
            $path = $set['prefix'] . $slug . '.html';
            $title = admin_share_plain_text((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $description = admin_share_plain_text((string) ($item['description'] ?? ''));
            $image = (string) ($item['image'] ?? $defaultImage);
            if (admin_share_write_page($path, [
                'title' => $title . $set['titleSuffix'],
                'description' => $description,
                'ogTitle' => $title,
                'ogDescription' => $description,
                'ogType' => $set['ogType'],
                'ogImage' => admin_share_resolve_asset_url($meta, $image),
                'ogUrl' => $baseUrl !== '' ? admin_seo_url($baseUrl, $path) : '',
                'siteName' => $siteName,
                'indexable' => true,
            ])) {
                $updated++;
            }
        }
    }

    return ['updated' => $updated, 'baseUrl' => $baseUrl];
}
