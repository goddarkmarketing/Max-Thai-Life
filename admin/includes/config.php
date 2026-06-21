<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__, 2));
define('DATA_PATH', ROOT_PATH . '/data');
define('JS_PATH', ROOT_PATH . '/js');
define('UPLOAD_PATH', ROOT_PATH . '/images/uploads');
define('BACKUP_PATH', DATA_PATH . '/backups');
define('ADMIN_PATH', dirname(__DIR__));

// Change after first login via admin/settings
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); // password: password

define('SESSION_NAME', 'maxthailife_admin');
define('ADMIN_BACKUP_MAX', 15);

date_default_timezone_set('Asia/Bangkok');

if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0755, true);
}
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!is_dir(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0755, true);
}
