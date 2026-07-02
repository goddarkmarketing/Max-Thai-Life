<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/contact-pickers.php';

function json_read(string $file): array
{
    $path = DATA_PATH . '/' . $file;
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON in ' . $file);
    }
    return $data;
}

function json_write(string $file, array $data): void
{
    $path = DATA_PATH . '/' . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_exists($path)) {
        // ไม่สำรองอัตโนมัติทีละไฟล์ — ใช้「สร้างไฟล์สำรองตอนนี้」ใน backups.php แทน (snapshot เต็ม 100%)
    }

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Failed to encode JSON for ' . $file);
    }

    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Failed to write ' . $file);
    }
    rename($tmp, $path);
}

function json_installed(): bool
{
    return file_exists(DATA_PATH . '/site.json');
}

function admin_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item-' . time();
}

function admin_h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/** @return array{name: string, sub: string, logo: string} */
function admin_brand_meta(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $site = json_read('site.json');
    $brand = is_array($site['brand'] ?? null) ? $site['brand'] : [];
    $cache = [
        'name' => (string) ($brand['name'] ?? 'Max Thai Life'),
        'sub' => (string) ($brand['sub'] ?? 'สำนักงานตัวแทนแม็ก'),
        'logo' => (string) ($brand['logo'] ?? 'images/logo/LOGO-THAILIFE.png'),
    ];
    return $cache;
}

function admin_brand_logo_url(): string
{
    return '../' . ltrim(admin_brand_meta()['logo'], '/');
}

function admin_post(string $key, string $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

function admin_post_array(string $key): array
{
    return is_array($_POST[$key] ?? null) ? $_POST[$key] : [];
}

/** @return list<array{image:string,alt:string}> */
function admin_parse_hero_slides_from_post(array $raw, int $max = 6): array
{
    $slides = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $image = trim((string) ($item['image'] ?? ''));
        if ($image === '') {
            continue;
        }
        $slides[] = [
            'image' => $image,
            'alt' => trim((string) ($item['alt'] ?? '')),
        ];
        if (count($slides) >= $max) {
            break;
        }
    }
    return $slides;
}

function admin_content_type_config(string $type): ?array
{
    $map = [
        'articles' => ['dir' => 'articles', 'dataAttr' => 'data-article-id', 'listKey' => null],
        'news' => ['dir' => 'news', 'dataAttr' => 'data-news-id', 'listKey' => 'list'],
        'careers' => ['dir' => 'careers', 'dataAttr' => 'data-career-id', 'listKey' => 'list'],
        'claims' => ['dir' => null, 'dataAttr' => null, 'listKey' => 'list'],
        'plans' => ['dir' => 'plans', 'dataAttr' => 'data-plan-id', 'listKey' => null],
    ];
    return $map[$type] ?? null;
}

function admin_content_types(): array
{
    return [
        'articles' => ['label' => 'บทความ', 'file' => 'articles.json', 'itemsKey' => 'items', 'coverSpec' => 'article_cover'],
        'news' => ['label' => 'ข่าว/กิจกรรม', 'file' => 'news.json', 'itemsKey' => 'items', 'coverSpec' => 'article_cover'],
        'careers' => ['label' => 'แนะนำอาชีพ', 'file' => 'careers.json', 'itemsKey' => 'items', 'coverSpec' => 'career_cover'],
        'claims' => ['label' => 'รีวิวเคลม', 'file' => 'claim-reviews.json', 'itemsKey' => 'items', 'coverSpec' => 'claim_cover'],
    ];
}

function admin_create_content_shell(string $type, string $slug): void
{
    $cfg = admin_content_type_config($type);
    if (!$cfg || !$cfg['dir']) {
        return;
    }

    $path = ROOT_PATH . '/' . $cfg['dir'] . '/' . $slug . '.html';
    if (file_exists($path)) {
        return;
    }

    $templates = [
        'articles' => ROOT_PATH . '/articles/tax-saving.html',
        'news' => ROOT_PATH . '/news/infinite-launch.html',
        'careers' => ROOT_PATH . '/careers/agent-application.html',
        'plans' => ROOT_PATH . '/plans/tax-saving.html',
    ];
    $templateFile = $templates[$type] ?? null;
    if (!$templateFile || !file_exists($templateFile)) {
        return;
    }

    $html = file_get_contents($templateFile);
    if ($html === false) {
        return;
    }

    $html = preg_replace('/' . preg_quote($cfg['dataAttr'], '/') . '="[^"]+"/', $cfg['dataAttr'] . '="' . $slug . '"', $html, 1) ?? $html;
    file_put_contents($path, $html);
}

function admin_is_visible(array $item): bool
{
    return ($item['visible'] ?? true) !== false;
}

function admin_load_admin_config(): array
{
    $path = DATA_PATH . '/admin.json';
    if (!file_exists($path)) {
        $data = [
            'user' => ADMIN_USER,
            'passHash' => ADMIN_PASS_HASH,
        ];
        json_write('admin.json', $data);
        return $data;
    }
    return json_read('admin.json');
}

