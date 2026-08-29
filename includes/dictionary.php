<?php

declare(strict_types=1);

function dictionary_map(): array
{
    static $map = null;
    if (is_array($map)) {
        return $map;
    }
    seed_vocab_from_file();
    $map = [];
    $stmt = db()->query('SELECT word_norm FROM vocab_words');
    foreach ($stmt as $row) {
        $map[$row['word_norm']] = true;
    }
    return $map;
}

function dictionary_words(): array
{
    return array_keys(dictionary_map());
}

function is_dictionary_word(string $norm): bool
{
    $stmt = db()->prepare('SELECT 1 FROM vocab_words WHERE word_norm = ? LIMIT 1');
    $stmt->execute([$norm]);
    return (bool) $stmt->fetchColumn();
}

function seed_vocab_from_file(): void
{
    static $seeded = false;
    if ($seeded) {
        return;
    }
    $exists = db()->query('SELECT 1 FROM vocab_words LIMIT 1')->fetchColumn();
    if ($exists) {
        $seeded = true;
        return;
    }
    $file = HOROF_ROOT . '/data/words.txt';
    if (!is_readable($file)) {
        $seeded = true;
        return;
    }
    $ins = db()->prepare('INSERT IGNORE INTO vocab_words (word, word_norm) VALUES (?, ?)');
    $fh = fopen($file, 'r');
    if ($fh === false) {
        $seeded = true;
        return;
    }
    while (($line = fgets($fh)) !== false) {
        $display = trim($line);
        $norm = ar_normalize($display);
        if ($norm !== '' && ar_len($norm) >= HOROF_MIN_WORD && ar_is_arabic_word($norm)) {
            $ins->execute([$display, $norm]);
        }
    }
    fclose($fh);
    $seeded = true;
}

function add_vocab_word(string $raw): array
{
    $display = trim($raw);
    $norm = ar_normalize($display);
    if ($norm === '' || ar_len($norm) < HOROF_MIN_WORD) {
        return ['ok' => false, 'error' => 'الكلمة يجب أن تكون ' . HOROF_MIN_WORD . ' أحرف على الأقل'];
    }
    if (!ar_is_arabic_word($norm)) {
        return ['ok' => false, 'error' => 'استخدم حروفاً عربية فقط'];
    }
    try {
        $ins = db()->prepare('INSERT INTO vocab_words (word, word_norm) VALUES (?, ?)');
        $ins->execute([$display, $norm]);
    } catch (PDOException $e) {
        if ((int) $e->errorInfo[1] === 1062) {
            return ['ok' => false, 'error' => 'الكلمة موجودة مسبقاً'];
        }
        throw $e;
    }
    return ['ok' => true, 'word' => $display];
}

function delete_vocab_word(int $id): void
{
    $stmt = db()->prepare('DELETE FROM vocab_words WHERE id = ?');
    $stmt->execute([$id]);
}

function search_vocab_words(string $q, int $limit = 80): array
{
    $q = ar_normalize(trim($q));
    if ($q === '') {
        $stmt = db()->query('SELECT id, word FROM vocab_words ORDER BY id DESC LIMIT ' . (int) $limit);
        return $stmt->fetchAll();
    }
    $stmt = db()->prepare(
        'SELECT id, word FROM vocab_words WHERE word_norm LIKE ? ORDER BY id DESC LIMIT ' . (int) $limit
    );
    $stmt->execute(['%' . $q . '%']);
    return $stmt->fetchAll();
}

function vocab_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM vocab_words')->fetchColumn();
}

function add_letter_pool(string $raw, string $name = ''): array
{
    $norm = ar_normalize(trim($raw));
    $name = clean_name($name, 40);
    if (!ar_is_arabic_word($norm) || ar_len($norm) < 6 || ar_len($norm) > 12) {
        return ['ok' => false, 'error' => 'أدخل مجموعة من 6 إلى 12 حرفاً عربياً'];
    }
    $ins = db()->prepare('INSERT INTO letter_pools (name, letters) VALUES (?, ?)');
    $ins->execute([$name, $norm]);
    return ['ok' => true];
}

function delete_letter_pool(int $id): void
{
    $stmt = db()->prepare('DELETE FROM letter_pools WHERE id = ?');
    $stmt->execute([$id]);
}

function letter_pools(): array
{
    return db()->query('SELECT id, name, letters FROM letter_pools ORDER BY id DESC')->fetchAll();
}

function generate_letters(): string
{
    $pools = db()->query('SELECT letters FROM letter_pools')->fetchAll(PDO::FETCH_COLUMN);
    if ($pools) {
        $pick = (string) $pools[array_rand($pools)];
        $chars = ar_chars($pick);
        shuffle($chars);
        if (count($chars) >= 8) {
            return implode('', array_slice($chars, 0, 8));
        }
        while (count($chars) < 8) {
            $chars[] = $chars[array_rand($chars)];
        }
        return implode('', $chars);
    }
    seed_vocab_from_file();
    $stmt = db()->query(
        'SELECT word_norm FROM vocab_words
         WHERE CHAR_LENGTH(word_norm) BETWEEN 3 AND 5
         ORDER BY RAND() LIMIT 12'
    );
    $pool = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
