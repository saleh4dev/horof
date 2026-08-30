<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require HOROF_ROOT . '/includes/admin.php';

admin_require();

$flash = '';
$flashOk = false;
$csrf = admin_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_ok()) {
        $flash = 'انتهت صلاحية الجلسة. أعد المحاولة.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create_set') {
            $result = create_round_set((string) ($_POST['name'] ?? ''), (string) ($_POST['letters'] ?? ''));
            if ($result['ok']) {
                header('Location: set.php?id=' . (int) $result['id']);
                exit;
            }
            $flash = $result['error'];
        } elseif ($action === 'delete_set') {
            delete_round_set((int) ($_POST['id'] ?? 0));
            $flash = 'حُذفت المجموعة.';
            $flashOk = true;
        }
    }
}

$sets = prepared_sets();
$ready = prepared_set_count();

$title = 'لوحة التحكم — حروف';
$bodyClass = 'page-admin';
require HOROF_ROOT . '/includes/header.php';
?>
<main class="admin-layout">
    <header class="host-top">
        <div>
            <p class="brand-kicker">لوحة التحكم</p>
            <h1>جولات المسابقة</h1>
            <p class="muted">أضف مجموعة حروف، ثم أدخل الكلمات التي يمكن استخراجها منها. كل مجموعة جاهزة تُستخدم كجولة.</p>
        </div>
        <a class="btn" href="logout.php">خروج</a>
    </header>
    <?php if ($flash): ?>
        <p class="form-msg <?= $flashOk ? 'ok' : '' ?>"><?= h($flash) ?></p>
    <?php endif; ?>

    <p class="status-line">المجموعات الجاهزة للعب: <strong><?= (int) $ready ?></strong></p>

    <section class="panel">
        <h2>مجموعة حروف جديدة</h2>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="create_set">
            <div class="row-2">
                <label>اسم المجموعة<input name="name" maxlength="40" placeholder="مثال: جولة الكتب"></label>
                <label>الحروف<input name="letters" required maxlength="24" placeholder="ك ت ب ا ر م ن ل"></label>
            </div>
            <button class="btn btn-primary" type="submit">إنشاء ثم إضافة الكلمات</button>
        </form>
    </section>

    <div class="pack-grid">
        <?php foreach ($sets as $set): ?>
            <article class="panel pack-card">
                <h2><?= h($set['name'] !== '' ? $set['name'] : 'مجموعة') ?></h2>
                <div class="letters"><?php foreach (ar_chars($set['letters']) as $ch): ?>
                    <span class="tile"><?= h($ch) ?></span>
                <?php endforeach; ?></div>
                <p class="muted"><?= (int) $set['word_count'] ?> كلمة معتمدة
                    <?php if ((int) $set['word_count'] < 1): ?> — أضف كلمات حتى تُستخدم في الجولات<?php endif; ?>
                </p>
                <div class="controls">
                    <a class="btn btn-primary" href="set.php?id=<?= (int) $set['id'] ?>">إدارة الكلمات</a>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                        <input type="hidden" name="action" value="delete_set">
                        <input type="hidden" name="id" value="<?= (int) $set['id'] ?>">
                        <button class="btn btn-danger" type="submit" onclick="return confirm('حذف هذه المجموعة وكلماتها؟')">حذف</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($sets === []): ?>
        <p class="muted">لا توجد مجموعات بعد. أنشئ مجموعة حروف ثم أضف الكلمات المستخرجة منها.</p>
    <?php endif; ?>
</main>
<?php require HOROF_ROOT . '/includes/footer.php'; ?>