function admin_save_password(string $current, string $newPass): bool
{
    $cfg = admin_load_admin_config();
    if (!password_verify($current, $cfg['passHash'] ?? '')) {
        return false;
    }
    if (strlen($newPass) < 6) {
        throw new InvalidArgumentException('รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
    }
    $cfg['passHash'] = password_hash($newPass, PASSWORD_DEFAULT);
    json_write('admin.json', $cfg);
    return true;
}

function admin_current_user(): string
{
    return (string) ($_SESSION['admin_user'] ?? ADMIN_USER);
}

/** Read-only account info for the account settings page */
function admin_account_details(): array
{
    $cfg = admin_load_admin_config();
    $username = (string) ($cfg['user'] ?? ADMIN_USER);
    $site = json_read('site.json');
    $agent = $site['agent'] ?? [];
    $brand = $site['brand'] ?? [];
    $adminPath = DATA_PATH . '/admin.json';
    $configUpdated = file_exists($adminPath) ? filemtime($adminPath) : false;
    $loginAt = $_SESSION['admin_login_at'] ?? null;

    $agentLabel = trim(
        implode(' · ', array_filter([
            (string) ($agent['name'] ?? ''),
            (string) ($agent['title'] ?? ''),
            (string) ($agent['branch'] ?? ''),
        ]))
    );

    return [
        ['label' => 'ชื่อผู้ใช้', 'value' => $username],
        ['label' => 'บทบาท', 'value' => 'ผู้ดูแลระบบ'],
        ['label' => 'สถานะ', 'value' => 'ใช้งานอยู่', 'badge' => 'ok'],
        ['label' => 'แบรนด์บนเว็บ', 'value' => (string) ($brand['name'] ?? '—')],
        ['label' => 'ตัวแทนบนเว็บ', 'value' => $agentLabel !== '' ? $agentLabel : '—'],
        ['label' => 'โทรศัพท์ตัวแทน', 'value' => (string) ($agent['phoneDisplay'] ?? $agent['phone'] ?? '—')],
        [
            'label' => 'เข้าสู่ระบบเมื่อ',
            'value' => is_string($loginAt) && $loginAt !== '' ? admin_format_datetime_th($loginAt) : '—',
        ],
        [
            'label' => 'เปลี่ยนรหัสผ่านล่าสุด',
            'value' => $configUpdated ? admin_format_datetime_th(date('c', $configUpdated)) : '—',
        ],
    ];
}

function admin_filter_visible_list(array $items): array
{
    return array_values(array_filter($items, 'admin_is_visible'));
}

function admin_filter_visible_map(array $items): array
{
    return array_filter($items, 'admin_is_visible');
}

function admin_filter_slug_list(array $slugs, array $itemsMap): array
{
    return array_values(array_filter($slugs, static function ($slug) use ($itemsMap) {
        return isset($itemsMap[$slug]) && admin_is_visible($itemsMap[$slug]);
    }));
}

/**
 * ปักหมุด (pin): รายการที่ปักหมุดจะถูกจัดขึ้นก่อนเสมอ เรียงตามเวลาที่ปักหมุด (ปักก่อน = ขึ้นก่อน)
 */
function admin_is_pinned(array $item): bool
{
    return ($item['pinned'] ?? false) === true;
}

function admin_pin_sort_value(array $item): string
{
    $v = $item['pinnedAt'] ?? '';
    return is_string($v) ? $v : '';
}

/**
 * แยกรายการ pinned (เรียงตาม pinnedAt จากเก่าไปใหม่) ไว้ก่อน แล้วต่อด้วยที่เหลือตามลำดับเดิม
 * รับเข้าเป็น list ของ pair ['key' => mixed, 'item' => array] เพื่อใช้ได้ทั้ง map/list/slug
 */
function admin_pin_partition_pairs(array $pairs): array
{
    $pinned = [];
    $rest = [];
    foreach (array_values($pairs) as $i => $pair) {
        if (admin_is_pinned($pair['item'])) {
            $pinned[] = ['i' => $i, 'pair' => $pair];
        } else {
            $rest[] = $pair;
        }
    }
    usort($pinned, static function (array $a, array $b): int {
        $av = admin_pin_sort_value($a['pair']['item']);
        $bv = admin_pin_sort_value($b['pair']['item']);
        if ($av === $bv) {
            return $a['i'] <=> $b['i'];
        }
        if ($av === '') {
            return 1;
        }
        if ($bv === '') {
            return -1;
        }
        return strcmp($av, $bv);
    });

    $out = [];
    foreach ($pinned as $p) {
        $out[] = $p['pair'];
    }
    foreach ($rest as $r) {
        $out[] = $r;
    }
    return $out;
}

/** เรียง map (slug => item) ให้รายการที่ปักหมุดขึ้นก่อน โดยคงคีย์ slug ไว้ */
function admin_sort_pinned_map(array $map): array
{
    $pairs = [];
    foreach ($map as $key => $item) {
        $pairs[] = ['key' => $key, 'item' => $item];
    }
    $sorted = admin_pin_partition_pairs($pairs);
    $out = [];
    foreach ($sorted as $pair) {
        $out[$pair['key']] = $pair['item'];
    }
    return $out;
}

/** เรียง list (array ของ item) ให้รายการที่ปักหมุดขึ้นก่อน */
function admin_sort_pinned_list(array $items): array
{
    $pairs = [];
    foreach (array_values($items) as $i => $item) {
        $pairs[] = ['key' => $i, 'item' => $item];
    }
    $sorted = admin_pin_partition_pairs($pairs);
    return array_map(static fn(array $pair) => $pair['item'], $sorted);
}

/** สร้างรหัสไม่ซ้ำสำหรับรายการรีวิว */
function admin_testimonial_uid(): string
{
    return 'rv' . bin2hex(random_bytes(6));
}

/** อ่าน testimonialsSection พร้อม normalize ให้มี items (migrate จาก slides เดิมถ้าจำเป็น) */
function admin_testimonials_normalize(array $section): array
{
    $items = $section['items'] ?? null;

    if (!is_array($items) || $items === []) {
        $items = [];
        foreach ($section['slides'] ?? [] as $slide) {
            foreach ((array) $slide as $card) {
                if (!is_array($card)) {
                    continue;
                }
                $quote = trim((string) ($card['quote'] ?? ''));
                if ($quote === '') {
                    continue;
                }
                $items[] = [
                    'id' => admin_testimonial_uid(),
                    'quote' => $quote,
                    'author' => trim((string) ($card['author'] ?? '')),
                    'visible' => true,
                ];
            }
        }
    } else {
        $items = array_values(array_map(static function ($it): array {
            if (!is_array($it)) {
                $it = [];
            }
            if (empty($it['id'])) {
                $it['id'] = admin_testimonial_uid();
            }
            return $it;
        }, $items));
    }

    $section['items'] = $items;
    return $section;
}

/** สร้าง slides (กลุ่มละ 3) จาก items ที่มองเห็น เรียงปักหมุดขึ้นก่อน — สำหรับ render หน้าแรก */
function admin_testimonials_rebuild_slides(array $items): array
{
    $visible = array_values(array_filter(
        $items,
        static fn($it) => is_array($it) && ($it['visible'] ?? true) !== false
    ));
    $visible = admin_sort_pinned_list($visible);

    $slides = [];
    $chunk = [];
    foreach ($visible as $it) {
        $chunk[] = [
            'quote' => (string) ($it['quote'] ?? ''),
            'author' => (string) ($it['author'] ?? ''),
        ];
        if (count($chunk) === 3) {
            $slides[] = $chunk;
            $chunk = [];
        }
    }
    if ($chunk !== []) {
        $slides[] = $chunk;
    }

    return $slides;
}

/** บันทึก testimonialsSection กลับ home.json (พร้อม rebuild slides) แล้ว regenerate JS */
function admin_testimonials_persist(array $section): void
{
    $home = json_read('home.json');
    $section['items'] = array_values($section['items'] ?? []);
    $section['slides'] = admin_testimonials_rebuild_slides($section['items']);
    $home['testimonialsSection'] = $section;
    json_write('home.json', $home);

    require_once __DIR__ . '/generate-js.php';
    generate_all_js();
}

/** เรียง list ของ slug ให้รายการที่ปักหมุดขึ้นก่อน (อ้างอิงสถานะ pin จาก $itemsMap) */
function admin_sort_pinned_slugs(array $slugs, array $itemsMap): array
{
    $pairs = [];
    foreach ($slugs as $slug) {
        $pairs[] = ['key' => $slug, 'item' => $itemsMap[$slug] ?? []];
    }
    $sorted = admin_pin_partition_pairs($pairs);
    return array_map(static fn(array $pair) => $pair['key'], $sorted);
}

/**
 * สร้างรายการสำหรับหน้าแรก: รายการที่ปักหมุดขึ้นก่อน (เรียงตาม pinnedAt) แล้วเติมจาก $fallback
 * จนครบอย่างน้อย $min รายการ (ไม่ซ้ำกัน) — ถ้าไม่มีการปักหมุดเลยจะได้ $fallback เดิม
 */
function admin_home_feed_slugs(array $allSlugs, array $itemsMap, array $fallback, int $min = 3): array
{
    $pinned = [];
    foreach ($allSlugs as $slug) {
        if (isset($itemsMap[$slug]) && admin_is_pinned($itemsMap[$slug])) {
            $pinned[] = $slug;
        }
    }
    $pinned = admin_sort_pinned_slugs($pinned, $itemsMap);

    $out = $pinned;
    $seen = array_fill_keys($pinned, true);
    foreach (array_merge($fallback, $allSlugs) as $slug) {
        if (count($out) >= $min) {
            break;
        }
        if (!isset($seen[$slug]) && isset($itemsMap[$slug])) {
            $out[] = $slug;
            $seen[$slug] = true;
        }
    }
    return $out;
}

/** คงค่า pinned/pinnedAt จาก item เดิมไว้บน item ใหม่ (ใช้ตอนแก้ไขการ์ด) */
function admin_preserve_pin(array $entry, array $prev): array
{
    if (array_key_exists('pinned', $prev)) {
        $entry['pinned'] = $prev['pinned'];
    }
    if (array_key_exists('pinnedAt', $prev)) {
        $entry['pinnedAt'] = $prev['pinnedAt'];
    }
    return $entry;
}

function admin_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / (1024 * 1024), 2) . ' MB';
}

/** @return list<string> */
function admin_backup_js_files(): array
{
    return [
        'site-data.js',
        'home-data.js',
        'pages-data.js',
        'plans-data.js',
        'plans-detail-content.js',
        'articles-data.js',
        'news-data.js',
        'careers-data.js',
        'claim-reviews-data.js',
    ];
}

/** @return list<string> */
function admin_backup_media_roots(): array
{
    return array_values(array_unique(array_merge(admin_media_allowed_roots(), [
        'images/profile',
        'images/plans',
        'images/plan-cards',
    ])));
}

/** @return list<string> */
function admin_backup_extra_files(): array
{
    return [
        'images/hero-banner.png',
    ];
}

/**
 * @return array<string, string> relativePath => absolutePath
 */
function admin_collect_backup_files(): array
{
    $files = [];

    foreach (glob(DATA_PATH . '/*.json') ?: [] as $file) {
        $name = basename($file);
        $files['data/' . $name] = $file;
    }

    foreach (admin_backup_js_files() as $js) {
        $path = JS_PATH . '/' . $js;
        if (is_file($path)) {
            $files['js/' . $js] = $path;
        }
    }

    foreach (admin_backup_media_roots() as $root) {
        $absRoot = ROOT_PATH . '/' . str_replace('\\', '/', trim($root, '/'));
        if (!is_dir($absRoot)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $full = $item->getPathname();
            $relFromRoot = str_replace('\\', '/', substr($full, strlen(ROOT_PATH) + 1));
            $files['media/' . $relFromRoot] = $full;
        }
    }

    foreach (admin_backup_extra_files() as $rel) {
        $path = ROOT_PATH . '/' . str_replace('\\', '/', $rel);
        if (is_file($path)) {
            $files['media/' . $rel] = $path;
        }
    }

    return $files;
}

function admin_backup_is_legacy(string $dir): bool
{
    if (is_file($dir . '/manifest.json') || is_dir($dir . '/data')) {
        return false;
    }
    return glob($dir . '/*.json') !== [];
}

function admin_remove_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            admin_remove_dir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }
    rmdir($dir);
}

/** @return array{version:int,kind:string,createdAt:string,totalFiles:int,totalBytes:int,counts:array<string,int>} */
function admin_read_backup_manifest(string $dir): array
{
    $manifestPath = $dir . '/manifest.json';
    if (is_file($manifestPath)) {
        $data = json_decode(file_get_contents($manifestPath) ?: '', true);
        if (is_array($data)) {
            return $data;
        }
    }
    $files = admin_backup_is_legacy($dir)
        ? (glob($dir . '/*.json') ?: [])
        : admin_backup_iter_files($dir);
    $totalBytes = 0;
    foreach ($files as $file) {
        $totalBytes += (int) (filesize($file) ?: 0);
    }
    return [
        'version' => admin_backup_is_legacy($dir) ? 1 : 2,
        'kind' => admin_backup_is_legacy($dir) ? 'legacy' : 'full',
        'createdAt' => '',
        'totalFiles' => count($files),
        'totalBytes' => $totalBytes,
        'counts' => ['data' => count(glob($dir . '/*.json') ?: [])],
    ];
}

/** @return list<string> */
function admin_backup_iter_files(string $dir): array
{
    $out = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $item) {
        if ($item->isFile() && basename($item->getPathname()) !== 'manifest.json') {
            $out[] = $item->getPathname();
        }
    }
    return $out;
}

function admin_backup_max_count(): int
{
    return max(1, (int) (defined('ADMIN_BACKUP_MAX') ? ADMIN_BACKUP_MAX : 15));
}

/** @return list<string> ใหม่สุดก่อน */
function admin_backup_ids(): array
{
    if (!is_dir(BACKUP_PATH)) {
        return [];
    }
    $dirs = glob(BACKUP_PATH . '/*', GLOB_ONLYDIR) ?: [];
    rsort($dirs);
    $ids = [];
    foreach ($dirs as $dir) {
        $id = basename($dir);
        if (preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $id)) {
            $ids[] = $id;
        }
    }
    return $ids;
}

/** ลบชุดเก่าสุดจนเหลือไม่เกิน $max ชุด — คืนจำนวนที่ลบ */
function admin_prune_backups(?int $max = null): int
{
    $max = $max ?? admin_backup_max_count();
    $ids = admin_backup_ids();
    $removed = 0;
    while (count($ids) > $max) {
        $oldest = array_pop($ids);
        if ($oldest === null) {
            break;
        }
        admin_delete_backup($oldest);
        $removed++;
    }
    return $removed;
}

/** @return int จำนวนชุดที่ลบ */
function admin_delete_all_backups(): int
{
    $ids = admin_backup_ids();
    foreach ($ids as $id) {
        admin_delete_backup($id);
    }
    return count($ids);
}

