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
    if ($room['status'] !== 'lobby') {
        throw new RuntimeException('المسابقة بدأت مسبقاً');
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM players WHERE room_id = ? AND kicked = 0');
    $count->execute([(int) $room['id']]);
    if ((int) $count->fetchColumn() < 1) {
        throw new RuntimeException('انتظر انضمام متسابق واحد على الأقل');
    }
    if (prepared_set_count() < 1) {
        throw new RuntimeException('لا توجد جولات جاهزة. أضف حروفاً وكلماتها من لوحة التحكم أولاً.');
    }
    room_lock($pdo, (int) $room['id']);
    try {
        $room = start_round($pdo, $room);
    } finally {
        room_unlock($pdo, (int) $room['id']);
    }
    json_out(public_state($pdo, $room, null, true));
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
