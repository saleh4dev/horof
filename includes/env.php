<?php

declare(strict_types=1);

function load_env_file(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $existing = getenv($key);
        if ($existing !== false && $existing !== '') {
            $_ENV[$key] = $existing;
            continue;
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

function db_config(): array
{
    $url = env('DATABASE_URL');
    if ($url !== '') {
        $parts = parse_url($url);
        if (is_array($parts)) {
            return [
                'host' => (string) ($parts['host'] ?? 'localhost'),
                'port' => (string) ($parts['port'] ?? '3306'),
                'name' => ltrim((string) ($parts['path'] ?? ''), '/'),
                'user' => urldecode((string) ($parts['user'] ?? '')),
                'pass' => urldecode((string) ($parts['pass'] ?? '')),
            ];
        }
    }
    return [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', env('MYSQL_DATABASE', 'horof')),
        'user' => env('DB_USER', env('MYSQL_USER', '')),
        'pass' => env('DB_PASSWORD', env('MYSQL_PASSWORD', env('DB_PASS', ''))),
    ];
}

function db_is_configured(): bool
{
    $cfg = db_config();
    return $cfg['name'] !== '' && $cfg['user'] !== '';
}
