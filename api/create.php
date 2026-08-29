<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'طريقة غير مدعومة'], 405);
}

try {
    $name = (string) ($input['name'] ?? '');
    $rounds = (int) ($input['rounds'] ?? 5);
    $seconds = (int) ($input['seconds'] ?? 60);
    $room = create_room($pdo, $name, $rounds, $seconds);
    json_out([
        'ok' => true,
        'code' => $room['code'],
        'host_token' => $room['host_token'],
        'host_url' => app_url('host.php?c=' . $room['code']),
        'display_url' => app_url('display.php?c=' . $room['code']),
        'share_url' => app_url('join.php?c=' . $room['code']),
    ]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
