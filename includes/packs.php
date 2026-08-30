<?php

declare(strict_types=1);

function prepared_sets(): array
{
    $sql = 'SELECT s.id, s.name, s.letters, COUNT(w.id) AS word_count
            FROM round_sets s
            LEFT JOIN round_set_words w ON w.set_id = s.id
            GROUP BY s.id, s.name, s.letters
            ORDER BY s.id ASC';
    return db()->query($sql)->fetchAll();
}

function playable_sets(): array
{
    return array_values(array_filter(prepared_sets(), static function (array $set): bool {
        return (int) $set['word_count'] > 0 && trim((string) $set['letters']) !== '';
    }));
}

function prepared_set_count(): int
{
    return count(playable_sets());
}

function load_round_set(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM round_sets WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function round_set_words(int $setId): array
{
    $stmt = db()->prepare(
        'SELECT id, word, word_norm FROM round_set_words WHERE set_id = ? ORDER BY id DESC'
    );
    $stmt->execute([$setId]);
    return $stmt->fetchAll();
}

function create_round_set(string $name, string $letters): array
{
    $name = clean_name($name, 40);
    $norm = ar_normalize(trim($letters));
    $len = ar_len($norm);
    if ($name === '') {
        $name = 'مجموعة';
    }
    if (!ar_is_arabic_word($norm) || $len < 4 || $len > 12) {
        return ['ok' => false, 'error' => 'أدخل من 4 إلى 12 حرفاً عربياً'];
    }
    $ins = db()->prepare('INSERT INTO round_sets (name, letters) VALUES (?, ?)');
    $ins->execute([$name, $norm]);
    return ['ok' => true, 'id' => (int) db()->lastInsertId()];
}

function update_round_set(int $id, string $name, string $letters): array
{
    $name = clean_name($name, 40);
    $norm = ar_normalize(trim($letters));
    $len = ar_len($norm);
    if ($name === '') {
        $name = 'مجموعة';
    }
    if (!ar_is_arabic_word($norm) || $len < 4 || $len > 12) {
        return ['ok' => false, 'error' => 'أدخل من 4 إلى 12 حرفاً عربياً'];
    }
    $stmt = db()->prepare('UPDATE round_sets SET name = ?, letters = ? WHERE id = ?');
    $stmt->execute([$name, $norm, $id]);
    return ['ok' => true];
}

function delete_round_set(int $id): void
{
    $stmt = db()->prepare('DELETE FROM round_sets WHERE id = ?');
    $stmt->execute([$id]);
}

function add_set_word(int $setId, string $raw, string $letters): array
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
        return ['ok' => false, 'error' => 'هذه الكلمة لا تُستخرج من حروف المجموعة'];
    }
    try {
        $ins = db()->prepare(
            'INSERT INTO round_set_words (set_id, word, word_norm) VALUES (?, ?, ?)'
        );
        $ins->execute([$setId, $display, $norm]);
    } catch (PDOException $e) {
        if ((int) $e->errorInfo[1] === 1062) {
            return ['ok' => false, 'error' => 'الكلمة مضافة مسبقاً لهذه الحروف'];
        }
        throw $e;
    }
    return ['ok' => true, 'word' => $display];
}

function delete_set_word(int $id, int $setId): void
{
    $stmt = db()->prepare('DELETE FROM round_set_words WHERE id = ? AND set_id = ?');
    $stmt->execute([$id, $setId]);
}

function is_set_word(int $setId, string $norm): bool
{
    $stmt = db()->prepare(
        'SELECT 1 FROM round_set_words WHERE set_id = ? AND word_norm = ? LIMIT 1'
    );
    $stmt->execute([$setId, $norm]);
    return (bool) $stmt->fetchColumn();
}

function parse_used_sets(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $ids = [];
    foreach (explode(',', $raw) as $part) {
        $id = (int) trim($part);
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    return $ids;
}

function pick_round_set(array $room): array
{
    $sets = playable_sets();
    if ($sets === []) {
        throw new RuntimeException('لا توجد جولات جاهزة. أضف حروفاً ثم كلمات مستخرجة منها في لوحة التحكم.');
    }
    $used = parse_used_sets($room['used_sets'] ?? null);
    $available = array_values(array_filter($sets, static function (array $set) use ($used): bool {
        return !in_array((int) $set['id'], $used, true);
    }));
    if ($available === []) {
        $available = $sets;
        $used = [];
    }
    $pick = $available[array_rand($available)];
    $used[] = (int) $pick['id'];
    $pick['_used_sets'] = implode(',', $used);
    return $pick;
}
