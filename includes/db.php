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
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
        ]);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '1226') || str_contains($msg, 'max_connections')) {
            db_connection_limit_exit();
        }
        throw $e;
    }
    ensure_schema($pdo);
    return $pdo;
}

function db_connection_limit_exit(): void
{
    $text = 'تجاوز حساب قاعدة البيانات حد الاتصالات لهذه الساعة. انتظر حتى تُعاد التعبئة من لوحة الاستضافة، أو ارفع max_connections_per_hour.';
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $text], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>حروف</title></head><body style="font-family:sans-serif;padding:2rem">';
    echo '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    exit;
}

function ensure_schema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $tables = array_map('strval', $tables);
    $needed = ['rooms', 'players', 'submissions', 'vocab_words', 'letter_pools', 'round_sets', 'round_set_words'];
    $have = array_map('strtolower', $tables);
    $missing = false;
    foreach ($needed as $table) {
        if (!in_array(strtolower($table), $have, true)) {
            $missing = true;
            break;
        }
    }
    if ($missing) {
        $sql = file_get_contents(HOROF_ROOT . '/sql/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('ملف القاعدة sql/schema.sql غير موجود');
        }
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }
    ensure_column($pdo, 'rooms', 'set_id', 'INT UNSIGNED NULL');
    ensure_column($pdo, 'rooms', 'used_sets', 'VARCHAR(255) NULL');
    $ready = true;
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?');
    $stmt->execute([$column]);
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '', $table) . '` ADD `' . str_replace('`', '', $column) . '` ' . $definition);
    }
}
