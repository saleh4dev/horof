<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$code = strtoupper(trim((string) ($_GET['c'] ?? '')));
$title = 'انضم للمسابقة — حروف';
$bodyClass = 'page-join';
$scripts = ['assets/js/common.js', 'assets/js/join.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="shell narrow">
    <header class="brand">
        <p class="brand-kicker">الانضمام للمسابقة</p>
        <h1 id="join-room-name">حروف</h1>
        <p class="lead">رمز الغرفة: <strong id="shown-code"><?= h($code !== '' ? $code : '—') ?></strong></p>
    </header>
    <section class="panel">
        <form id="join-form" class="stack">
            <input type="hidden" name="code" value="<?= h($code) ?>">
            <label>
                اسمك في المسابقة
                <input type="text" name="name" maxlength="24" required autocomplete="nickname" placeholder="يظهر على شاشة العرض">
            </label>
            <button type="submit" class="btn btn-primary">دخول</button>
            <p class="form-msg" hidden></p>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
