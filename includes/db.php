<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = db_config();
    $host = $cfg['host'];
    $port = $cfg['port'] ?: '3306';
    $name = $cfg['name'];
    $user = $cfg['user'];
    $pass = $cfg['pass'];
    $charset = 'utf8mb4';
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET NAMES {$charset} COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET time_zone = '+00:00'");
    ensure_schema($pdo);
    return $pdo;
}

function ensure_schema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $sql = file_get_contents(HOROF_ROOT . '/sql/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('ملف القاعدة sql/schema.sql غير موجود');
    }
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $ready = true;
}
