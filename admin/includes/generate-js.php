<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function js_encode($data, int $indent = 0): string
{
    if ($data === null) {
        return 'null';
    }
    if (is_bool($data)) {
        return $data ? 'true' : 'false';
    }
    if (is_int($data) || is_float($data)) {
        return (string) $data;
    }
    if (is_string($data)) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if (is_array($data)) {
        if ($data === []) {
            return '[]';
        }
        $isList = array_keys($data) === range(0, count($data) - 1);
        $pad = str_repeat('  ', $indent);
        $padInner = str_repeat('  ', $indent + 1);
        $lines = [];
        if ($isList) {
            foreach ($data as $item) {
                $lines[] = $padInner . js_encode($item, $indent + 1);
            }
            return "[\n" . implode(",\n", $lines) . "\n" . $pad . ']';
        }
        foreach ($data as $key => $value) {
            $k = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string) $key) ? $key : json_encode($key);
            $lines[] = $padInner . $k . ': ' . js_encode($value, $indent + 1);
        }
        return "{\n" . implode(",\n", $lines) . "\n" . $pad . '}';
    }
    return 'null';
}

function js_write_file(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content, LOCK_EX);
}

function generate_all_js(): void
{
    $articles = json_read('articles.json');
    $items = admin_filter_visible_map($articles['items'] ?? []);
    js_write_file(
        JS_PATH . '/articles-data.js',
        "window.ARTICLES_DETAIL = " . js_encode($items) . ";\n"
    );

    $plans = json_read('plans.json');
    $planItems = admin_filter_visible_list($plans['items'] ?? []);
    js_write_file(
        JS_PATH . '/plans-data.js',
        "var PLAN_COVER = \"images/cover แผนประกัน/\";\n\nwindow.PLANS_DATA = " . js_encode($planItems) . ";\n"
    );

    $plansDetail = json_read('plans-detail.json');
    $detailItems = $plansDetail['items'] ?? [];

    $imageMap = [];
    foreach ($planItems as $plan) {
        $href = $plan['href'] ?? '';
        $slug = preg_replace('#^plans/|\.html$#', '', $href);
        if ($slug && !empty($plan['image'])) {
            $imageMap[$slug] = $plan['image'];
        }
    }
    foreach ($detailItems as $slug => &$detail) {
        if (!empty($imageMap[$slug])) {
            $detail['image'] = $imageMap[$slug];
        }
    }
    unset($detail);

    $attachLines = [];
    foreach ($imageMap as $slug => $img) {
        $key = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $slug) ? $slug : json_encode($slug);
        $attachLines[] = '    ' . $key . ': ' . json_encode($img, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $detailJs = "window.PLANS_DETAIL = " . js_encode($detailItems) . ";\n\n";
    $detailJs .= "(function attachPlanImages() {\n";
    $detailJs .= "  var images = {\n" . implode(",\n", $attachLines) . "\n  };\n";
    $detailJs .= "  Object.keys(images).forEach(function (key) {\n";
    $detailJs .= "    if (window.PLANS_DETAIL[key]) {\n";
    $detailJs .= "      window.PLANS_DETAIL[key].image = images[key];\n";
    $detailJs .= "    }\n  });\n})();\n";

    js_write_file(JS_PATH . '/plans-detail-content.js', $detailJs);

    $news = json_read('news.json');
    $newsItems = admin_filter_visible_map($news['items'] ?? []);
    $newsList = admin_filter_slug_list($news['list'] ?? array_keys($newsItems), $news['items'] ?? []);
    $newsHome = admin_filter_slug_list($news['home'] ?? $newsList, $news['items'] ?? []);
    $newsCover = "var NEWS_COVER = \"images/cover%20cart/\";\n\n";
    js_write_file(
        JS_PATH . '/news-data.js',
        $newsCover .
        'window.NEWS_DETAIL = ' . js_encode($newsItems) . ";\n\n" .
        'window.NEWS_LIST = ' . js_encode($newsList) . ";\n\n" .
        'window.NEWS_HOME = ' . js_encode($newsHome) . ";\n"
    );

    $careers = json_read('careers.json');
    $careerItems = admin_filter_visible_map($careers['items'] ?? []);
    $careerList = admin_filter_slug_list($careers['list'] ?? array_keys($careerItems), $careers['items'] ?? []);
    js_write_file(
        JS_PATH . '/careers-data.js',
        'window.CAREERS_DETAIL = ' . js_encode($careerItems) . ";\n\n" .
        'window.CAREERS_LIST = ' . js_encode($careerList) . ";\n"
    );

    $claims = json_read('claim-reviews.json');
    $claimItems = admin_filter_visible_map($claims['items'] ?? []);
    $claimList = admin_filter_slug_list($claims['list'] ?? array_keys($claimItems), $claims['items'] ?? []);
    $claimCover = "var CLAIM_COVER = \"images/cover%20cart/\";\n\n";
    js_write_file(
        JS_PATH . '/claim-reviews-data.js',
        $claimCover .
        'window.CLAIM_REVIEWS_DETAIL = ' . js_encode($claimItems) . ";\n\n" .
        'window.CLAIM_REVIEWS_LIST = ' . js_encode($claimList) . ";\n\n" .
        'window.CLAIM_GALLERY_MORE = ' . js_encode($claims['galleryMore'] ?? []) . ";\n"
    );

    $site = json_read('site.json');
    js_write_file(JS_PATH . '/site-data.js', 'window.SITE_DATA = ' . js_encode($site) . ";\n");

    $home = json_read('home.json');
    js_write_file(JS_PATH . '/home-data.js', 'window.HOME_DATA = ' . js_encode($home) . ";\n");

    $pages = json_read('pages.json');
    js_write_file(JS_PATH . '/pages-data.js', 'window.PAGES_DATA = ' . js_encode($pages) . ";\n");
}

function admin_run_import(): bool
{
    $script = ADMIN_PATH . '/tools/import-from-js.mjs';
    if (!file_exists($script)) {
        return false;
    }
    $cmd = 'node ' . escapeshellarg($script) . ' 2>&1';
    exec($cmd, $output, $code);
    return $code === 0;
}