function admin_list_backups(): array
{
    if (!is_dir(BACKUP_PATH)) {
        return [];
    }
    $out = [];
    foreach (admin_backup_ids() as $id) {
        $dir = BACKUP_PATH . '/' . $id;
        $manifest = admin_read_backup_manifest($dir);
        $out[] = [
            'id' => $id,
            'path' => $dir,
            'label' => admin_format_backup_datetime($id),
            'mtime' => filemtime($dir) ?: 0,
            'version' => (int) ($manifest['version'] ?? 1),
            'kind' => (string) ($manifest['kind'] ?? 'legacy'),
            'totalFiles' => (int) ($manifest['totalFiles'] ?? 0),
            'totalBytes' => (int) ($manifest['totalBytes'] ?? 0),
            'counts' => is_array($manifest['counts'] ?? null) ? $manifest['counts'] : [],
            'isFull' => ((string) ($manifest['kind'] ?? '')) === 'full',
        ];
    }
    return $out;
}

function admin_format_backup_datetime(string $id): string
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})-(\d{2})$/', $id, $m)) {
        return $id;
    }
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $mo = (int) $m[2];
    return sprintf(
        '%d %s %d · %s:%s:%s',
        (int) $m[3],
        $months[$mo] ?? $m[2],
        (int) $m[1],
        $m[4],
        $m[5],
        $m[6]
    );
}

function admin_create_manual_backup(): string
{
    require_once __DIR__ . '/generate-js.php';
    generate_all_js();

    if (!is_dir(BACKUP_PATH) && !mkdir(BACKUP_PATH, 0755, true)) {
        throw new RuntimeException('สร้างโฟลเดอร์สำรองไม่สำเร็จ');
    }

    $id = date('Y-m-d_H-i-s');
    $dir = BACKUP_PATH . '/' . $id;
    if (is_dir($dir)) {
        admin_remove_dir($dir);
    }
    if (!mkdir($dir, 0755, true)) {
        throw new RuntimeException('สร้างโฟลเดอร์สำรองไม่สำเร็จ');
    }

    $sources = admin_collect_backup_files();
    if ($sources === []) {
        admin_remove_dir($dir);
        throw new RuntimeException('ไม่มีไฟล์ให้สำรอง');
    }

    $counts = ['data' => 0, 'js' => 0, 'media' => 0];
    $totalBytes = 0;

    foreach ($sources as $rel => $src) {
        $dest = $dir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $destDir = dirname($dest);
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            throw new RuntimeException('สร้างโฟลเดอร์สำรองไม่สำเร็จ');
        }
        if (!copy($src, $dest)) {
            throw new RuntimeException('คัดลอกไฟล์ไม่สำเร็จ: ' . $rel);
        }
        $totalBytes += (int) (filesize($src) ?: 0);
        if (str_starts_with($rel, 'data/')) {
            $counts['data']++;
        } elseif (str_starts_with($rel, 'js/')) {
            $counts['js']++;
        } elseif (str_starts_with($rel, 'media/')) {
            $counts['media']++;
        }
    }

    $manifest = [
        'version' => 2,
        'kind' => 'full',
        'createdAt' => date('c'),
        'totalFiles' => count($sources),
        'totalBytes' => $totalBytes,
        'counts' => $counts,
    ];
    file_put_contents(
        $dir . '/manifest.json',
        json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        LOCK_EX
    );

    try {
        admin_build_backup_zip($id);
    } catch (Throwable $e) {
        // โฟลเดอร์สำรองยังใช้ได้ — ดาวน์โหลดจะลองสร้าง zip อีกครั้ง
    }

    admin_prune_backups();

    return $id;
}

function admin_backup_prepare_download(): void
{
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function admin_send_file_download(string $path, string $downloadName, string $mime = 'application/octet-stream'): void
{
    admin_backup_prepare_download();
    if (!is_file($path)) {
        throw new RuntimeException('ไม่พบไฟล์');
    }
    $size = filesize($path);
    if ($size === false) {
        throw new RuntimeException('อ่านขนาดไฟล์ไม่สำเร็จ');
    }
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
    header('Content-Length: ' . (string) $size);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    $fp = fopen($path, 'rb');
    if ($fp === false) {
        throw new RuntimeException('เปิดไฟล์ไม่สำเร็จ');
    }
    while (!feof($fp)) {
        $chunk = fread($fp, 1024 * 1024);
        if ($chunk === false) {
            fclose($fp);
            throw new RuntimeException('อ่านไฟล์ไม่สำเร็จ');
        }
        echo $chunk;
        flush();
    }
    fclose($fp);
    exit;
}

function admin_backup_zip_skip(string $basename): bool
{
    return in_array($basename, ['all.zip', 'data-only.zip'], true);
}

/** @return list<string> */
function admin_backup_zip_sources(string $dir, bool $dataOnly = false): array
{
    $out = [];
    foreach (admin_backup_iter_files($dir) as $path) {
        $basename = basename($path);
        if (admin_backup_zip_skip($basename)) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($path, strlen($dir) + 1));
        if ($dataOnly && !str_starts_with($rel, 'data/') && !str_starts_with($rel, 'js/')) {
            continue;
        }
        $out[] = $path;
    }
    return $out;
}

function admin_build_backup_zip(string $backupId, bool $dataOnly = false): string
{
    $backupId = basename($backupId);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
        throw new InvalidArgumentException('รหัสสำรองไม่ถูกต้อง');
    }
    $dir = BACKUP_PATH . '/' . $backupId;
    if (!is_dir($dir)) {
        throw new RuntimeException('ไม่พบไฟล์สำรอง');
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('เซิร์ฟเวอร์ไม่รองรับ Zip — ติดต่อโฮสต์ให้เปิด php-zip');
    }

    admin_backup_prepare_download();

    $zipName = $dataOnly ? 'data-only.zip' : 'all.zip';
    $zipPath = $dir . '/' . $zipName;
    if (is_file($zipPath)) {
        unlink($zipPath);
    }

    $sources = admin_backup_zip_sources($dir, $dataOnly);
    if ($sources === []) {
        throw new RuntimeException('ไม่มีไฟล์ให้บีบอัด');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('สร้างไฟล์ zip ไม่สำเร็จ');
    }
    foreach ($sources as $path) {
        $rel = str_replace('\\', '/', substr($path, strlen($dir) + 1));
        if (!$zip->addFile($path, $rel)) {
            $zip->close();
            if (is_file($zipPath)) {
                unlink($zipPath);
            }
            throw new RuntimeException('บีบอัดไฟล์ไม่สำเร็จ: ' . $rel);
        }
    }
    if (!$zip->close()) {
        if (is_file($zipPath)) {
            unlink($zipPath);
        }
        throw new RuntimeException('ปิดไฟล์ zip ไม่สำเร็จ');
    }
    if (!is_file($zipPath) || filesize($zipPath) === 0) {
        throw new RuntimeException('สร้างไฟล์ zip ไม่สำเร็จ');
    }

    return $zipPath;
}

function admin_delete_backup(string $backupId): void
{
    $backupId = basename($backupId);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
        throw new InvalidArgumentException('รหัสสำรองไม่ถูกต้อง');
    }
    $dir = BACKUP_PATH . '/' . $backupId;
    if (!is_dir($dir)) {
        throw new RuntimeException('ไม่พบไฟล์สำรอง');
    }
    admin_remove_dir($dir);
}

function admin_backup_file_path(string $backupId, string $file): string
{
    $backupId = basename($backupId);
    $file = str_replace('\\', '/', trim($file, '/'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
        throw new InvalidArgumentException('รหัสสำรองไม่ถูกต้อง');
    }
    if (!preg_match('/^(data|js|media)\/[a-zA-Z0-9_\-\.\/]+\.(json|js|png|jpe?g|webp|gif|svg|mp4|webm|ogg|mov|ttf|woff2?)$/i', $file)
        && !preg_match('/^[a-zA-Z0-9_\-\.]+\.json$/', $file)) {
        throw new InvalidArgumentException('ชื่อไฟล์ไม่ถูกต้อง');
    }
    $dir = BACKUP_PATH . '/' . $backupId;
    if (admin_backup_is_legacy($dir)) {
        $path = $dir . '/' . basename($file);
    } else {
        $path = $dir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $file);
    }
    if (!is_file($path)) {
        throw new RuntimeException('ไม่พบไฟล์');
    }
    return $path;
}

function admin_restore_backup_file(string $backupDir, string $rel, string $src): void
{
    $rel = str_replace('\\', '/', $rel);
    if (str_starts_with($rel, 'data/')) {
        $name = basename($rel);
        json_write($name, json_decode(file_get_contents($src) ?: '[]', true) ?: []);
        return;
    }
    if (str_starts_with($rel, 'js/')) {
        $dest = JS_PATH . '/' . basename($rel);
    } elseif (str_starts_with($rel, 'media/')) {
        $dest = ROOT_PATH . '/' . substr($rel, strlen('media/'));
    } else {
        $dest = DATA_PATH . '/' . basename($rel);
    }
    $destDir = dirname($dest);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!copy($src, $dest)) {
        throw new RuntimeException('กู้คืนไฟล์ไม่สำเร็จ: ' . $rel);
    }
}

function admin_media_allowed_roots(): array
{
    return [
        'images/uploads',
        'videos/uploads',
        'images/plan-covers',
        'images/cover cart',
        'images/แนะนำอาชีพ',
        'images/cta',
        'images/logo',
    ];
}

function admin_is_allowed_media_path(string $path): bool
{
    $path = str_replace('\\', '/', trim($path, '/'));
    foreach (admin_media_allowed_roots() as $root) {
        if ($path === $root || str_starts_with($path, $root . '/')) {
            return true;
        }
    }
    return false;
}

