<?php
declare(strict_types=1);

// Trim data/plans-detail.json to only keep the plan slugs
// referenced by data/plans.json.
//
// This reduces GitHub Pages deploy failures caused by large files.

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/generate-js.php';

$plans = json_read('plans.json');
$planItems = $plans['items'] ?? [];

$neededSlugs = [];
foreach ($planItems as $item) {
    $href = (string) ($item['href'] ?? '');
    // Expected: plans/<slug>.html
    if ($href === '') continue;
    if (!preg_match('#^plans/([^/]+)\.html$#', $href, $m)) {
        continue;
    }
    $neededSlugs[] = (string) $m[1];
}
$neededSlugs = array_values(array_unique($neededSlugs));

$plansDetail = json_read('plans-detail.json');
$detailItems = $plansDetail['items'] ?? [];

// Keep only matching keys.
$plansDetail['items'] = array_intersect_key($detailItems, array_flip($neededSlugs));

// Keep possible "list" ordering if the file has it.
if (isset($plansDetail['list']) && is_array($plansDetail['list'])) {
    $plansDetail['list'] = array_values(array_filter(
        $plansDetail['list'],
        static fn($slug) => in_array((string) $slug, $neededSlugs, true)
    ));
    if ($plansDetail['list'] === []) {
        $plansDetail['list'] = $neededSlugs;
    }
}

json_write('plans-detail.json', $plansDetail);

// Regenerate JS (and related SEO files).
generate_all_js();

echo "Trimmed plans-detail.json. Kept slugs: " . implode(', ', $neededSlugs) . PHP_EOL;

