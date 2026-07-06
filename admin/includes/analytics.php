<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/generate-seo.php';

function admin_analytics_page_path(string $type, string $id): string
{
    return match ($type) {
        'articles' => 'articles/' . $id . '.html',
        'news' => 'news/' . $id . '.html',
        'careers' => 'careers/' . $id . '.html',
        'plans' => 'plans/' . $id . '.html',
        'site' => $id === 'index.html' ? 'index.html' : $id,
        default => '',
    };
}

function admin_analytics_page_exists(string $type, string $id): bool
{
    $path = admin_analytics_page_path($type, $id);
    if ($path === '') {
        return false;
    }
    return is_file(ROOT_PATH . '/' . $path);
}

function admin_analytics_page_url(string $type, string $id): string
{
    $path = admin_analytics_page_path($type, $id);
    if ($path === '') {
        return '#';
    }

    $site = json_read('site.json');
    $meta = admin_normalize_meta($site['meta'] ?? [], $site['brand'] ?? []);
    $base = admin_seo_base_url($meta);
    $webPath = $path === 'index.html' ? '' : $path;

    if ($base !== '') {
        return admin_seo_url($base, $webPath);
    }

    return match ($type) {
        'articles' => '../articles/' . $id . '.html',
        'news' => '../news/' . $id . '.html',
        'careers' => '../careers/' . $id . '.html',
        'plans' => '../plans/' . $id . '.html',
        'site' => '../' . $path,
        default => '#',
    };
}

function admin_analytics_default(): array
{
    return [
        'updatedAt' => date('c'),
        'totalViews' => 0,
        'devices' => [
            'mobile' => 0,
            'desktop' => 0,
            'tablet' => 0,
            'other' => 0,
        ],
        'pages' => [
            'articles' => [],
            'news' => [],
            'careers' => [],
            'plans' => [],
            'site' => [],
        ],
        'daily' => [],
    ];
}

function admin_analytics_empty_day(): array
{
    return [
        'totalViews' => 0,
        'devices' => [
            'mobile' => 0,
            'desktop' => 0,
            'tablet' => 0,
            'other' => 0,
        ],
        'pages' => [
            'articles' => [],
            'news' => [],
            'careers' => [],
            'plans' => [],
            'site' => [],
        ],
    ];
}

function admin_analytics_date_key(?int $timestamp = null): string
{
    return date('Y-m-d', $timestamp ?? time());
}

function admin_analytics_valid_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }
    $parts = explode('-', $value);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        return null;
    }
    return $value;
}

/**
 * @return array{mode: string, from: ?string, to: ?string, preset: string}
 */
function admin_analytics_resolve_range(string $preset, string $from, string $to): array
{
    $today = admin_analytics_date_key();

    if ($preset === 'today') {
        return ['mode' => 'range', 'from' => $today, 'to' => $today, 'preset' => 'today'];
    }
    if ($preset === '7d') {
        return ['mode' => 'range', 'from' => admin_analytics_date_key(strtotime('-6 days')), 'to' => $today, 'preset' => '7d'];
    }
    if ($preset === '30d') {
        return ['mode' => 'range', 'from' => admin_analytics_date_key(strtotime('-29 days')), 'to' => $today, 'preset' => '30d'];
    }

    $fromDate = admin_analytics_valid_date($from);
    $toDate = admin_analytics_valid_date($to);
    if ($fromDate !== null || $toDate !== null) {
        $fromDate = $fromDate ?? $toDate ?? $today;
        $toDate = $toDate ?? $fromDate ?? $today;
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        return ['mode' => 'range', 'from' => $fromDate, 'to' => $toDate, 'preset' => 'custom'];
    }

    return ['mode' => 'all', 'from' => null, 'to' => null, 'preset' => 'all'];
}

function admin_analytics_format_date_th(string $ymd): string
{
    $ts = strtotime($ymd . ' 12:00:00');
    if ($ts === false) {
        return $ymd;
    }
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return sprintf(
        '%d %s %d',
        (int) date('j', $ts),
        $months[(int) date('n', $ts)] ?? '',
        (int) date('Y', $ts)
    );
}