function admin_delete_media_file(string $path): void
{
    $path = str_replace('\\', '/', trim($path, '/'));
    if (!admin_is_allowed_media_path($path)) {
        throw new InvalidArgumentException('ไม่สามารถลบไฟล์นอกโฟลเดอร์ที่อนุญาต');
    }
    $full = ROOT_PATH . '/' . $path;
    $real = realpath($full);
    $rootReal = realpath(ROOT_PATH);
    if ($real === false || $rootReal === false || !str_starts_with($real, $rootReal)) {
        throw new RuntimeException('ไม่พบไฟล์');
    }
    if (!is_file($real)) {
        throw new RuntimeException('ไม่พบไฟล์');
    }
    if (!unlink($real)) {
        throw new RuntimeException('ลบไฟล์ไม่สำเร็จ');
    }
}

function admin_restore_backup(string $backupId, ?string $file = null): void
{
    $backupId = basename($backupId);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
        throw new InvalidArgumentException('รหัสสำรองไม่ถูกต้อง');
    }
    $dir = BACKUP_PATH . '/' . $backupId;
    if (!is_dir($dir)) {
        throw new RuntimeException('ไม่พบไฟล์สำรอง');
    }

    if ($file !== null && $file !== '') {
        $file = str_replace('\\', '/', trim($file, '/'));
        if (admin_backup_is_legacy($dir)) {
            $src = $dir . '/' . basename($file);
        } else {
            $src = $dir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $file);
        }
        if (!is_file($src)) {
            throw new RuntimeException('ไม่พบไฟล์');
        }
        admin_restore_backup_file($dir, admin_backup_is_legacy($dir) ? 'data/' . basename($file) : $file, $src);
        return;
    }

    if (admin_backup_is_legacy($dir)) {
        foreach (glob($dir . '/*.json') ?: [] as $src) {
            $name = basename($src);
            json_write($name, json_decode(file_get_contents($src) ?: '[]', true) ?: []);
        }
        return;
    }

    foreach (admin_backup_iter_files($dir) as $src) {
        $rel = str_replace('\\', '/', substr($src, strlen($dir) + 1));
        admin_restore_backup_file($dir, $rel, $src);
    }
}

function admin_scan_media_files(): array
{
    $roots = [
        'images/uploads' => 'อัปโหลด',
        'videos/uploads' => 'วิดีโอ',
        'images/plan-covers' => 'แผนประกัน',
        'images/cover cart' => 'ปกบทความ',
        'images/แนะนำอาชีพ' => 'แนะนำอาชีพ',
        'images/cta' => 'CTA',
        'images/logo' => 'โลโก้',
    ];
    $files = [];
    foreach ($roots as $rel => $label) {
        $abs = ROOT_PATH . '/' . $rel;
        if (!is_dir($abs)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                continue;
            }
            $path = str_replace('\\', '/', substr($file->getPathname(), strlen(ROOT_PATH) + 1));
            $files[] = [
                'path' => $path,
                'group' => $label,
                'size' => $file->getSize(),
                'mtime' => $file->getMTime(),
            ];
        }
    }
    usort($files, static fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $files;
}

function admin_content_preview_url(string $type, string $slug): string
{
    $map = [
        'plans' => '../plans/' . $slug . '.html',
        'articles' => '../articles/' . $slug . '.html',
        'news' => '../news/' . $slug . '.html',
        'careers' => '../careers/' . $slug . '.html',
        'claims' => '../products.html#claims',
    ];
    return $map[$type] ?? '#';
}

function admin_duplicate_slug(string $slug, array $existing): string
{
    $base = $slug . '-copy';
    $candidate = $base;
    $n = 2;
    while (in_array($candidate, $existing, true)) {
        $candidate = $base . '-' . $n;
        $n++;
    }
    return $candidate;
}

function admin_delete_content_shell(string $type, string $slug): void
{
    $cfg = admin_content_type_config($type);
    if (!$cfg || !$cfg['dir']) {
        return;
    }
    $path = ROOT_PATH . '/' . $cfg['dir'] . '/' . $slug . '.html';
    if (file_exists($path)) {
        unlink($path);
    }
}

function admin_default_plan_nav_children(): array
{
    $children = [
        ['label' => 'แผนประกันทั้งหมด', 'href' => 'plans.html', 'icon' => 'layout-grid'],
    ];
    foreach (admin_plan_categories() as $cat) {
        $children[] = [
            'label' => $cat['label'],
            'href' => 'plans.html?category=' . $cat['id'] . '#' . $cat['id'],
            'category' => $cat['id'],
            'icon' => $cat['icon'],
        ];
    }
    return $children;
}

function admin_default_navigation(): array
{
    return [
        ['label' => 'หน้าหลัก', 'href' => 'index.html', 'visible' => true],
        ['label' => 'เกี่ยวกับเรา', 'href' => 'about.html', 'visible' => true],
        [
            'label' => 'แผนประกัน',
            'href' => 'plans.html',
            'visible' => true,
            'children' => admin_default_plan_nav_children(),
        ],
        ['label' => 'บทความ', 'href' => 'products.html', 'visible' => true],
        ['label' => 'แนะนำอาชีพ', 'href' => 'career.html', 'visible' => true],
        ['label' => 'ข่าว/กิจกรรม', 'href' => 'news.html', 'visible' => true],
        ['label' => 'รีวิวเคลม', 'href' => 'claim-reviews.html', 'visible' => true],
        ['label' => 'ติดต่อ', 'href' => 'contact.html', 'visible' => true, 'cta' => true],
    ];
}

function admin_apply_site_general_post(array $data): array
{
    $extraContacts = $data['agent']['extraContacts'] ?? null;
    $data['brand'] = [
        'name' => admin_post('brand_name'),
        'sub' => admin_post('brand_sub'),
        'logo' => admin_post('brand_logo'),
    ];
    $data['agent'] = [
        'name' => admin_post('agent_name'),
        'title' => admin_post('agent_title'),
        'branch' => admin_post('agent_branch'),
        'phone' => preg_replace('/\D/', '', admin_post('agent_phone')),
        'phoneDisplay' => admin_post('agent_phone_display'),
        'license' => admin_post('agent_license'),
        'ulRights' => admin_post('agent_ul'),
        'tagline' => admin_post('agent_tagline'),
    ];
    if (is_array($extraContacts)) {
        $data['agent']['extraContacts'] = $extraContacts;
    }
    return $data;
}

function admin_apply_site_navigation_post(array $data): array
{
    $labels = admin_post_array('nav_label');
    $hrefs = admin_post_array('nav_href');
    $visibles = admin_post_array('nav_visible');
    $ctas = admin_post_array('nav_cta');
    $existing = $data['navigation'] ?? [];
    $nav = [];
    foreach ($labels as $i => $label) {
        $label = trim($label);
        $href = trim($hrefs[$i] ?? '');
        if ($label === '' || $href === '') {
            continue;
        }
        $item = ['label' => $label, 'href' => $href, 'visible' => isset($visibles[$i])];
        if (isset($ctas[$i])) {
            $item['cta'] = true;
        }
        if (str_starts_with($href, 'plans.html')) {
            foreach ($existing as $old) {
                if (str_starts_with((string) ($old['href'] ?? ''), 'plans.html') && !empty($old['children'])) {
                    $item['children'] = $old['children'];
                    break;
                }
            }
            if (empty($item['children'])) {
                $item['children'] = admin_default_plan_nav_children();
            }
        }
        $nav[] = $item;
    }
    $data['navigation'] = $nav !== [] ? $nav : admin_default_navigation();
    return $data;
}

function admin_nav_item_visible(array $item): bool
{
    return !isset($item['visible']) || (bool) $item['visible'];
}

function admin_nav_preview_url(string $href): string
{
    $href = trim($href);
    if ($href === '') {
        return '#';
    }
    if (preg_match('#^(https?://|tel:|mailto:)#i', $href)) {
        return $href;
    }
    return '../' . ltrim(str_replace('\\', '/', $href), '/');
}

function admin_nav_is_plans_href(string $href): bool
{
    $path = strtok($href, '?') ?: $href;
    return $path === 'plans.html' || str_ends_with($path, '/plans.html');
}

/** @return list<array<string, mixed>> */
function admin_nav_parse_children_from_post(array $post): array
{
    $labels = is_array($post['nav_child_label'] ?? null) ? $post['nav_child_label'] : [];
    $hrefs = is_array($post['nav_child_href'] ?? null) ? $post['nav_child_href'] : [];
    $categories = is_array($post['nav_child_category'] ?? null) ? $post['nav_child_category'] : [];
    $children = [];
    foreach ($labels as $i => $label) {
        $label = trim((string) $label);
        $href = trim((string) ($hrefs[$i] ?? ''));
        if ($label === '' || $href === '') {
            continue;
        }
        $child = ['label' => $label, 'href' => $href];
        $cat = trim((string) ($categories[$i] ?? ''));
        if ($cat !== '') {
            $child['category'] = $cat;
        }
        $children[] = $child;
    }
    return $children;
}

/**
 * @param list<array<string, mixed>> $navigation
 * @return list<array<string, mixed>>
 */
function admin_nav_save_item(array $navigation, string $index, array $post): array
{
    $label = trim((string) ($post['label'] ?? ''));
    $href = trim((string) ($post['href'] ?? ''));
    if ($label === '' || $href === '') {
        throw new RuntimeException('กรุณากรอกชื่อเมนูและลิงก์');
    }

    $item = [
        'label' => $label,
        'href' => $href,
        'visible' => isset($post['visible']),
    ];
    if (isset($post['cta'])) {
        $item['cta'] = true;
    }

    $wantsChildren = isset($post['has_children']) || admin_nav_is_plans_href($href);
    if ($wantsChildren) {
        $children = admin_nav_parse_children_from_post($post);
        if ($children !== []) {
            $item['children'] = $children;
        } elseif (admin_nav_is_plans_href($href)) {
            $item['children'] = admin_default_plan_nav_children();
        }
    }

    if ($index === 'new') {
        $navigation[] = $item;
        return $navigation;
    }

    $indexInt = (int) $index;
    if (!isset($navigation[$indexInt])) {
        throw new RuntimeException('ไม่พบเมนู');
    }
    $navigation[$indexInt] = $item;
    return $navigation;
}

