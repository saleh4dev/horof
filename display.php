<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$code = strtoupper(trim((string) ($_GET['c'] ?? '')));
$title = 'شاشة العرض — حروف';
$bodyClass = 'page-display';
$scripts = ['assets/js/common.js', 'assets/js/display.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="display-layout">
    <header class="display-top">
        <div class="display-brand" id="disp-name">حروف</div>
        <div class="display-meta">
            <span id="disp-round"></span>
            <span class="disp-timer" id="disp-timer"></span>
        </div>
    </header>

    <section class="display-stage" id="stage">
        <div class="lobby-show" id="lobby-show">
            <p class="display-kicker">امسح الباركود للانضمام</p>
            <h1 class="display-code" id="disp-code"><?= h($code !== '' ? $code : '------') ?></h1>
            <img id="disp-qr" alt="باركود الغرفة">
            <ul id="disp-lobby-players" class="lobby-chips"></ul>
        </div>

        <div class="play-show" id="play-show" hidden>
            <div class="letters huge" id="disp-letters"></div>
            <ol id="disp-live-board" class="board display-board"></ol>
        </div>

        <div class="results-show" id="results-show" hidden>
            <h2 id="results-title">نتيجة الجولة</h2>
            <ol id="disp-results" class="board display-board results-board"></ol>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
