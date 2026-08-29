<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$code = strtoupper(trim((string) ($_GET['c'] ?? '')));
$title = 'غرفة القائد — حروف';
$bodyClass = 'page-host';
$scripts = ['assets/js/common.js', 'assets/js/host.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="host-layout">
    <header class="host-top">
        <div>
            <p class="brand-kicker" id="room-name">غرفة القائد</p>
            <h1 id="room-code"><?= h($code !== '' ? $code : '------') ?></h1>
        </div>
        <div class="host-actions">
            <a id="display-link" class="btn" target="_blank" rel="noopener">شاشة العرض</a>
            <button type="button" id="copy-link" class="btn">نسخ رابط المشاركة</button>
        </div>
    </header>

    <div class="host-grid">
        <section class="panel share-panel">
            <h2>باركود الغرفة</h2>
            <div class="qr-wrap">
                <img id="qr-image" alt="باركود الانضمام للمسابقة">
            </div>
            <p class="share-url" id="share-url"></p>
            <a id="whatsapp-link" class="btn btn-ghost" target="_blank" rel="noopener">مشاركة عبر واتساب</a>
        </section>

        <section class="panel">
            <h2>المتسابقون <span id="player-count">0</span></h2>
            <ul id="player-list" class="player-list"></ul>
            <p class="muted" id="lobby-hint">انتظر دخول اللاعبين ثم ابدأ.</p>
        </section>
    </div>

    <section class="panel control-bar">
        <div class="status-line">
            <span id="status-label">الانتظار</span>
            <span id="round-label"></span>
            <span id="timer-label"></span>
        </div>
        <div class="letters" id="letters"></div>
        <div class="controls">
            <button type="button" id="btn-start" class="btn btn-primary">بدء المسابقة</button>
            <button type="button" id="btn-end-round" class="btn" hidden>إنهاء الجولة</button>
            <button type="button" id="btn-end-game" class="btn btn-danger">إنهاء المسابقة</button>
        </div>
        <p class="form-msg" id="host-msg" hidden></p>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
