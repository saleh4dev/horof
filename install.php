<?php

declare(strict_types=1);

require __DIR__ . '/includes/env.php';
load_env_file(__DIR__ . '/.env');

$installed = db_is_configured();
$error = '';
$ok = false;

function install_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function write_env_file(array $values): void
{
    $lines = [
        '# بيانات الاتصال بقاعدة البيانات',
        'DB_HOST=' . $values['DB_HOST'],
        'DB_PORT=' . $values['DB_PORT'],
        'DB_NAME=' . $values['DB_NAME'],
        'DB_USER=' . $values['DB_USER'],
        'DB_PASSWORD=' . $values['DB_PASSWORD'],
        'APP_URL=' . $values['APP_URL'],
        '',
    ];
    $written = file_put_contents(__DIR__ . '/.env', implode("\n", $lines));
    if ($written === false) {
        throw new RuntimeException('تعذر كتابة ملف .env — اضبط صلاحية المجلد ثم أعد المحاولة.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $port = trim((string) ($_POST['db_port'] ?? '3306')) ?: '3306';
    $name = trim((string) ($_POST['db_name'] ?? ''));
    $user = trim((string) ($_POST['db_user'] ?? ''));
    $pass = (string) ($_POST['db_pass'] ?? '');
    $base = trim((string) ($_POST['base_url'] ?? ''));

    if ($name === '' || $user === '') {
        $error = 'أدخل اسم قاعدة البيانات واسم المستخدم.';
    } else {
        try {
            if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                throw new RuntimeException('يلزم PHP 8.0 أو أحدث. الإصدار الحالي: ' . PHP_VERSION);
            }
            if (!extension_loaded('pdo_mysql')) {
                throw new RuntimeException('فعّل امتداد PDO MySQL على الاستضافة.');
            }
            if (!extension_loaded('mbstring')) {
                throw new RuntimeException('فعّل امتداد mbstring على الاستضافة.');
            }
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
            if ($sql === false) {
                throw new RuntimeException('ملف القاعدة sql/schema.sql غير موجود.');
            }
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
            write_env_file([
                'DB_HOST' => $host,
                'DB_PORT' => $port,
                'DB_NAME' => $name,
                'DB_USER' => $user,
                'DB_PASSWORD' => $pass,
                'APP_URL' => $base,
            ]);
            $installed = true;
            $ok = true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تثبيت حروف</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<main class="install panel">
    <p class="brand-kicker">التثبيت</p>
    <h1>حروف</h1>
    <?php if ($installed && ($ok || $_SERVER['REQUEST_METHOD'] !== 'POST')): ?>
        <p>الاتصال بقاعدة البيانات جاهز عبر ملف <code>.env</code>.</p>
        <p><a class="btn btn-primary" href="index.php">فتح المسابقة</a></p>
    <?php else: ?>
        <p class="muted">أدخل بيانات MySQL ليتم حفظها في ملف <code>.env</code>. على Render ضع نفس القيم في Environment Variables.</p>
        <?php if ($error): ?><p class="form-msg"><?= install_h($error) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <label>خادم MySQL<input name="db_host" value="localhost" required></label>
            <label>المنفذ<input name="db_port" value="3306" required></label>
            <label>اسم القاعدة<input name="db_name" required placeholder="horof"></label>
            <label>اسم المستخدم<input name="db_user" required></label>
            <label>كلمة المرور<input name="db_pass" type="password"></label>
            <label>رابط الموقع (اختياري)<input name="base_url" placeholder="https://horof.onrender.com"></label>
            <button class="btn btn-primary" type="submit">حفظ .env وإنشاء الجداول</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
