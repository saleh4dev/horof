<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'طريقة غير مدعومة'], 405);
}

try {
    $room = load_host_room($pdo, $hostToken);
    if (!$room) {
        throw new RuntimeException('صلاحية القائد غير صحيحة');
    }
    $playerId = (int) ($input['player_id'] ?? 0);
    if ($playerId < 1) {
        throw new RuntimeException('متسابق غير محدد');
    }
    kick_player($pdo, $room, $playerId);
    json_out(public_state($pdo, $room, null, true));
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