/**
 * @param list<array<string, mixed>> $navigation
 * @return list<array<string, mixed>>
 */
function admin_nav_delete_item(array $navigation, int $index): array
{
    if (!isset($navigation[$index])) {
        throw new RuntimeException('ไม่พบเมนู');
    }
    array_splice($navigation, $index, 1);
    return array_values($navigation);
}

/**
 * @param list<array<string, mixed>> $navigation
 * @return list<array<string, mixed>>
 */
function admin_nav_toggle_visible(array $navigation, int $index): array
{
    if (!isset($navigation[$index])) {
        throw new RuntimeException('ไม่พบเมนู');
    }
    $navigation[$index]['visible'] = !admin_nav_item_visible($navigation[$index]);
    return $navigation;
}

/** บันทึก navigation ใน site.json แล้ว sync ไป js/site-data.js ทันที */
function admin_nav_publish_site(array $data): void
{
    json_write('site.json', $data);
    require_once __DIR__ . '/generate-js.php';
    generate_all_js();
}

function admin_inline_drag_icon(): string
{
    return '<svg class="nav-drag-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>';
}

/**
 * @param list<mixed> $list
 * @param list<int|string> $order
 * @return list<mixed>
 */
function admin_reorder_list_by_indices(array $list, array $order): array
{
    $new = [];
    foreach ($order as $idx) {
        $idx = (int) $idx;
        if (isset($list[$idx])) {
            $new[] = $list[$idx];
        }
    }
    if (count($new) !== count($list)) {
        throw new RuntimeException('ลำดับไม่ถูกต้อง');
    }
    return $new;
}

/** บันทึก footer ใน site.json แล้ว sync ไป js/site-data.js ทันที */
function admin_footer_publish_site(array $data): void
{
    json_write('site.json', $data);
    require_once __DIR__ . '/generate-js.php';
    generate_all_js();
}

/**
 * @param list<int|string> $order
 */
function admin_footer_reorder(array $footer, string $section, int $col, array $order): array
{
    if ($section === 'topCta') {
        $footer['topCta'] = admin_reorder_list_by_indices($footer['topCta'] ?? [], $order);
    } elseif ($section === 'bottom') {
        $footer['bottom']['links'] = admin_reorder_list_by_indices($footer['bottom']['links'] ?? [], $order);
    } elseif ($section === 'link') {
        if (!isset($footer['columns'][$col])) {
            throw new RuntimeException('ไม่พบคอลัมน์');
        }
        $footer['columns'][$col]['links'] = admin_reorder_list_by_indices($footer['columns'][$col]['links'] ?? [], $order);
    } else {
        throw new RuntimeException('ไม่รองรับการจัดลำดับ');
    }
    return admin_normalize_footer($footer);
}

/**
 * @param list<array<string, mixed>> $navigation
 * @return list<array<string, mixed>>
 */
function admin_nav_patch_item(array $navigation, int $index, array $post): array
{
    if (!isset($navigation[$index])) {
        throw new RuntimeException('ไม่พบเมนู');
    }
    $existing = $navigation[$index];
    $label = trim((string) ($post['label'] ?? ''));
    $href = trim((string) ($post['href'] ?? ''));
    if ($label === '' || $href === '') {
        throw new RuntimeException('กรุณากรอกชื่อเมนูและลิงก์');
    }

    $item = [
        'label' => $label,
        'href' => $href,
        'visible' => isset($post['visible']),
    ];
    if (isset($post['cta'])) {
        $item['cta'] = true;
    }
    if (!empty($existing['children'])) {
        $item['children'] = $existing['children'];
    }

    $navigation[$index] = $item;
    return $navigation;
}

/**
 * @param list<array<string, mixed>> $navigation
 * @return list<array<string, mixed>>
 */
function admin_nav_patch_child(array $navigation, int $parentIndex, string $childIndex, array $post): array
{
    if (!isset($navigation[$parentIndex])) {
        throw new RuntimeException('ไม่พบเมนูหลัก');
    }
    $children = $navigation[$parentIndex]['children'] ?? [];
    $label = trim((string) ($post['label'] ?? ''));
    $href = trim((string) ($post['href'] ?? ''));
    if ($label === '' || $href === '') {
        throw new RuntimeException('กรุณากรอกชื่อและลิงก์เมนูย่อย');
    }

    $child = ['label' => $label, 'href' => $href];
    $cat = trim((string) ($post['category'] ?? ''));
    if ($cat !== '') {
        $child['category'] = $cat;
    }

    if ($childIndex === 'new') {
        $children[] = $child;
    } else {
        $childIndexInt = (int) $childIndex;
        if (!isset($children[$childIndexInt])) {
            throw new RuntimeException('ไม่พบเมนูย่อย');
        }
        $children[$childIndexInt] = $child;
    }

    $navigation[$parentIndex]['children'] = array_values($children);
    return $navigation;
}

/**
 * @param list<array<string, mixed>> $navigation
 * @return list<array<string, mixed>>
 */
function admin_nav_delete_child(array $navigation, int $parentIndex, int $childIndex): array
{
    if (!isset($navigation[$parentIndex]['children'][$childIndex])) {
        throw new RuntimeException('ไม่พบเมนูย่อย');
    }
    array_splice($navigation[$parentIndex]['children'], $childIndex, 1);
    $navigation[$parentIndex]['children'] = array_values($navigation[$parentIndex]['children']);
    if ($navigation[$parentIndex]['children'] === []) {
        unset($navigation[$parentIndex]['children']);
    }
    return $navigation;
}

/**
 * @param list<array<string, mixed>> $navigation
 * @param list<int|string> $order
 * @return list<array<string, mixed>>
 */
function admin_nav_reorder(array $navigation, array $order): array
{
    $new = [];
    foreach ($order as $idx) {
        $idx = (int) $idx;
        if (isset($navigation[$idx])) {
            $new[] = $navigation[$idx];
        }
    }
    if (count($new) !== count($navigation)) {
        throw new RuntimeException('ลำดับเมนูไม่ถูกต้อง');
    }
    return $new;
}

/**
 * @param list<array<string, mixed>> $navigation
 * @param list<int|string> $order
 * @return list<array<string, mixed>>
 */
function admin_nav_reorder_children(array $navigation, int $parentIndex, array $order): array
{
    if (!isset($navigation[$parentIndex]['children'])) {
        throw new RuntimeException('ไม่พบเมนูย่อย');
    }
    $children = $navigation[$parentIndex]['children'];
    $new = [];
    foreach ($order as $idx) {
        $idx = (int) $idx;
        if (isset($children[$idx])) {
            $new[] = $children[$idx];
        }
    }
    if (count($new) !== count($children)) {
        throw new RuntimeException('ลำดับเมนูย่อยไม่ถูกต้อง');
    }
    $navigation[$parentIndex]['children'] = $new;
    return $navigation;
}

function admin_apply_site_footer_post(array $data): array
{
    $data['footer'] = admin_normalize_footer($data['footer'] ?? []);
    $data['footer']['tagline'] = admin_post('footer_tagline');
    return $data;
}

function admin_default_footer(): array
{
    return [
        'tagline' => 'ที่ปรึกษาทางการเงินและประกันชีวิต · สาขานครปฐม',
        'topCta' => [
            ['label' => 'ติดต่อสอบถาม', 'href' => 'contact.html', 'variant' => 'white', 'visible' => true],
            ['label' => 'โทร 085-292-5320', 'href' => 'tel:0852925320', 'variant' => 'outline', 'visible' => true],
        ],
        'columns' => [
            [
                'id' => 'main',
                'title' => 'สำนักงานตัวแทน',
                'wide' => true,
                'links' => [
                    ['label' => 'หน้าหลัก', 'href' => 'index.html', 'visible' => true],
                    ['label' => 'เกี่ยวกับเรา', 'href' => 'about.html', 'visible' => true],
                    ['label' => 'แผนประกัน', 'href' => 'plans.html', 'visible' => true],
                    ['label' => 'บทความ / ผลิตภัณฑ์', 'href' => 'products.html', 'visible' => true],
                    ['label' => 'แนะนำอาชีพ', 'href' => 'career.html', 'visible' => true],
                    ['label' => 'ข่าวและกิจกรรม', 'href' => 'news.html', 'visible' => true],
                    ['label' => 'รีวิวเคลม', 'href' => 'claim-reviews.html', 'visible' => true],
                    ['label' => 'ติดต่อสอบถาม', 'href' => 'contact.html', 'visible' => true],
                ],
            ],
            [
                'id' => 'plans',
                'title' => 'แผนประกันแนะนำ',
                'moreLink' => ['label' => 'ดูแผนทั้งหมด →', 'href' => 'plans.html', 'visible' => true],
                'links' => [
                    ['label' => 'ลดหย่อนภาษี แบบสั้น', 'href' => 'plans/tax-saving.html', 'visible' => true],
                    ['label' => 'ไลฟ์เวิร์ส เวลท์ ฟิต 99/99', 'href' => 'plans/life-wealth-fit-99-99.html', 'visible' => true],
                    ['label' => 'สุขภาพ วัยทำงาน', 'href' => 'plans/health-working.html', 'visible' => true],
                    ['label' => 'INFINITE', 'href' => 'plans/infinite.html', 'visible' => true],
                    ['label' => 'เลกาซี ฟิต รีไทร์ 99/10', 'href' => 'plans/legacy-fit-retire.html', 'visible' => true],
                    ['label' => 'ยูนิเวอร์แซลไลฟ์', 'href' => 'plans/universal-life.html', 'visible' => true],
                ],
            ],
            [
                'id' => 'services',
                'title' => 'สนใจบริการ',
                'links' => [
                    ['label' => 'สนใจทำประกันชีวิต', 'href' => 'contact.html?topic=insurance', 'visible' => true],
                    ['label' => 'สนใจเป็นตัวแทน', 'href' => 'contact.html?topic=agent', 'visible' => true],
                    ['label' => 'ติดต่อสอบถามทั่วไป', 'href' => 'contact.html', 'visible' => true],
                    ['label' => 'เกียรติประวัติ MDRT', 'href' => 'about.html#overview', 'visible' => true],
                ],
            ],
            [
                'id' => 'customer',
                'title' => 'บริการลูกค้าไทยประกันชีวิต',
                'links' => [
                    ['label' => 'สิทธิพิเศษ', 'href' => 'https://www.thailife.com/th/service/customer', 'visible' => true, 'external' => true],
                    ['label' => 'ไทยประกันชีวิต iService', 'href' => 'https://www.thailife.com', 'visible' => true, 'external' => true],
                    ['label' => 'แคร์เซ็นเตอร์ (CSC)', 'href' => 'https://www.thailife.com', 'visible' => true, 'external' => true],
                    ['label' => 'ฮอตไลน์ 1124', 'href' => 'tel:1124', 'visible' => true],
                    ['label' => 'เมดิแคร์ / ฮอตเคลม', 'href' => 'https://www.thailife.com', 'visible' => true, 'external' => true],
                    ['label' => 'โรงพยาบาลคู่สัญญา', 'href' => 'https://www.thailife.com', 'visible' => true, 'external' => true],
                ],
            ],
            [
                'id' => 'agent',
                'title' => 'บริการตัวแทน',
                'links' => [
                    ['label' => 'นักขายดิจิทัล (Digital Agent)', 'href' => 'https://www.thailife.com', 'visible' => true, 'external' => true],
                    ['label' => 'Digital Office ต้นฉบับ', 'href' => 'https://digitaloffices.thailife.com/worachat.tot', 'visible' => true, 'external' => true],
                    ['label' => 'สมัครเป็นตัวแทน', 'href' => 'career.html', 'visible' => true],
                ],
            ],
            [
                'id' => 'contact',
                'title' => 'ติดต่อตัวแทน',
                'type' => 'agent',
            ],
        ],
        'bottom' => [
            'copyright' => 'สงวนสิทธิ์ © {year} บริษัท ไทยประกันชีวิต จำกัด (มหาชน)',
            'links' => [
                ['label' => 'นโยบายส่วนบุคคล', 'href' => 'https://www.thailife.com/th/privacy', 'visible' => true, 'external' => true],
                ['label' => 'thailife.com', 'href' => 'https://www.thailife.com', 'visible' => true, 'external' => true],
                ['label' => 'Digital Office', 'href' => 'https://digitaloffices.thailife.com/worachat.tot', 'visible' => true, 'external' => true],
            ],
        ],
    ];
}

