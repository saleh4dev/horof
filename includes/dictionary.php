<?php

declare(strict_types=1);

function dictionary_map(): array
{
    static $map = null;
    if (is_array($map)) {
        return $map;
    }
    $map = [];
    $file = HOROF_ROOT . '/data/words.txt';
    if (!is_readable($file)) {
        return $map;
    }
    $fh = fopen($file, 'r');
    if ($fh === false) {
        return $map;
    }
    while (($line = fgets($fh)) !== false) {
        $word = ar_normalize(trim($line));
        if ($word !== '' && ar_len($word) >= HOROF_MIN_WORD && ar_is_arabic_word($word)) {
            $map[$word] = true;
        }
    }
    fclose($fh);
    return $map;
}

function dictionary_words(): array
{
    return array_keys(dictionary_map());
}

function is_dictionary_word(string $norm): bool
{
    return isset(dictionary_map()[$norm]);
}

function generate_letters(): string
{
    $pool = array_values(array_filter(dictionary_words(), static function (string $w): bool {
        $n = ar_len($w);
        return $n >= 3 && $n <= 5;
    }));
    if ($pool === []) {
        return 'ارنلمتبا';
    }
    shuffle($pool);
    $chars = [];
    foreach ($pool as $word) {
        $next = array_merge($chars, ar_chars($word));
        if (count($next) <= 8) {
            $chars = $next;
        }
        if (count($chars) >= 8) {
            break;
        }
    }
    $fill = ar_chars('اويرنلمتباودسفك');
    while (count($chars) < 8) {
        $chars[] = $fill[random_int(0, count($fill) - 1)];
    }
    shuffle($chars);
    return implode('', array_slice($chars, 0, 8));
}

function word_points(string $word, int $elapsedMs, int $roundSeconds): int
{
    $len = ar_len($word);
    $base = 10;
    $lengthBonus = max(0, $len - 3) * 2;
    $totalMs = max(1000, $roundSeconds * 1000);
    $remain = max(0, $totalMs - $elapsedMs);
    $speed = (int) floor(($remain / $totalMs) * 5);
    return $base + $lengthBonus + $speed;
}

function validate_submission(string $raw, string $letters): array
{
    $display = trim($raw);
    $norm = ar_normalize($display);
    if ($norm === '' || ar_len($norm) < HOROF_MIN_WORD) {
        return ['ok' => false, 'error' => 'الكلمة يجب أن تكون ' . HOROF_MIN_WORD . ' أحرف على الأقل'];
    }
    if (!ar_is_arabic_word($norm)) {
        return ['ok' => false, 'error' => 'استخدم حروفاً عربية فقط'];
    }
    if (!ar_uses_letters($norm, ar_normalize($letters))) {
        return ['ok' => false, 'error' => 'الحروف المستخدمة غير موجودة في المجموعة'];
    }
    if (!is_dictionary_word($norm)) {
        return ['ok' => false, 'error' => 'هذه الكلمة غير معتمدة في قاموس المسابقة'];
    }
    return ['ok' => true, 'word' => $display, 'norm' => $norm];
}
