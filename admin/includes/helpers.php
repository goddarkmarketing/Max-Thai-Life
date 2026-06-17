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
        $backupDir = BACKUP_PATH . '/' . date('Y-m-d_H-i-s');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        copy($path, $backupDir . '/' . basename($file));
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

function admin_list_backups(): array
{
    if (!is_dir(BACKUP_PATH)) {
        return [];
    }
    $dirs = glob(BACKUP_PATH . '/*', GLOB_ONLYDIR) ?: [];
    rsort($dirs);
    $out = [];
    foreach ($dirs as $dir) {
        $files = glob($dir . '/*.json') ?: [];
        $id = basename($dir);
        $out[] = [
            'id' => $id,
            'path' => $dir,
            'files' => array_map('basename', $files),
            'count' => count($files),
            'label' => admin_format_backup_datetime($id),
            'mtime' => filemtime($dir) ?: 0,
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
    $id = date('Y-m-d_H-i-s');
    $dir = BACKUP_PATH . '/' . $id;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('สร้างโฟลเดอร์สำรองไม่สำเร็จ');
    }
    $copied = 0;
    foreach (glob(DATA_PATH . '/*.json') ?: [] as $file) {
        if (copy($file, $dir . '/' . basename($file))) {
            $copied++;
        }
    }
    if ($copied === 0) {
        rmdir($dir);
        throw new RuntimeException('ไม่มีไฟล์ JSON ให้สำรอง');
    }
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
    foreach (glob($dir . '/*') ?: [] as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
    rmdir($dir);
}

function admin_backup_file_path(string $backupId, string $file): string
{
    $backupId = basename($backupId);
    $file = basename($file);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $backupId)) {
        throw new InvalidArgumentException('รหัสสำรองไม่ถูกต้อง');
    }
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.json$/', $file)) {
        throw new InvalidArgumentException('ชื่อไฟล์ไม่ถูกต้อง');
    }
    $path = BACKUP_PATH . '/' . $backupId . '/' . $file;
    if (!is_file($path)) {
        throw new RuntimeException('ไม่พบไฟล์');
    }
    return $path;
}

function admin_media_allowed_roots(): array
{
    return [
        'images/uploads',
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
        $file = basename($file);
        $src = $dir . '/' . $file;
        if (!is_file($src)) {
            throw new RuntimeException('ไม่พบไฟล์');
        }
        json_write($file, json_decode(file_get_contents($src) ?: '[]', true) ?: []);
        return;
    }
    foreach (glob($dir . '/*.json') ?: [] as $src) {
        $name = basename($src);
        json_write($name, json_decode(file_get_contents($src) ?: '[]', true) ?: []);
    }
}

function admin_scan_media_files(): array
{
    $roots = [
        'images/uploads' => 'อัปโหลด',
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
    $data['footer']['tagline'] = admin_post('footer_tagline');
    $labels = admin_post_array('footer_label');
    $hrefs = admin_post_array('footer_href');
    $links = [];
    foreach ($labels as $i => $label) {
        $label = trim($label);
        $href = trim($hrefs[$i] ?? '');
        if ($label === '' || $href === '') {
            continue;
        }
        $links[] = ['label' => $label, 'href' => $href];
    }
    $data['footer']['planLinks'] = $links;
    return $data;
}

function admin_apply_site_seo_post(array $data): array
{
    $data['meta']['description'] = admin_post('meta_description');
    $data['meta']['ogImage'] = admin_post('meta_og_image');
    $data['meta']['analyticsId'] = admin_post('meta_analytics_id');
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
