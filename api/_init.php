<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

header('Cache-Control: no-store');

$pdo = db();
$input = request_json();
$hostToken = header_token('X-Host-Token');
$playerToken = header_token('X-Player-Token');