function admin_normalize_footer(array $footer): array
{
    $default = admin_default_footer();
    if (!isset($footer['columns']) || !is_array($footer['columns']) || $footer['columns'] === []) {
        $planLinks = $footer['planLinks'] ?? [];
        $footer = array_replace_recursive($default, array_filter($footer, static fn($v) => $v !== null));
        if ($planLinks !== []) {
            foreach ($footer['columns'] as &$column) {
                if (($column['id'] ?? '') === 'plans') {
                    $column['links'] = [];
                    foreach ($planLinks as $link) {
                        $column['links'][] = [
                            'label' => $link['label'] ?? '',
                            'href' => $link['href'] ?? '',
                            'visible' => $link['visible'] ?? true,
                        ];
                    }
                }
            }
            unset($column);
        }
        unset($footer['planLinks']);
    }
    if (!isset($footer['topCta'])) {
        $footer['topCta'] = $default['topCta'];
    }
    if (!isset($footer['bottom'])) {
        $footer['bottom'] = $default['bottom'];
    }
    if (!isset($footer['tagline']) || $footer['tagline'] === '') {
        $footer['tagline'] = $default['tagline'];
    }
    return $footer;
}

function admin_default_contact_dock(): array
{
    return [
        'enabled' => true,
        'items' => [
            ['label' => 'โทร', 'href' => 'tel:0852925320', 'icon' => 'phone', 'color' => '#015fd9', 'visible' => true],
            ['label' => 'แอดไลน์', 'href' => 'contact.html', 'icon' => 'message-circle', 'color' => '#06c755', 'visible' => true],
            ['label' => 'ใบเสนอเบี้ย', 'href' => 'contact.html?topic=insurance', 'icon' => 'file-text', 'color' => '#38bdf8', 'visible' => true],
        ],
    ];
}

function admin_normalize_contact_dock(array $dock): array
{
    $default = admin_default_contact_dock();
    if (!isset($dock['items']) || !is_array($dock['items']) || $dock['items'] === []) {
        $dock['items'] = $default['items'];
    }
    if (!isset($dock['enabled'])) {
        $dock['enabled'] = $default['enabled'];
    }
    $dock['items'] = array_map(
        static fn(array $item): array => admin_normalize_contact_item($item),
        $dock['items']
    );
    return $dock;
}

function admin_contact_dock_save_item(array $dock, string $index, array $post): array
{
    $dock = admin_normalize_contact_dock($dock);
    $isNew = $index === 'new';
    $indexInt = $isNew ? -1 : (int) $index;
    $icons = array_keys(admin_contact_icon_options());
    $icon = trim((string) ($post['icon'] ?? ''));
    if (!in_array($icon, $icons, true)) {
        $icon = 'message-circle';
    }
    $item = [
        'label' => trim((string) ($post['label'] ?? '')),
        'href' => trim((string) ($post['href'] ?? '')),
        'icon' => $icon,
        'color' => admin_normalize_hex_color((string) ($post['color'] ?? '#015fd9')),
        'visible' => ($post['visible'] ?? '') === '1',
    ];
    if ($item['label'] === '' || $item['href'] === '') {
        throw new RuntimeException('กรุณากรอกชื่อและลิงก์');
    }
    if ($isNew) {
        $dock['items'][] = $item;
    } elseif (isset($dock['items'][$indexInt])) {
        $dock['items'][$indexInt] = $item;
    } else {
        throw new RuntimeException('ไม่พบรายการ');
    }
    return $dock;
}

function admin_contact_dock_reorder(array $dock, array $order): array
{
    $dock = admin_normalize_contact_dock($dock);
    $dock['items'] = admin_reorder_list_by_indices($dock['items'], $order);
    return $dock;
}

function admin_default_social_links(): array
{
    return [
        ['label' => 'Facebook', 'href' => '#', 'icon' => 'facebook', 'color' => '#1877f2', 'visible' => true],
        ['label' => 'Line', 'href' => '#', 'icon' => 'message-circle', 'color' => '#06c755', 'visible' => true],
        ['label' => 'Email', 'href' => 'mailto:contact@example.com', 'icon' => 'mail', 'color' => '#015fd9', 'visible' => true],
    ];
}

function admin_normalize_social(array $social): array
{
    if (isset($social['links']) && is_array($social['links']) && $social['links'] !== []) {
        return [
            'links' => array_values(array_map(
                static fn(array $item): array => admin_normalize_contact_item($item),
                $social['links']
            )),
        ];
    }

    $links = [];
    if (!empty($social['facebook'])) {
        $links[] = [
            'label' => 'Facebook',
            'href' => (string) $social['facebook'],
            'icon' => 'facebook',
            'style' => 'facebook',
            'visible' => true,
        ];
    }
    if (!empty($social['line'])) {
        $links[] = [
            'label' => 'Line',
            'href' => (string) $social['line'],
            'icon' => 'message-circle',
            'style' => 'line',
            'visible' => true,
        ];
    }
    if (!empty($social['email'])) {
        $email = (string) $social['email'];
        $href = str_starts_with($email, 'mailto:') ? $email : 'mailto:' . $email;
        $links[] = [
            'label' => 'Email',
            'href' => $href,
            'icon' => 'mail',
            'style' => 'email',
            'visible' => true,
        ];
    }
    if ($links === []) {
        $links = admin_default_social_links();
    }

    return [
        'links' => array_values(array_map(
            static fn(array $item): array => admin_normalize_contact_item($item),
            $links
        )),
    ];
}

function admin_social_save_link(array $social, string $index, array $post): array
{
    $social = admin_normalize_social($social);
    $isNew = $index === 'new';
    $indexInt = $isNew ? -1 : (int) $index;
    $icons = array_keys(admin_contact_icon_options());
    $icon = trim((string) ($post['icon'] ?? ''));
    if (!in_array($icon, $icons, true)) {
        $icon = 'message-circle';
    }
    $item = [
        'label' => trim((string) ($post['label'] ?? '')),
        'href' => trim((string) ($post['href'] ?? '')),
        'icon' => $icon,
        'color' => admin_normalize_hex_color((string) ($post['color'] ?? '#015fd9')),
        'visible' => ($post['visible'] ?? '') === '1',
    ];
    if ($item['label'] === '' || $item['href'] === '') {
        throw new RuntimeException('กรุณากรอกชื่อและลิงก์');
    }
    if ($isNew) {
        $social['links'][] = $item;
    } elseif (isset($social['links'][$indexInt])) {
        $social['links'][$indexInt] = $item;
    } else {
        throw new RuntimeException('ไม่พบรายการ');
    }
    return $social;
}

function admin_social_reorder_links(array $social, array $order): array
{
    $social = admin_normalize_social($social);
    $social['links'] = admin_reorder_list_by_indices($social['links'], $order);
    return $social;
}

function admin_normalize_agent_contacts(array $agent): array
{
    if (!isset($agent['extraContacts']) || !is_array($agent['extraContacts'])) {
        $agent['extraContacts'] = [];
    }
    return $agent;
}

function admin_agent_contact_save_item(array $agent, string $index, array $post): array
{
    $agent = admin_normalize_agent_contacts($agent);
    $isNew = $index === 'new';
    $indexInt = $isNew ? -1 : (int) $index;
    $item = [
        'label' => trim((string) ($post['label'] ?? '')),
        'text' => trim((string) ($post['text'] ?? '')),
        'href' => trim((string) ($post['href'] ?? '')),
        'visible' => ($post['visible'] ?? '') === '1',
    ];
    if ($item['label'] === '' || $item['text'] === '') {
        throw new RuntimeException('กรุณากรอกชื่อหัวข้อและข้อความแสดง');
    }
    if ($isNew) {
        $agent['extraContacts'][] = $item;
    } elseif (isset($agent['extraContacts'][$indexInt])) {
        $agent['extraContacts'][$indexInt] = $item;
    } else {
        throw new RuntimeException('ไม่พบรายการ');
    }
    return $agent;
}

