<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require HOROF_ROOT . '/includes/admin.php';

admin_require();
seed_vocab_from_file();

$flash = '';
$flashOk = false;
$csrf = admin_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_ok()) {
        $flash = 'انتهت صلاحية الجلسة. أعد المحاولة.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'add_word') {
            $result = add_vocab_word((string) ($_POST['word'] ?? ''));
            $flash = $result['ok'] ? 'تمت إضافة الكلمة.' : $result['error'];
            $flashOk = $result['ok'];
        } elseif ($action === 'add_words') {
            $lines = preg_split('/\R/u', (string) ($_POST['words'] ?? '')) ?: [];
            $added = 0;
            foreach ($lines as $line) {
                $result = add_vocab_word($line);
                if ($result['ok']) {
                    $added++;
                }
            }
            $flash = $added > 0 ? "تمت إضافة {$added} كلمة." : 'لم تُضف كلمات جديدة.';
            $flashOk = $added > 0;
        } elseif ($action === 'delete_word') {
            delete_vocab_word((int) ($_POST['id'] ?? 0));
            $flash = 'حُذفت الكلمة.';
            $flashOk = true;
        } elseif ($action === 'add_letters') {
            $result = add_letter_pool((string) ($_POST['letters'] ?? ''), (string) ($_POST['letter_name'] ?? ''));
            $flash = $result['ok'] ? 'أُضيفت مجموعة الحروف.' : $result['error'];
            $flashOk = $result['ok'];
        } elseif ($action === 'delete_letters') {
            delete_letter_pool((int) ($_POST['id'] ?? 0));
            $flash = 'حُذفت مجموعة الحروف.';
            $flashOk = true;
        }
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$words = search_vocab_words($search);
$totalWords = vocab_count();
$pools = letter_pools();

$title = 'لوحة التحكم — حروف';
$bodyClass = 'page-admin';
$assetPrefix = '../';
require HOROF_ROOT . '/includes/header.php';
?>
<main class="admin-layout">
    <header class="host-top">
        <div>
            <p class="brand-kicker">لوحة التحكم</p>
            <h1>الكلمات والحروف</h1>
            <p class="muted">الكلمات المقبولة من اللاعبين تُحفظ هنا في قاعدة البيانات. إن أضفت مجموعات حروف، تُستخدم في الجولات بدل التوليد العشوائي.</p>
        </div>
        <a class="btn" href="logout.php">خروج</a>
    </header>
    <?php if ($flash): ?>
        <p class="form-msg <?= $flashOk ? 'ok' : '' ?>"><?= h($flash) ?></p>
    <?php endif; ?>

    <div class="host-grid">
        <section class="panel">
            <h2>مجموعات الحروف</h2>
            <p class="muted">كل مجموعة تظهر كجولة. من 6 إلى 12 حرفاً، بدون فراغات أو معها.</p>
            <form method="post" class="stack">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="add_letters">
                <label>اسم المجموعة (اختياري)<input name="letter_name" maxlength="40" placeholder="مثال: مجموعة الكتب"></label>
                <label>الحروف<input name="letters" required maxlength="24" placeholder="كتب ارمنل"></label>
                <button class="btn btn-primary" type="submit">إضافة الحروف</button>
            </form>
            <ul class="player-list">
                <?php foreach ($pools as $pool): ?>
                    <li>
                        <span>
                            <strong><?= h($pool['name'] !== '' ? $pool['name'] : 'مجموعة') ?></strong>
                            <span class="muted"><?= h(implode(' ', ar_chars($pool['letters']))) ?></span>
                        </span>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <input type="hidden" name="action" value="delete_letters">
                            <input type="hidden" name="id" value="<?= (int) $pool['id'] ?>">
                            <button class="kick" type="submit">حذف</button>
                        </form>
                    </li>
                <?php endforeach; ?>
                <?php if ($pools === []): ?>
                    <li class="muted">لا توجد مجموعات بعد. الجولات ستُولَّد تلقائياً من الكلمات.</li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="panel">
            <h2>إضافة كلمات</h2>
            <p class="muted">هذه هي الكلمات التي يُقبل إرسالها أثناء المسابقة. حالياً <?= (int) $totalWords ?> كلمة.</p>
            <form method="post" class="stack">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="add_word">
                <label>كلمة واحدة<input name="word" maxlength="32" placeholder="مثال: كتاب"></label>
                <button class="btn btn-primary" type="submit">إضافة</button>
            </form>
            <form method="post" class="stack" style="margin-top:1rem">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="add_words">
                <label>عدة كلمات (سطر لكل كلمة)
                    <textarea name="words" placeholder="كتاب&#10;كتب&#10;مكتب"></textarea>
                </label>
                <button class="btn" type="submit">إضافة القائمة</button>
            </form>
        </section>
    </div>

    <section class="panel" style="margin-top:1rem">
        <h2>الكلمات المحفوظة</h2>
        <form method="get" class="word-form">
            <input type="search" name="q" value="<?= h($search) ?>" placeholder="بحث في الكلمات">
            <button class="btn" type="submit">بحث</button>
        </form>
        <table class="admin-table">
            <thead>
                <tr><th>الكلمة</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($words as $row): ?>
                <tr>
                    <td><?= h($row['word']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <input type="hidden" name="action" value="delete_word">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="kick" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($words === []): ?>
            <p class="muted">لا توجد كلمات مطابقة.</p>
        <?php endif; ?>
    </section>
</main>
<?php require HOROF_ROOT . '/includes/footer.php'; ?>