function admin_analytics_range_label(array $range): string
{
    if (($range['mode'] ?? '') !== 'range' || empty($range['from']) || empty($range['to'])) {
        return 'ทั้งหมด';
    }
    $from = admin_analytics_format_date_th((string) $range['from']);
    $to = admin_analytics_format_date_th((string) $range['to']);
    if ($range['from'] === $range['to']) {
        return $from;
    }
    return $from . ' – ' . $to;
}

function admin_analytics_filter_by_range(array $data, string $from, string $to): array
{
    $filtered = admin_analytics_default();
    $filtered['updatedAt'] = $data['updatedAt'] ?? date('c');
    $daily = is_array($data['daily'] ?? null) ? $data['daily'] : [];

    foreach ($daily as $dateKey => $day) {
        if (!is_string($dateKey) || $dateKey < $from || $dateKey > $to || !is_array($day)) {
            continue;
        }
        $filtered['totalViews'] += (int) ($day['totalViews'] ?? 0);
        foreach (array_keys($filtered['devices']) as $device) {
            $filtered['devices'][$device] += (int) ($day['devices'][$device] ?? 0);
        }
        foreach (array_keys($filtered['pages']) as $type) {
            $pages = is_array($day['pages'][$type] ?? null) ? $day['pages'][$type] : [];
            foreach ($pages as $id => $count) {
                $filtered['pages'][$type][(string) $id] = (int) ($filtered['pages'][$type][(string) $id] ?? 0) + (int) $count;
            }
        }
    }

    return $filtered;
}

function admin_analytics_read(): array
{
    $data = json_read('analytics.json');
    if ($data === []) {
        return admin_analytics_default();
    }
    $defaults = admin_analytics_default();
    $data['devices'] = array_merge($defaults['devices'], is_array($data['devices'] ?? null) ? $data['devices'] : []);
    $data['pages'] = array_merge($defaults['pages'], is_array($data['pages'] ?? null) ? $data['pages'] : []);
    foreach (array_keys($defaults['pages']) as $type) {
        if (!is_array($data['pages'][$type] ?? null)) {
            $data['pages'][$type] = [];
        }
    }
    if (!is_array($data['daily'] ?? null)) {
        $data['daily'] = [];
    }
    $data['totalViews'] = (int) ($data['totalViews'] ?? 0);
    return $data;
}

function admin_analytics_write(array $data): void
{
    $data['updatedAt'] = date('c');
    json_write('analytics.json', $data);
}

function admin_analytics_valid_type(string $type): bool
{
    return in_array($type, ['articles', 'news', 'careers', 'plans', 'site'], true);
}

function admin_analytics_sanitize_id(string $id): string
{
    $id = trim($id);
    if ($id === '' || strlen($id) > 120) {
        return '';
    }
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $id)) {
        return '';
    }
    return $id;
}

function admin_analytics_detect_device(string $userAgent): string
{
    $ua = strtolower($userAgent);
    if ($ua === '') {
        return 'other';
    }
    if (preg_match('/ipad|tablet|kindle|playbook|silk/', $ua)) {
        return 'tablet';
    }
    if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone|opera mini|iemobile/', $ua)) {
        return 'mobile';
    }
    return 'desktop';
}

function admin_analytics_dedup_path(): string
{
    return DATA_PATH . '/analytics-dedup.json';
}

function admin_analytics_should_count(string $type, string $id, string $ip): bool
{
    $window = 1800;
    $bucket = (int) floor(time() / $window);
    $key = hash('sha256', $type . '|' . $id . '|' . $ip . '|' . $bucket);

    $path = admin_analytics_dedup_path();
    $store = ['entries' => []];
    if (is_file($path)) {
        $raw = file_get_contents($path);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && is_array($decoded['entries'] ?? null)) {
            $store = $decoded;
        }
    }

    $now = time();
    $entries = [];
    foreach ($store['entries'] as $hash => $ts) {
        if ($now - (int) $ts < 86400) {
            $entries[$hash] = (int) $ts;
        }
    }

    if (isset($entries[$key])) {
        return false;
    }

    $entries[$key] = $now;
    if (count($entries) > 5000) {
        asort($entries);
        $entries = array_slice($entries, -4000, null, true);
    }

    file_put_contents($path, json_encode(['entries' => $entries], JSON_UNESCAPED_UNICODE), LOCK_EX);
    return true;
}

