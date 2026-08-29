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
    if ($room['status'] !== 'playing') {
        throw new RuntimeException('لا توجد جولة جارية');
    }
    room_lock($pdo, (int) $room['id']);
    try {
        $room = finish_round($pdo, $room);
    } finally {
        room_unlock($pdo, (int) $room['id']);
    }
    json_out(public_state($pdo, $room, null, true));
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
