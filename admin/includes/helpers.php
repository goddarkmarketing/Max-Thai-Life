<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

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

    admin_prune_backups();

    return $id;
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
        'images/cover แผนประกัน',
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
        'images/cover แผนประกัน' => 'แผนประกัน',
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
    return [
        ['label' => 'แผนประกันทั้งหมด', 'href' => 'plans.html'],
        ['label' => 'ออมทรัพย์', 'href' => 'plans.html?category=savings#savings', 'category' => 'savings'],
        ['label' => 'คุ้มครองชีวิต', 'href' => 'plans.html?category=protect#protect', 'category' => 'protect'],
        ['label' => 'ประกันสุขภาพ', 'href' => 'plans.html?category=health#health', 'category' => 'health'],
        ['label' => 'สัญญาเพิ่มเติม', 'href' => 'plans.html?category=rider#rider', 'category' => 'rider'],
        ['label' => 'บำนาญ/เกษียณ', 'href' => 'plans.html?category=pension#pension', 'category' => 'pension'],
        ['label' => 'ลงทุน/Life Verse', 'href' => 'plans.html?category=invest#invest', 'category' => 'invest'],
    ];
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
    $data['social'] = [
        'facebook' => admin_post('social_facebook'),
        'line' => admin_post('social_line'),
        'email' => admin_post('social_email'),
    ];
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
