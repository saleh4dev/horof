<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

$code = strtoupper(trim((string) ($_GET['c'] ?? $input['code'] ?? '')));
$player = $playerToken !== '' ? load_player($pdo, $playerToken) : null;
$room = $code !== '' ? load_room_by_code($pdo, $code) : null;
if (!$room && $hostToken !== '') {
    $room = load_host_room($pdo, $hostToken);
}
if (!$room && $player) {
    $room = load_room_by_id($pdo, (int) $player['room_id']);
}
if (!$room) {
    json_out(['ok' => false, 'error' => 'الغرفة غير موجودة'], 404);
}
$isHost = $hostToken !== '' && hash_equals((string) $room['host_token'], $hostToken);

try {
    json_out(public_state($pdo, $room, $player, $isHost));
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
