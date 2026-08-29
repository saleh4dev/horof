<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'طريقة غير مدعومة'], 405);
}

try {
    $player = load_player($pdo, $playerToken);
    if (!$player) {
        throw new RuntimeException('جلسة المتسابق غير صالحة');
    }
    $room = load_room_by_id($pdo, (int) $player['room_id']);
    if (!$room) {
        throw new RuntimeException('الغرفة غير موجودة');
    }
    $result = submit_word($pdo, $player, $room, (string) ($input['word'] ?? ''));
    if (!$result['ok']) {
        json_out($result, 422);
    }
    $freshPlayer = load_player($pdo, $playerToken);
    json_out(array_merge($result, public_state($pdo, $room, $freshPlayer, false)));
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
