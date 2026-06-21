<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function admin_seo_base_url(array $meta): string
{
    return rtrim(trim($meta['siteUrl'] ?? ''), '/');
}

function admin_seo_url(string $base, string $path): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if ($path === '' || $path === 'index.html') {
        return $base . '/';
    }
    return $base . '/' . $path;
}

function admin_seo_lastmod(string $path): string
{
    $full = ROOT_PATH . '/' . ltrim($path, '/');
    if (is_file($full)) {
        $ts = filemtime($full);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
    }
    return date('Y-m-d');
}

function admin_seo_collect_urls(array $meta): array
{
    $base = admin_seo_base_url($meta);
    if ($base === '') {
        return [];
    }

    $urls = [];

    foreach ($meta['pages'] ?? [] as $slug => $page) {
        if (empty($page['indexable'])) {
            continue;
        }
        $priority = $slug === 'index.html' ? '1.0' : '0.8';
        $urls[] = [
            'loc' => admin_seo_url($base, $slug),
            'lastmod' => admin_seo_lastmod($slug),
            'changefreq' => $slug === 'index.html' ? 'weekly' : 'monthly',
            'priority' => $priority,
        ];
    }

    $plans = admin_filter_visible_list(json_read('plans.json')['items'] ?? []);
    foreach ($plans as $plan) {
        $href = (string) ($plan['href'] ?? '');
        if ($href === '') {
            continue;
        }
        $urls[] = [
            'loc' => admin_seo_url($base, $href),
            'lastmod' => admin_seo_lastmod($href),
            'changefreq' => 'monthly',
            'priority' => '0.7',
        ];
    }

    $contentSets = [
        ['file' => 'articles.json', 'prefix' => 'articles/'],
        ['file' => 'news.json', 'prefix' => 'news/'],
        ['file' => 'careers.json', 'prefix' => 'careers/'],
    ];

    foreach ($contentSets as $set) {
        $data = json_read($set['file']);
        $items = admin_filter_visible_map($data['items'] ?? []);
        foreach ($items as $slug => $item) {
            $path = $set['prefix'] . $slug . '.html';
            $lastmod = (string) ($item['dateModified'] ?? $item['datePublished'] ?? '');
            if ($lastmod !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $lastmod)) {
                $lastmod = substr($lastmod, 0, 10);
            } else {
                $lastmod = admin_seo_lastmod($path);
            }
            $urls[] = [
                'loc' => admin_seo_url($base, $path),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }
    }

    return $urls;
}

function generate_sitemap_xml(): bool
{
    $site = json_read('site.json');
    $meta = admin_normalize_meta($site['meta'] ?? [], $site['brand'] ?? []);
    $urls = admin_seo_collect_urls($meta);
    if ($urls === []) {
        return false;
    }

    $lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ];

    foreach ($urls as $url) {
        $lines[] = '  <url>';
        $lines[] = '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
        $lines[] = '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</lastmod>';
        $lines[] = '    <changefreq>' . htmlspecialchars($url['changefreq'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</changefreq>';
        $lines[] = '    <priority>' . htmlspecialchars($url['priority'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</priority>';
        $lines[] = '  </url>';
    }

    $lines[] = '</urlset>';
    file_put_contents(ROOT_PATH . '/sitemap.xml', implode("\n", $lines) . "\n", LOCK_EX);
    return true;
}

function generate_robots_txt(): bool
{
    $site = json_read('site.json');
    $meta = admin_normalize_meta($site['meta'] ?? [], $site['brand'] ?? []);
    $base = admin_seo_base_url($meta);

    $lines = [
        'User-agent: *',
        'Allow: /',
        '',
    ];

    if ($base !== '') {
        $lines[] = 'Sitemap: ' . $base . '/sitemap.xml';
        $lines[] = '';
    }

    file_put_contents(ROOT_PATH . '/robots.txt', implode("\n", $lines), LOCK_EX);
    return true;
}

function generate_seo_files(): array
{
    return [
        'sitemap' => generate_sitemap_xml(),
        'robots' => generate_robots_txt(),
    ];
}
