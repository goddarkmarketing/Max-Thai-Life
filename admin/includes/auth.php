<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_is_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function admin_login(string $user, string $pass): bool
{
    $cfg = admin_load_admin_config();
    $adminUser = $cfg['user'] ?? ADMIN_USER;
    $hash = $cfg['passHash'] ?? ADMIN_PASS_HASH;
    if ($user !== $adminUser || !password_verify($pass, $hash)) {
        return false;
    }
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = $user;
    return true;
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function admin_verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(admin_csrf_token(), $token);
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function admin_get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