function admin_analytics_track(string $type, string $id, string $userAgent, string $ip): bool
{
    if (!admin_analytics_valid_type($type)) {
        return false;
    }
    $id = admin_analytics_sanitize_id($id);
    if ($id === '') {
        return false;
    }

    $ip = trim($ip);
    if ($ip === '' || !admin_analytics_should_count($type, $id, $ip)) {
        return false;
    }

    $device = admin_analytics_detect_device($userAgent);
    $data = admin_analytics_read();

    $data['totalViews'] = (int) ($data['totalViews'] ?? 0) + 1;
    if (!isset($data['devices'][$device])) {
        $data['devices'][$device] = 0;
    }
    $data['devices'][$device] = (int) $data['devices'][$device] + 1;

    if (!isset($data['pages'][$type][$id])) {
        $data['pages'][$type][$id] = 0;
    }
    $data['pages'][$type][$id] = (int) $data['pages'][$type][$id] + 1;

    $dateKey = admin_analytics_date_key();
    if (!isset($data['daily'][$dateKey]) || !is_array($data['daily'][$dateKey])) {
        $data['daily'][$dateKey] = admin_analytics_empty_day();
    }
    $data['daily'][$dateKey]['totalViews'] = (int) ($data['daily'][$dateKey]['totalViews'] ?? 0) + 1;
    if (!isset($data['daily'][$dateKey]['devices'][$device])) {
        $data['daily'][$dateKey]['devices'][$device] = 0;
    }
    $data['daily'][$dateKey]['devices'][$device] = (int) $data['daily'][$dateKey]['devices'][$device] + 1;
    if (!isset($data['daily'][$dateKey]['pages'][$type][$id])) {
        $data['daily'][$dateKey]['pages'][$type][$id] = 0;
    }
    $data['daily'][$dateKey]['pages'][$type][$id] = (int) $data['daily'][$dateKey]['pages'][$type][$id] + 1;

    admin_analytics_write($data);
    return true;
}

function admin_analytics_count(string $type, string $id): int
{
    if (!admin_analytics_valid_type($type)) {
        return 0;
    }
    $id = admin_analytics_sanitize_id($id);
    if ($id === '') {
        return 0;
    }
    $data = admin_analytics_read();
    return (int) ($data['pages'][$type][$id] ?? 0);
}

function admin_analytics_counts_for_type(string $type, ?array $data = null): array
{
    if (!admin_analytics_valid_type($type)) {
        return [];
    }
    $data = $data ?? admin_analytics_read();
    $pages = $data['pages'][$type] ?? [];
    return is_array($pages) ? $pages : [];
}

function admin_analytics_type_total(string $type, ?array $data = null): int
{
    return array_sum(admin_analytics_counts_for_type($type, $data));
}

function admin_analytics_content_labels(string $type): array
{
    $map = [];
    if ($type === 'plans') {
        $plans = json_read('plans.json');
        foreach ($plans['items'] ?? [] as $plan) {
            if (!is_array($plan)) {
                continue;
            }
            $href = (string) ($plan['href'] ?? '');
            $slug = preg_replace('#^plans/|\.html$#', '', $href);
            if ($slug !== '') {
                $map[$slug] = (string) ($plan['title'] ?? $slug);
            }
        }
        $details = json_read('plans-detail.json');
        foreach ($details['items'] ?? [] as $slug => $detail) {
            if (!isset($map[$slug]) && is_array($detail)) {
                $map[$slug] = (string) ($detail['title'] ?? $slug);
            }
        }
        return $map;
    }

    $fileMap = [
        'articles' => ['file' => 'articles.json', 'key' => 'items'],
        'news' => ['file' => 'news.json', 'key' => 'items'],
        'careers' => ['file' => 'careers.json', 'key' => 'items'],
        'site' => null,
    ];
    $cfg = $fileMap[$type] ?? null;
    if ($cfg === null) {
        return $map;
    }

    $store = json_read($cfg['file']);
    $items = $store[$cfg['key']] ?? [];
    if (!is_array($items)) {
        return $map;
    }
    foreach ($items as $slug => $item) {
        if (!is_array($item)) {
            continue;
        }
        $map[(string) $slug] = (string) ($item['title'] ?? $slug);
    }
    return $map;
}

