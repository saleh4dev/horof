<?php

declare(strict_types=1);

function ar_normalize(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}]/u', '', $s) ?? $s;
    $map = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ة' => 'ه',
        'ء' => '', 'ـ' => '',
        'ك' => 'ك', 'ي' => 'ي',
    ];
    $s = strtr($s, $map);
    $s = preg_replace('/\s+/u', '', $s) ?? $s;
    return $s;
}

function ar_chars(string $s): array
{
    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($chars) ? $chars : [];
}

function ar_len(string $s): int
{
    return mb_strlen($s, 'UTF-8');
}

function ar_counts(string $s): array
{
    $counts = [];
    foreach (ar_chars($s) as $ch) {
        $counts[$ch] = ($counts[$ch] ?? 0) + 1;
    }
    return $counts;
}

function ar_uses_letters(string $word, string $letters): bool
{
    $bag = ar_counts($letters);
    foreach (ar_chars($word) as $ch) {
        if (empty($bag[$ch])) {
            return false;
        }
        $bag[$ch]--;
    }
    return true;
}

function ar_is_arabic_word(string $word): bool
{
    return (bool) preg_match('/^[\x{0621}-\x{064A}]+$/u', $word);
}
