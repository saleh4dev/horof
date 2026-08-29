<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$title = 'حروف — مسابقة الكلمات';
$scripts = ['assets/js/common.js', 'assets/js/home.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="shell">
    <header class="brand">
        <p class="brand-kicker">مسابقة مباشرة على الإنترنت</p>
        <h1>حروف</h1>
        <p class="lead">يظهر القائد مجموعة أحرف، ويكوّن المتسابقون أكبر عدد من الكلمات في أسرع وقت.</p>
    </header>

    <div class="home-grid">
        <section class="panel">
            <h2>إنشاء غرفة</h2>
            <p class="muted">أنت القائد. سمّ الغرفة وحدّد الجولات ثم شارك الرمز أو الباركود.</p>
            <form id="create-form" class="stack">
                <label>
                    اسم الغرفة
                    <input type="text" name="name" maxlength="40" required autocomplete="off" placeholder="مثال: مسابقة الجمعة">
                </label>
                <div class="row-2">
                    <label>
                        عدد الجولات
                        <select name="rounds">
                            <?php for ($i = 3; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>" <?= $i === 5 ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>
                        مدة الجولة
                        <select name="seconds">
                            <option value="30">30 ثانية</option>
                            <option value="45">45 ثانية</option>
                            <option value="60" selected>60 ثانية</option>
                            <option value="90">90 ثانية</option>
                        </select>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">إنشاء المسابقة</button>
                <p class="form-msg" hidden></p>
            </form>
        </section>

        <section class="panel">
            <h2>الانضمام</h2>
            <p class="muted">أدخل رمز الغرفة واسمك للدخول كمتسابق.</p>
            <form id="join-form" class="stack">
                <label>
                    رمز الغرفة
                    <input type="text" name="code" maxlength="8" required autocomplete="off" placeholder="مثل K7M2QX" class="code-input">
                </label>
                <label>
                    اسمك
                    <input type="text" name="name" maxlength="24" required autocomplete="nickname" placeholder="اسم يظهر على الشاشة">
                </label>
                <button type="submit" class="btn">دخول المسابقة</button>
                <p class="form-msg" hidden></p>
            </form>
        </section>
    </div>

    <section class="rules">
        <h2>كيف تُحتسب النتيجة</h2>
        <ul>
            <li>كل كلمة صحيحة تمنح <strong>10 نقاط</strong> — كثرة الكلمات هي العامل الأقوى.</li>
            <li>الكلمة الأطول تمنح نقطتين إضافيتين عن كل حرف فوق ثلاثة.</li>
            <li>السرعة تمنح حتى 5 نقاط إضافية حسب وقت الإرسال داخل الجولة.</li>
            <li>الجولة تنتهي بانتهاء الوقت أو بإنهاء القائد، ثم تظهر النتائج والمجموع، وتنتقل التالية تلقائياً.</li>
        </ul>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
