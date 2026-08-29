<?php

declare(strict_types=1);

function load_room_by_code(PDO $pdo, string $code): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE code = ? LIMIT 1');
    $stmt->execute([strtoupper(trim($code))]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function load_room_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function load_host_room(PDO $pdo, string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE host_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function load_player(PDO $pdo, string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM players WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function room_lock(PDO $pdo, int $roomId): void
{
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $stmt->execute(['horof_room_' . $roomId]);
}

function room_unlock(PDO $pdo, int $roomId): void
{
    $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $stmt->execute(['horof_room_' . $roomId]);
}

function create_room(PDO $pdo, string $hostName, int $rounds, int $seconds): array
{
    $hostName = clean_name($hostName);
    if (ar_len($hostName) < 2) {
        throw new RuntimeException('اكتب اسم القائد');
    }
    $rounds = max(1, min(15, $rounds));
    $allowed = [30, 45, 60, 90];
    if (!in_array($seconds, $allowed, true)) {
        $seconds = 60;
    }
    for ($i = 0; $i < 8; $i++) {
        $code = random_code();
        $stmt = $pdo->prepare('SELECT id FROM rooms WHERE code = ?');
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            $token = random_token();
            $ins = $pdo->prepare(
                'INSERT INTO rooms (code, host_token, host_name, total_rounds, round_seconds)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([$code, $token, $hostName, $rounds, $seconds]);
            $room = load_room_by_id($pdo, (int) $pdo->lastInsertId());
            if (!$room) {
                throw new RuntimeException('تعذر إنشاء الغرفة');
            }
            return $room;
        }
    }
    throw new RuntimeException('تعذر توليد رمز الغرفة');
}

function join_room(PDO $pdo, string $code, string $name): array
{
    $room = load_room_by_code($pdo, $code);
    if (!$room) {
        throw new RuntimeException('الغرفة غير موجودة');
    }
    if ($room['status'] === 'finished') {
        throw new RuntimeException('المسابقة انتهت');
    }
    $name = clean_name($name);
    if (ar_len($name) < 2) {
        throw new RuntimeException('اكتب اسماً أوضح');
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM players WHERE room_id = ? AND kicked = 0');
    $count->execute([(int) $room['id']]);
    if ((int) $count->fetchColumn() >= HOROF_MAX_PLAYERS) {
        throw new RuntimeException('الغرفة ممتلئة');
    }
    $exists = $pdo->prepare('SELECT id FROM players WHERE room_id = ? AND name = ? LIMIT 1');
    $exists->execute([(int) $room['id'], $name]);
    if ($exists->fetch()) {
        $name = $name . random_int(2, 99);
        $name = mb_substr($name, 0, 24);
    }
    $token = random_token();
    $ins = $pdo->prepare('INSERT INTO players (room_id, token, name) VALUES (?, ?, ?)');
    $ins->execute([(int) $room['id'], $token, $name]);
    return [
        'player' => load_player($pdo, $token),
        'room' => $room,
    ];
}

function require_host(PDO $pdo, array $room, string $token): void
{
    if ($token === '' || !hash_equals($room['host_token'], $token)) {
        throw new RuntimeException('صلاحية القائد غير صحيحة');
    }
}

function start_round(PDO $pdo, array $room): array
{
    $next = (int) $room['current_round'] + 1;
    $letters = generate_letters();
    $stmt = $pdo->prepare(
        'UPDATE rooms
         SET status = ?, current_round = ?, letters = ?, round_started_at = ?, results_until = NULL
         WHERE id = ?'
    );
    $stmt->execute(['playing', $next, $letters, now_utc(), (int) $room['id']]);
    $updated = load_room_by_id($pdo, (int) $room['id']);
    return $updated ?: $room;
}

function finish_round(PDO $pdo, array $room): array
{
    if ($room['status'] !== 'playing') {
        return $room;
    }
    $until = gmdate('Y-m-d H:i:s', time() + HOROF_RESULTS_SECONDS);
    $stmt = $pdo->prepare(
        'UPDATE rooms SET status = ?, results_until = ? WHERE id = ? AND status = ?'
    );
    $stmt->execute(['results', $until, (int) $room['id'], 'playing']);
    $updated = load_room_by_id($pdo, (int) $room['id']);
    return $updated ?: $room;
}

function finish_game(PDO $pdo, array $room): array
{
    $stmt = $pdo->prepare(
        'UPDATE rooms SET status = ?, results_until = NULL WHERE id = ?'
    );
    $stmt->execute(['finished', (int) $room['id']]);
    $updated = load_room_by_id($pdo, (int) $room['id']);
    return $updated ?: $room;
}

function tick_room(PDO $pdo, array $room): array
{
    room_lock($pdo, (int) $room['id']);
    try {
        $fresh = load_room_by_id($pdo, (int) $room['id']);
        if (!$fresh) {
            return $room;
        }
        $room = $fresh;
        if ($room['status'] === 'playing' && $room['round_started_at']) {
            $end = strtotime($room['round_started_at'] . ' UTC') + (int) $room['round_seconds'];
            if (time() >= $end) {
                $room = finish_round($pdo, $room);
            }
        }
        if ($room['status'] === 'results' && $room['results_until']) {
            if (time() >= strtotime($room['results_until'] . ' UTC')) {
                if ((int) $room['current_round'] >= (int) $room['total_rounds']) {
                    $room = finish_game($pdo, $room);
                } else {
                    $room = start_round($pdo, $room);
                }
            }
        }
        return $room;
    } finally {
        room_unlock($pdo, (int) $room['id']);
    }
}

function kick_player(PDO $pdo, array $room, int $playerId): void
{
    $stmt = $pdo->prepare('UPDATE players SET kicked = 1 WHERE id = ? AND room_id = ?');
    $stmt->execute([$playerId, (int) $room['id']]);
}

function submit_word(PDO $pdo, array $player, array $room, string $raw): array
{
    if ((int) $player['kicked'] === 1) {
        throw new RuntimeException('تم طردك من المسابقة');
    }
    $room = tick_room($pdo, $room);
    if ($room['status'] !== 'playing') {
        throw new RuntimeException('لا توجد جولة جارية');
    }
    $check = validate_submission($raw, (string) $room['letters']);
    if (!$check['ok']) {
        return $check;
    }
    $started = strtotime($room['round_started_at'] . ' UTC');
    $elapsed = (int) max(0, (time() - $started) * 1000);
    $points = word_points($check['norm'], $elapsed, (int) $room['round_seconds']);
    try {
        $ins = $pdo->prepare(
            'INSERT INTO submissions (room_id, player_id, round, word, word_norm, valid, points, elapsed_ms)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)'
        );
        $ins->execute([
            (int) $room['id'],
            (int) $player['id'],
            (int) $room['current_round'],
            $check['word'],
            $check['norm'],
            $points,
            $elapsed,
        ]);
    } catch (PDOException $e) {
        if ((int) $e->errorInfo[1] === 1062) {
            return ['ok' => false, 'error' => 'قدّمت هذه الكلمة مسبقاً'];
        }
        throw $e;
    }
    return [
        'ok' => true,
        'word' => $check['word'],
        'norm' => $check['norm'],
        'points' => $points,
    ];
}

function player_scores(PDO $pdo, int $roomId, int $round): array
{
    $sql = 'SELECT p.id, p.name, p.kicked,
                   COALESCE(SUM(CASE WHEN s.valid = 1 THEN s.points ELSE 0 END), 0) AS total_score,
                   COALESCE(SUM(CASE WHEN s.valid = 1 THEN 1 ELSE 0 END), 0) AS total_words,
                   COALESCE(SUM(CASE WHEN s.valid = 1 AND s.round = ? THEN s.points ELSE 0 END), 0) AS round_score,
                   COALESCE(SUM(CASE WHEN s.valid = 1 AND s.round = ? THEN 1 ELSE 0 END), 0) AS round_words
            FROM players p
            LEFT JOIN submissions s ON s.player_id = p.id
            WHERE p.room_id = ?
            GROUP BY p.id, p.name, p.kicked
            ORDER BY total_score DESC, total_words DESC, p.joined_at ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$round, $round, $roomId]);
    return $stmt->fetchAll();
}

function player_round_words(PDO $pdo, int $playerId, int $round): array
{
    $stmt = $pdo->prepare(
        'SELECT word, points, elapsed_ms
         FROM submissions
         WHERE player_id = ? AND round = ? AND valid = 1
         ORDER BY submitted_at ASC'
    );
    $stmt->execute([$playerId, $round]);
    return $stmt->fetchAll();
}

function round_words_by_player(PDO $pdo, int $roomId, int $round): array
{
    $stmt = $pdo->prepare(
        'SELECT player_id, word, points
         FROM submissions
         WHERE room_id = ? AND round = ? AND valid = 1
         ORDER BY submitted_at ASC'
    );
    $stmt->execute([$roomId, $round]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $pid = (int) $row['player_id'];
        $out[$pid][] = ['word' => $row['word'], 'points' => (int) $row['points']];
    }
    return $out;
}

function public_state(PDO $pdo, array $room, ?array $player = null, bool $isHost = false): array
{
    $room = tick_room($pdo, $room);
    $round = (int) $room['current_round'];
    $players = player_scores($pdo, (int) $room['id'], $round);
    $secondsLeft = 0;
    $resultsLeft = 0;
    if ($room['status'] === 'playing' && $room['round_started_at']) {
        $end = strtotime($room['round_started_at'] . ' UTC') + (int) $room['round_seconds'];
        $secondsLeft = max(0, $end - time());
    }
    if ($room['status'] === 'results' && $room['results_until']) {
        $resultsLeft = max(0, strtotime($room['results_until'] . ' UTC') - time());
    }
    $letters = $room['letters'] ? ar_chars((string) $room['letters']) : [];
    $roundWords = [];
    if (in_array($room['status'], ['results', 'finished'], true) && $round > 0) {
        $roundWords = round_words_by_player($pdo, (int) $room['id'], $round);
    }
    $list = [];
    foreach ($players as $p) {
        $pid = (int) $p['id'];
        $item = [
            'id' => $pid,
            'name' => $p['name'],
            'kicked' => (int) $p['kicked'] === 1,
            'total_score' => (int) $p['total_score'],
            'total_words' => (int) $p['total_words'],
            'round_score' => (int) $p['round_score'],
            'round_words' => (int) $p['round_words'],
        ];
        if (isset($roundWords[$pid])) {
            $item['words'] = $roundWords[$pid];
        }
        $list[] = $item;
    }
    $you = null;
    if ($player && (int) $player['room_id'] === (int) $room['id']) {
        $you = [
            'id' => (int) $player['id'],
            'name' => $player['name'],
            'kicked' => (int) $player['kicked'] === 1,
            'words' => player_round_words($pdo, (int) $player['id'], $round),
        ];
    }
    $code = $room['code'];
    return [
        'ok' => true,
        'server_time' => time(),
        'is_host' => $isHost,
        'share_url' => app_url('join.php?c=' . $code),
        'display_url' => app_url('display.php?c=' . $code),
        'room' => [
            'code' => $code,
            'host_name' => $room['host_name'],
            'status' => $room['status'],
            'round' => $round,
            'total_rounds' => (int) $room['total_rounds'],
            'round_seconds' => (int) $room['round_seconds'],
            'letters' => $letters,
            'seconds_left' => $secondsLeft,
            'results_left' => $resultsLeft,
        ],
        'players' => $list,
        'you' => $you,
    ];
}
