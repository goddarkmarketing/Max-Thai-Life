<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/generate-js.php';
require_once __DIR__ . '/../includes/landing-blocks.php';
require_once __DIR__ . '/../includes/landing-pages.php';
require_once __DIR__ . '/../includes/plan-blocks.php';

require_once __DIR__ . '/lib/TestRunner.php';
require_once __DIR__ . '/lib/DataSnapshot.php';
require_once __DIR__ . '/lib/HttpClient.php';
require_once __DIR__ . '/lib/JsReader.php';

function test_marker(string $prefix = 'MTL_TEST'): string
{
    return $prefix . '_' . bin2hex(random_bytes(4));
}

function test_patch_json(string $file, callable $mutator): mixed
{
    $data = json_read($file);
    $backup = json_encode($data, JSON_UNESCAPED_UNICODE);
    $mutator($data);
    json_write($file, $data);
    return json_decode($backup, true);
}

function test_restore_json(string $file, array $original): void
{
    json_write($file, $original);
}

/** @return array<string, string> */
function test_site_form_fields(array $site, array $overrides = []): array
{
    $brand = $site['brand'] ?? [];
    $agent = $site['agent'] ?? [];
    $social = $site['social'] ?? [];
    return array_merge([
        'brand_name' => $brand['name'] ?? '',
        'brand_sub' => $brand['sub'] ?? '',
        'brand_logo' => $brand['logo'] ?? '',
        'agent_name' => $agent['name'] ?? '',
        'agent_title' => $agent['title'] ?? '',
        'agent_branch' => $agent['branch'] ?? '',
        'agent_phone' => $agent['phone'] ?? '',
        'agent_phone_display' => $agent['phoneDisplay'] ?? '',
        'agent_license' => $agent['license'] ?? '',
        'agent_ul' => $agent['ulRights'] ?? '',
        'agent_tagline' => $agent['tagline'] ?? '',
        'social_facebook' => $social['facebook'] ?? '',
        'social_line' => $social['line'] ?? '',
        'social_email' => $social['email'] ?? '',
    ], $overrides);
}

/** @return array<string, string> */
function test_create_png(string $path): void
{
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        true
    );
    if ($png === false) {
        throw new RuntimeException('สร้าง PNG ทดสอบไม่สำเร็จ');
    }
    file_put_contents($path, $png);
}

function test_first_plan_slug(): string
{
    $plans = json_read('plans.json');
    $href = $plans['items'][0]['href'] ?? '';
    $slug = preg_replace('#^plans/|\.html$#', '', $href);
    if ($slug === '') {
        throw new RuntimeException('ไม่พบ slug แผนประกัน');
    }
    return $slug;
}

function test_home_hero_form_fields(array $home, array $overrides = []): array
{
    $hero = $home['hero'] ?? [];
    $ctaPrimary = $hero['ctaPrimary'] ?? [];
    $ctaPhone = $hero['ctaPhone'] ?? [];
    $ctaContact = $hero['ctaContact'] ?? [];
    return array_merge([
        'tab' => 'hero',
        'hero_image' => $hero['image'] ?? '',
        'hero_alt' => $hero['alt'] ?? '',
        'hero_avatar' => $hero['avatar'] ?? '',
        'hero_lead' => $hero['lead'] ?? '',
        'cta_primary_label' => $ctaPrimary['label'] ?? '',
        'cta_primary_href' => $ctaPrimary['href'] ?? '',
        'cta_phone_label' => $ctaPhone['label'] ?? '',
        'cta_phone_href' => $ctaPhone['href'] ?? '',
        'cta_contact_label' => $ctaContact['label'] ?? '',
        'cta_contact_href' => $ctaContact['href'] ?? '',
    ], $overrides);
}