function admin_analytics_site_page_labels(): array
{
    $site = json_read('site.json');
    $meta = admin_normalize_meta($site['meta'] ?? [], $site['brand'] ?? []);
    $labels = [];
    foreach ($meta['pages'] ?? [] as $slug => $page) {
        $labels[(string) $slug] = (string) ($page['title'] ?? $slug);
    }
    $labels['index.html'] = $labels['index.html'] ?? 'หน้าหลัก';
    return $labels;
}

/**
 * @return list<array{id: string, title: string, views: int, href: string}>
 */
function admin_analytics_rows(string $type, ?array $data = null): array
{
    $counts = admin_analytics_counts_for_type($type, $data);
    $labels = $type === 'site' ? admin_analytics_site_page_labels() : admin_analytics_content_labels($type);
    $rows = [];

    foreach ($counts as $id => $views) {
        $views = (int) $views;
        if ($views <= 0) {
            continue;
        }
        $title = $labels[$id] ?? $id;
        $slug = (string) $id;
        $rows[] = [
            'id' => $slug,
            'title' => $title,
            'views' => $views,
            'href' => admin_analytics_page_url($type, $slug),
            'exists' => admin_analytics_page_exists($type, $slug),
        ];
    }

    usort($rows, static fn(array $a, array $b): int => $b['views'] <=> $a['views']);
    return $rows;
}

function admin_analytics_device_summary(array $data): array
{
    $devices = is_array($data['devices'] ?? null) ? $data['devices'] : [];
    $total = max(1, array_sum(array_map('intval', $devices)));
    $order = ['mobile', 'desktop', 'tablet', 'other'];
    $labels = [
        'mobile' => 'มือถือ',
        'desktop' => 'คอมพิวเตอร์',
        'tablet' => 'แท็บเล็ต',
        'other' => 'อื่นๆ',
    ];
    $rows = [];
    foreach ($order as $key) {
        $count = (int) ($devices[$key] ?? 0);
        if ($count <= 0) {
            continue;
        }
        $rows[] = [
            'key' => $key,
            'label' => $labels[$key],
            'count' => $count,
            'percent' => round(($count / $total) * 100, 1),
        ];
    }
    usort($rows, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
    return $rows;
}

function admin_analytics_format_number(int $n): string
{
    return number_format($n, 0, '.', ',');
}

function admin_analytics_category_breakdown(array $sections, ?array $data = null): array
{
    $grand = 0;
    foreach ($sections as $section) {
        $grand += admin_analytics_type_total((string) $section['key'], $data);
    }
    $grand = max(1, $grand);

    $rows = [];
    foreach ($sections as $section) {
        $total = admin_analytics_type_total((string) $section['key'], $data);
        if ($total <= 0) {
            continue;
        }
        $rows[] = [
            'key' => (string) $section['key'],
            'label' => (string) $section['label'],
            'total' => $total,
            'percent' => round(($total / $grand) * 100, 1),
        ];
    }

    usort($rows, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);
    return $rows;
}

/**
 * @return array{rows: list<array>, total: int, count: int, maxViews: int}
 */
function admin_analytics_section_stats(string $type, ?array $data = null): array
{
    $rows = admin_analytics_rows($type, $data);
    $total = admin_analytics_type_total($type, $data);
    $maxViews = $rows !== [] ? (int) $rows[0]['views'] : 1;
    $maxViews = max(1, $maxViews);

    foreach ($rows as $i => $row) {
        $views = (int) $row['views'];
        $rows[$i]['rank'] = $i + 1;
        $rows[$i]['share'] = $total > 0 ? round(($views / $total) * 100, 1) : 0.0;
        $rows[$i]['bar'] = round(($views / $maxViews) * 100, 1);
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'count' => count($rows),
        'maxViews' => $maxViews,
    ];
}