function admin_agent_contact_reorder(array $agent, array $order): array
{
    $agent = admin_normalize_agent_contacts($agent);
    $agent['extraContacts'] = admin_reorder_list_by_indices($agent['extraContacts'], $order);
    return $agent;
}

function admin_post_return_page(string $default): string
{
    $return = trim(admin_post('return', $default));
    if ($return === '' || str_contains($return, '://') || str_contains($return, '..')) {
        return $default;
    }
    return $return;
}

function admin_footer_href_slug(string $href): string
{
    $href = trim($href);
    if ($href === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $href)) {
        return parse_url($href, PHP_URL_PATH) ?: $href;
    }
    return preg_replace('#^\.\./#', '', $href);
}

function admin_footer_link_visible(array $link): bool
{
    return !isset($link['visible']) || (bool) $link['visible'];
}

function admin_footer_preview_url(string $href): string
{
    if (preg_match('#^https?://#i', $href) || str_starts_with($href, 'tel:') || str_starts_with($href, 'mailto:')) {
        return $href;
    }
    return '../' . ltrim($href, '/');
}

function admin_seo_static_pages(): array
{
    return [
        'index.html' => [
            'label' => 'หน้าหลัก',
            'title' => 'Max Thai Life | สำนักงานตัวแทนแม็ก ไทยประกันชีวิต',
            'description' => 'Max Thai Life — ผู้บริหารศูนย์ ไทยประกันชีวิต สาขานครปฐม ที่ปรึกษาทางการเงินและประกันชีวิต',
        ],
        'about.html' => [
            'label' => 'เกี่ยวกับเรา',
            'title' => 'เกี่ยวกับเรา | Max Thai Life',
            'description' => 'เกี่ยวกับ Max Thai Life — ที่ปรึกษาทางการเงินและประกันชีวิต เกียรติประวัติและผลงาน',
        ],
        'contact.html' => [
            'label' => 'ติดต่อ',
            'title' => 'ติดต่อ | Max Thai Life',
            'description' => 'ติดต่อ Max Thai Life — สนใจทำประกันชีวิต สนใจเป็นตัวแทน ติดต่อสอบถาม',
        ],
        'plans.html' => [
            'label' => 'แผนประกัน',
            'title' => 'แผนประกัน | Max Thai Life',
            'description' => 'แผนประกันชีวิตและสุขภาพ ไทยประกันชีวิต — ออม เกษียณ ลดหย่อนภาษี สุขภาพ คุ้มครองชีวิต',
        ],
        'products.html' => [
            'label' => 'บทความ',
            'title' => 'ผลิตภัณฑ์และบริการ | Max Thai Life',
            'description' => 'ผลิตภัณฑ์และบริการประกันชีวิต ไทยประกันชีวิต — Max Thai Life',
        ],
        'career.html' => [
            'label' => 'แนะนำอาชีพ',
            'title' => 'แนะนำอาชีพ | Max Thai Life',
            'description' => 'แนะนำอาชีพตัวแทนประกันชีวิต ไทยประกันชีวิต — Max Thai Life ศูนย์นครปฐม',
        ],
        'news.html' => [
            'label' => 'ข่าว/กิจกรรม',
            'title' => 'ข่าว/กิจกรรม | Max Thai Life',
            'description' => 'ข่าวสารและกิจกรรม ไทยประกันชีวิต — Max Thai Life ศูนย์นครปฐม',
        ],
        'claim-reviews.html' => [
            'label' => 'รีวิวเคลม',
            'title' => 'รีวิวเคลม | Max Thai Life',
            'description' => 'รีวิวเคลมประกันชีวิตและสุขภาพจากลูกค้าจริง — Max Thai Life ศูนย์นครปฐม',
        ],
    ];
}

function admin_normalize_meta(array $meta, array $brand = []): array
{
    $defaults = [
        'description' => 'Max Thai Life — ผู้บริหารศูนย์ ไทยประกันชีวิต สาขานครปฐม ที่ปรึกษาทางการเงินและประกันชีวิต',
        'ogImage' => $brand['logo'] ?? 'images/logo/LOGO-THAILIFE.png',
        'ogTitle' => ($brand['name'] ?? 'Max Thai Life') . ' | ' . ($brand['sub'] ?? 'สำนักงานตัวแทนแม็ก ไทยประกันชีวิต'),
        'ogDescription' => '',
        'siteUrl' => '',
        'shareAssetBaseUrl' => '',
        'shareAssetFallbackUrl' => 'https://goddarkmarketing.github.io/Max-Thai-Life',
        'titleSuffix' => '| Max Thai Life',
        'analyticsId' => '',
        'googleSiteVerification' => '',
        'localBusiness' => [
            'enabled' => true,
            'address' => 'จ.นครปฐม',
            'areaServed' => 'นครปฐม',
            'googleBusinessUrl' => '',
        ],
        'pages' => [],
    ];

    $meta = array_merge($defaults, $meta);
    $meta['localBusiness'] = array_merge($defaults['localBusiness'], $meta['localBusiness'] ?? []);

    foreach (admin_seo_static_pages() as $slug => $pageDefaults) {
        $existing = $meta['pages'][$slug] ?? [];
        $meta['pages'][$slug] = [
            'title' => (string) ($existing['title'] ?? $pageDefaults['title']),
            'description' => (string) ($existing['description'] ?? $pageDefaults['description']),
            'indexable' => !isset($existing['indexable']) || (bool) $existing['indexable'],
        ];
    }

    if ($meta['ogDescription'] === '') {
        $meta['ogDescription'] = (string) $meta['description'];
    }

    return $meta;
}

function admin_plan_categories(): array
{
    return [
        ['id' => 'savings', 'label' => 'ออมทรัพย์', 'icon' => 'piggy-bank'],
        ['id' => 'protect', 'label' => 'คุ้มครองชีวิต', 'icon' => 'shield-check'],
        ['id' => 'health', 'label' => 'ประกันสุขภาพ', 'icon' => 'heart-pulse'],
        ['id' => 'rider', 'label' => 'สัญญาเพิ่มเติม', 'icon' => 'file-plus-2'],
        ['id' => 'pension', 'label' => 'บำนาญ/เกษียณ', 'icon' => 'armchair'],
        ['id' => 'invest', 'label' => 'ลงทุน/Life Verse', 'icon' => 'trending-up'],
    ];
}

function admin_plan_category_icon(?string $categoryId = null): string
{
    if ($categoryId === null || $categoryId === '') {
        return 'layout-grid';
    }
    foreach (admin_plan_categories() as $cat) {
        if ($cat['id'] === $categoryId) {
            return (string) ($cat['icon'] ?? 'circle');
        }
    }
    return 'circle';
}

function admin_plan_category_label(string $categoryId): string
{
    foreach (admin_plan_categories() as $cat) {
        if ($cat['id'] === $categoryId) {
            return $cat['label'];
        }
    }
    return $categoryId;
}

function admin_plans_list_url(?string $category = null): string
{
    $category = trim((string) $category);
    return $category !== '' ? 'plans-list.php?category=' . rawurlencode($category) : 'plans-list.php';
}

function admin_plans_active_nav(?string $category = null): string
{
    return admin_plans_list_url($category);
}

function admin_plan_nav_children(): array
{
    $children = [];
    foreach (admin_plan_categories() as $cat) {
        $children[] = [
            'href' => admin_plans_list_url($cat['id']),
            'label' => $cat['label'],
        ];
    }
    return $children;
}

function admin_plan_detail_for_slug(string $slug): ?array
{
    $details = json_read('plans-detail.json');
    $detail = $details['items'][$slug] ?? null;
    return is_array($detail) ? $detail : null;
}

function admin_plan_uses_richtext(string $slug): bool
{
    $detail = admin_plan_detail_for_slug($slug);
    return is_array($detail) && ($detail['editor'] ?? '') === 'richtext';
}

function admin_plan_edit_content_url(string $slug): string
{
    return admin_plan_uses_richtext($slug)
        ? 'plan-richtext.php?slug=' . rawurlencode($slug)
        : 'plan-visual.php?slug=' . rawurlencode($slug);
}

function admin_plan_card_bootstrap_body_html(array $card): string
{
    $html = '';
    $desc = trim(str_replace(["\r\n", "\r"], "\n", (string) ($card['desc'] ?? '')));
    if ($desc !== '') {
        $html .= '<h2>ภาพรวมแผน</h2>';
        foreach (preg_split('/\n+/', $desc) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $html .= '<p>' . $line . '</p>';
            }
        }
    }

    $features = $card['features'] ?? [];
    if (is_array($features) && $features !== []) {
        $html .= '<h2>จุดเด่นและผลประโยชน์</h2><ul>';
        foreach ($features as $feature) {
            $feature = trim((string) $feature);
            if ($feature !== '') {
                $html .= '<li>' . $feature . '</li>';
            }
        }
        $html .= '</ul>';
    }

    return trim($html) !== '' ? $html : '<p></p>';
}

