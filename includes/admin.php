<?php

declare(strict_types=1);

function admin_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function admin_csrf_token(): string
{
    admin_session_start();
    if (empty($_SESSION['horof_csrf'])) {
        $_SESSION['horof_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['horof_csrf'];
}

function admin_csrf_ok(): bool
{
    $token = (string) ($_POST['csrf'] ?? '');
    return $token !== '' && hash_equals(admin_csrf_token(), $token);
}

function admin_logged_in(): bool
{
    admin_session_start();
    return !empty($_SESSION['horof_admin']);
}

function admin_require(): void
{
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_try_login(string $user, string $pass): bool
{
    $expectedUser = env('ADMIN_USER', 'admin');
    $expectedPass = env('ADMIN_PASSWORD');
    if ($expectedPass === '') {
        return false;
    }
    if (!hash_equals($expectedUser, $user) || !hash_equals($expectedPass, $pass)) {
        return false;
    }
    admin_session_start();
    session_regenerate_id(true);
    $_SESSION['horof_admin'] = 1;
    return true;
}
