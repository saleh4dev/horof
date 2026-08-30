<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$code = strtoupper(trim((string) ($_GET['c'] ?? '')));
$title = 'المسابقة — حروف';
$bodyClass = 'page-play';
$scripts = ['assets/js/common.js', 'assets/js/play.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="play-layout">
    <header class="play-top">
        <div>
            <p class="brand-kicker" id="player-name">متسابق</p>
            <h1 id="round-title">حروف</h1>
        </div>
        <div class="timer" id="timer">--</div>
    </header>

    <section class="letters big" id="letters"></section>

    <form id="word-form" class="word-form" hidden>
        <input type="text" id="word-input" maxlength="64" autocomplete="off" enterkeyhint="send" placeholder="كوّن كلمة من الحروف">
        <button type="submit" class="btn btn-primary">إرسال</button>
    </form>
    <p class="form-msg" id="play-msg" hidden></p>

    <section class="panel">
        <h2>كلماتك هذه الجولة</h2>
        <ul id="my-words" class="word-chips"></ul>
        <p class="muted" id="score-line"></p>
    </section>

    <section class="panel" id="board-panel">
        <h2>الترتيب</h2>
        <ol id="board" class="board"></ol>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