function admin_plan_init_richtext_detail(string $slug, array $card): void
{
    $details = json_read('plans-detail.json');
    if (!isset($details['items']) || !is_array($details['items'])) {
        $details['items'] = [];
    }

    $title = (string) ($card['title'] ?? $slug);
    $existingBody = (string) ($details['items'][$slug]['bodyHtml'] ?? '');
    $bodyHtml = trim($existingBody) !== '' ? $existingBody : admin_plan_card_bootstrap_body_html($card);
    $details['items'][$slug] = array_merge($details['items'][$slug] ?? [], [
        'editor' => 'richtext',
        'bodyHtml' => $bodyHtml,
        'title' => $title,
        'breadcrumb' => $title,
        'description' => (string) ($card['desc'] ?? ''),
        'heroLead' => (string) ($card['desc'] ?? ''),
        'image' => (string) ($card['image'] ?? ''),
        'ctaTitle' => (string) ($details['items'][$slug]['ctaTitle'] ?? ('สนใจ' . ($title !== '' ? ' ' . $title : 'แผนนี้') . '?')),
        'ctaLead' => (string) ($details['items'][$slug]['ctaLead'] ?? 'ขอใบเสนอเบี้ยและปรึกษาฟรี'),
        'visible' => ($details['items'][$slug]['visible'] ?? true) !== false,
    ]);
    json_write('plans-detail.json', $details);
}

function admin_plan_sync_missing_details(): int
{
    $plans = json_read('plans.json');
    $details = json_read('plans-detail.json');
    $items = $details['items'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    $created = 0;
    $seen = [];
    foreach ($plans['items'] ?? [] as $card) {
        if (!is_array($card)) {
            continue;
        }
        $href = (string) ($card['href'] ?? '');
        $slug = preg_replace('#^plans/|\.html$#', '', $href);
        if ($slug === '' || isset($seen[$slug])) {
            continue;
        }
        $seen[$slug] = true;
        if (isset($items[$slug])) {
            continue;
        }
        admin_plan_init_richtext_detail($slug, $card);
        $created++;
    }

    return $created;
}

function admin_plan_default_theme_for_category(string $category): string
{
    return match ($category) {
        'protect' => 'protect',
        'health' => 'health',
        'rider' => 'infinite',
        'pension' => 'retire',
        'invest' => 'universal',
        default => 'tax',
    };
}

function admin_save_site_footer(array $footer): void
{
    $data = json_read('site.json');
    $data['footer'] = admin_normalize_footer($footer);
    json_write('site.json', $data);
}

function admin_footer_save_item(array $footer, string $section, int $col, string $index, array $post): array
{
    $isNew = $index === 'new';
    $indexInt = $isNew ? -1 : (int) $index;

    if ($section === 'settings') {
        $footer['tagline'] = trim($post['tagline'] ?? '');
        $footer['bottom']['copyright'] = trim($post['copyright'] ?? '');
    } elseif ($section === 'column') {
        if (!isset($footer['columns'][$col])) {
            throw new RuntimeException('ไม่พบคอลัมน์');
        }
        $footer['columns'][$col]['title'] = trim($post['title'] ?? '');
        $footer['columns'][$col]['wide'] = ($post['wide'] ?? '') === '1';
        if (($footer['columns'][$col]['id'] ?? '') === 'plans') {
            $footer['columns'][$col]['moreLink'] = [
                'label' => trim($post['more_label'] ?? ''),
                'href' => trim($post['more_href'] ?? ''),
                'visible' => ($post['more_visible'] ?? '') === '1',
            ];
        }
    } elseif ($section === 'topCta') {
        $item = [
            'label' => trim($post['label'] ?? ''),
            'href' => trim($post['href'] ?? ''),
            'variant' => ($post['variant'] ?? 'white') === 'outline' ? 'outline' : 'white',
            'visible' => ($post['visible'] ?? '') === '1',
        ];
        if ($isNew) {
            $footer['topCta'][] = $item;
        } elseif (isset($footer['topCta'][$indexInt])) {
            $footer['topCta'][$indexInt] = $item;
        } else {
            throw new RuntimeException('ไม่พบรายการ');
        }
    } elseif ($section === 'bottom') {
        $item = [
            'label' => trim($post['label'] ?? ''),
            'href' => trim($post['href'] ?? ''),
            'visible' => ($post['visible'] ?? '') === '1',
            'external' => ($post['external'] ?? '') === '1',
        ];
        if ($isNew) {
            $footer['bottom']['links'][] = $item;
        } elseif (isset($footer['bottom']['links'][$indexInt])) {
            $footer['bottom']['links'][$indexInt] = $item;
        } else {
            throw new RuntimeException('ไม่พบรายการ');
        }
    } elseif ($section === 'link') {
        if (!isset($footer['columns'][$col])) {
            throw new RuntimeException('ไม่พบคอลัมน์');
        }
        $item = [
            'label' => trim($post['label'] ?? ''),
            'href' => trim($post['href'] ?? ''),
            'visible' => ($post['visible'] ?? '') === '1',
            'external' => ($post['external'] ?? '') === '1',
        ];
        if ($isNew) {
            $footer['columns'][$col]['links'][] = $item;
        } elseif (isset($footer['columns'][$col]['links'][$indexInt])) {
            $footer['columns'][$col]['links'][$indexInt] = $item;
        } else {
            throw new RuntimeException('ไม่พบรายการ');
        }
    } elseif ($section === 'moreLink') {
        if (!isset($footer['columns'][$col])) {
            throw new RuntimeException('ไม่พบคอลัมน์');
        }
        $footer['columns'][$col]['moreLink'] = [
            'label' => trim($post['label'] ?? ''),
            'href' => trim($post['href'] ?? ''),
            'visible' => ($post['visible'] ?? '') === '1',
        ];
    } else {
        throw new RuntimeException('ไม่รองรับประเภทรายการ');
    }

    return admin_normalize_footer($footer);
}

function admin_apply_site_seo_post(array $data): array
{
    $brand = $data['brand'] ?? [];
    $meta = admin_normalize_meta($data['meta'] ?? [], $brand);

    $meta['siteUrl'] = rtrim(trim(admin_post('meta_site_url')), '/');
    $meta['shareAssetBaseUrl'] = rtrim(trim(admin_post('meta_share_asset_base_url')), '/');
    $meta['shareAssetFallbackUrl'] = rtrim(trim(admin_post('meta_share_asset_fallback_url')), '/');
    $meta['titleSuffix'] = trim(admin_post('meta_title_suffix', '| Max Thai Life'));
    $meta['description'] = trim(admin_post('meta_description'));
    $meta['ogTitle'] = trim(admin_post('meta_og_title'));
    $meta['ogDescription'] = trim(admin_post('meta_og_description'));
    $meta['ogImage'] = admin_post('meta_og_image');
    $meta['analyticsId'] = trim(admin_post('meta_analytics_id'));
    $meta['googleSiteVerification'] = trim(admin_post('meta_google_verification'));

    $meta['localBusiness']['enabled'] = admin_post('local_enabled') === '1';
    $meta['localBusiness']['address'] = trim(admin_post('local_address'));
    $meta['localBusiness']['areaServed'] = trim(admin_post('local_area'));
    $meta['localBusiness']['googleBusinessUrl'] = trim(admin_post('local_gbp_url'));

    foreach (admin_seo_static_pages() as $slug => $defaults) {
        $title = trim(admin_post('page_title_' . str_replace('.', '_', $slug)));
        $description = trim(admin_post('page_desc_' . str_replace('.', '_', $slug)));
        $indexable = admin_post('page_index_' . str_replace('.', '_', $slug)) === '1';

        $meta['pages'][$slug] = [
            'title' => $title !== '' ? $title : $defaults['title'],
            'description' => $description !== '' ? $description : $defaults['description'],
            'indexable' => $indexable,
        ];
    }

    if ($meta['ogDescription'] === '') {
        $meta['ogDescription'] = $meta['description'];
    }

    $data['meta'] = admin_normalize_meta($meta, $brand);
    return $data;
}

function admin_inquiry_topic_label(string $topic): string
{
    $map = [
        'insurance' => 'สนใจทำประกันชีวิต',
        'quote' => 'ขอใบเสนอเบี้ย',
        'agent' => 'สนใจเป็นตัวแทน',
        'inquiry' => 'ติดต่อสอบถาม',
    ];
    return $map[$topic] ?? $topic;
}

function admin_load_leads(): array
{
    return json_read('leads.json');
}

function admin_count_new_leads(): int
{
    $items = admin_load_leads()['items'] ?? [];
    $n = 0;
    foreach ($items as $item) {
        if (($item['status'] ?? 'new') === 'new') {
            $n++;
        }
    }
    return $n;
}

function admin_save_lead(array $lead): void
{
    $data = admin_load_leads();
    $items = $data['items'] ?? [];
    array_unshift($items, $lead);
    if (count($items) > 500) {
        $items = array_slice($items, 0, 500);
    }
    json_write('leads.json', ['items' => $items]);
}

function admin_update_lead(string $id, string $status): void
{
    $data = admin_load_leads();
    $items = $data['items'] ?? [];
    $found = false;
    foreach ($items as &$item) {
        if (($item['id'] ?? '') === $id) {
            $item['status'] = $status;
            $found = true;
            break;
        }
    }
    unset($item);
    if (!$found) {
        throw new RuntimeException('ไม่พบข้อความ');
    }
    json_write('leads.json', ['items' => $items]);
}

function admin_delete_lead(string $id): void
{
    $data = admin_load_leads();
    $items = array_values(array_filter($data['items'] ?? [], static fn ($item) => ($item['id'] ?? '') !== $id));
    json_write('leads.json', ['items' => $items]);
}

function admin_log_publish(): void
{
    $data = json_read('publish-log.json');
    $entries = $data['entries'] ?? [];
    array_unshift($entries, [
        'at' => date('c'),
        'user' => $_SESSION['admin_user'] ?? 'admin',
    ]);
    if (count($entries) > 50) {
        $entries = array_slice($entries, 0, 50);
    }
    json_write('publish-log.json', ['entries' => $entries]);
}

function admin_list_publish_log(): array
{
    return json_read('publish-log.json')['entries'] ?? [];
}

function admin_format_datetime_th(string $iso): string
{
    $ts = strtotime($iso);
    if ($ts === false) {
        return $iso;
    }
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return sprintf(
        '%d %s %d · %s:%s',
        (int) date('j', $ts),
        $months[(int) date('n', $ts)] ?? '',
        (int) date('Y', $ts),
        date('H', $ts),
        date('i', $ts)
    );
}
