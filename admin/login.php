<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require HOROF_ROOT . '/includes/admin.php';

admin_session_start();
$error = '';

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string) ($_POST['user'] ?? ''));
    $pass = (string) ($_POST['pass'] ?? '');
    if (env('ADMIN_PASSWORD') === '') {
        $error = 'أضف ADMIN_USER و ADMIN_PASSWORD في متغيرات البيئة ثم أعد المحاولة.';
    } elseif (!admin_try_login($user, $pass)) {
        $error = 'بيانات الدخول غير صحيحة.';
    } else {
        header('Location: index.php');
        exit;
    }
}

$title = 'دخول الإدارة — حروف';
$bodyClass = 'page-admin';
$assetPrefix = '../';
require HOROF_ROOT . '/includes/header.php';
?>
<main class="shell narrow">
    <header class="brand">
        <p class="brand-kicker">لوحة التحكم</p>
        <h1>الإدارة</h1>
        <p class="lead">إدارة الكلمات المقبولة ومجموعات الحروف التي تظهر في الجولات.</p>
    </header>
    <section class="panel">
        <?php if ($error): ?><p class="form-msg"><?= h($error) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <label>اسم المستخدم<input name="user" required autocomplete="username"></label>
            <label>كلمة المرور<input name="pass" type="password" required autocomplete="current-password"></label>
            <button class="btn btn-primary" type="submit">دخول</button>
        </form>
    </section>
</main>
<?php require HOROF_ROOT . '/includes/footer.php'; ?>
