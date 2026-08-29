<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'طريقة غير مدعومة'], 405);
}

try {
    $code = strtoupper(trim((string) ($input['code'] ?? '')));
    $name = (string) ($input['name'] ?? '');
    $result = join_room($pdo, $code, $name);
    json_out([
        'ok' => true,
        'player_token' => $result['player']['token'],
        'code' => $result['room']['code'],
        'play_url' => app_url('play.php?c=' . $result['room']['code']),
    ]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
