<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require HOROF_ROOT . '/includes/admin.php';

admin_require();

$id = (int) ($_GET['id'] ?? 0);
$set = $id > 0 ? load_round_set($id) : null;
if (!$set) {
    header('Location: index.php');
    exit;
}

$flash = '';
$flashOk = false;
$csrf = admin_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_ok()) {
        $flash = 'انتهت صلاحية الجلسة. أعد المحاولة.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'update_set') {
            $result = update_round_set($id, (string) ($_POST['name'] ?? ''), (string) ($_POST['letters'] ?? ''));
            $flash = $result['ok'] ? 'حُفظت الحروف.' : $result['error'];
            $flashOk = $result['ok'];
            if ($result['ok']) {
                $set = load_round_set($id) ?: $set;
            }
        } elseif ($action === 'add_word') {
            $result = add_set_word($id, (string) ($_POST['word'] ?? ''), (string) $set['letters']);
            $flash = $result['ok'] ? 'أُضيفت الكلمة.' : $result['error'];
            $flashOk = $result['ok'];
        } elseif ($action === 'add_words') {
            $lines = preg_split('/\R/u', (string) ($_POST['words'] ?? '')) ?: [];
            $added = 0;
            $skipped = 0;
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $result = add_set_word($id, $line, (string) $set['letters']);
                if ($result['ok']) {
                    $added++;
                } else {
                    $skipped++;
                }
            }
            $flash = "أُضيفت {$added} كلمة.";
            if ($skipped > 0) {
                $flash .= " وتُركت {$skipped} لأنها لا تطابق الحروف أو مكررة.";
            }
            $flashOk = $added > 0;
        } elseif ($action === 'delete_word') {
            delete_set_word((int) ($_POST['word_id'] ?? 0), $id);
            $flash = 'حُذفت الكلمة.';
            $flashOk = true;
        }
    }
}

$words = round_set_words($id);
$title = 'كلمات المجموعة — حروف';
$bodyClass = 'page-admin';
$scripts = ['assets/js/admin-set.js'];
require HOROF_ROOT . '/includes/header.php';
?>
<main class="admin-layout">
    <header class="host-top">
        <div>
            <p class="brand-kicker">مجموعة جاهزة للجولة</p>
            <h1><?= h($set['name'] !== '' ? $set['name'] : 'مجموعة') ?></h1>
        </div>
        <a class="btn" href="index.php">كل المجموعات</a>
    </header>
    <?php if ($flash): ?>
        <p class="form-msg <?= $flashOk ? 'ok' : '' ?>"><?= h($flash) ?></p>
    <?php endif; ?>

    <section class="panel">
        <h2>الحروف المعروضة</h2>
        <div class="letters big" id="set-letters">
            <?php foreach (ar_chars($set['letters']) as $ch): ?>
                <span class="tile"><?= h($ch) ?></span>
            <?php endforeach; ?>
        </div>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="update_set">
            <div class="row-2">
                <label>اسم المجموعة<input name="name" maxlength="40" value="<?= h($set['name']) ?>"></label>
                <label>الحروف<input name="letters" required maxlength="24" value="<?= h($set['letters']) ?>"></label>
            </div>
            <button class="btn" type="submit">حفظ الحروف</button>
        </form>
    </section>

    <div class="host-grid" style="margin-top:1rem">
        <section class="panel">
            <h2>إضافة كلمة مستخرجة من هذه الحروف</h2>
            <form method="post" class="word-form" id="add-word-form" data-letters="<?= h($set['letters']) ?>">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="add_word">
                <input name="word" id="new-word" maxlength="32" required placeholder="مثال: كتاب" autocomplete="off">
                <button class="btn btn-primary" type="submit">إضافة</button>
            </form>
            <p class="form-msg" id="word-hint" hidden></p>
            <form method="post" class="stack" style="margin-top:1rem">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="add_words">
                <label>عدة كلمات (سطر لكل كلمة)
                    <textarea name="words" placeholder="كتاب&#10;كتب&#10;ركب"></textarea>
                </label>
                <button class="btn" type="submit">إضافة القائمة</button>
            </form>
        </section>

        <section class="panel">
            <h2>الكلمات المعتمدة (<?= count($words) ?>)</h2>
            <p class="muted">هذه فقط ما يُقبل من اللاعبين عندما تظهر هذه الحروف.</p>
            <ul class="word-chips">
                <?php foreach ($words as $row): ?>
                    <li>
                        <?= h($row['word']) ?>
                        <form method="post" class="inline-del">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <input type="hidden" name="action" value="delete_word">
                            <input type="hidden" name="word_id" value="<?= (int) $row['id'] ?>">
                            <button class="kick" type="submit">حذف</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($words === []): ?>
                <p class="muted">أضف كلمة واحدة على الأقل حتى تُستخدم هذه المجموعة في المسابقة.</p>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require HOROF_ROOT . '/includes/footer.php'; ?>
