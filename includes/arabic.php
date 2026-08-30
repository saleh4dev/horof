<?php

declare(strict_types=1);

function ar_normalize(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{06D6}-\x{06ED}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $s) ?? $s;
    $map = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و',
        'ء' => '', 'ـ' => '',
    ];
    $s = strtr($s, $map);
    $s = preg_replace('/\s+/u', '', $s) ?? $s;
    return $s;
}

function ar_needed_extras(string $word, string $letters): array
{
    $bag = ar_counts($letters);
    $need = [];
    foreach (ar_chars($word) as $ch) {
        if (!empty($bag[$ch])) {
            $bag[$ch]--;
            continue;
        }
        $need[$ch] = ($need[$ch] ?? 0) + 1;
    }
    return $need;
}

function ar_shortage_message(array $need): string
{
    $parts = [];
    foreach ($need as $ch => $n) {
        $parts[] = $n > 1 ? $ch . ' × ' . $n : $ch;
    }
    return 'تنقص هذه الحروف من المجموعة: ' . implode('، ', $parts);
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
