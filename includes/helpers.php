<?php

declare(strict_types=1);

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return $_POST ?: [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function header_token(string $name): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return trim((string) ($_SERVER[$key] ?? ''));
}

function app_url(string $path = ''): string
{
    $base = trim(env('APP_URL', env('RENDER_EXTERNAL_URL')));
    if ($base === '') {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (str_ends_with($dir, '/api')) {
            $dir = dirname($dir);
        }
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            $dir = '';
        }
        $base = $scheme . '://' . $host . $dir;
    }
    $base = rtrim($base, '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function random_token(): string
{
    return bin2hex(random_bytes(16));
}

function random_code(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clean_name(string $name, int $max = 24): string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    $name = preg_replace('/[^\p{L}\p{N} _.\-]/u', '', $name) ?? '';
    return mb_substr($name, 0, $max);
}

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}
