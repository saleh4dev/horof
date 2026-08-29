<?php

declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

define('HOROF_ROOT', dirname(__DIR__));
define('HOROF_RESULTS_SECONDS', 15);
define('HOROF_MIN_WORD', 3);
define('HOROF_MAX_PLAYERS', 24);

require HOROF_ROOT . '/includes/env.php';
load_env_file(HOROF_ROOT . '/.env');

$script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
$isInstall = $script === 'install.php';

if (!db_is_configured()) {
    if ($isInstall) {
        return;
    }
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        echo json_encode(['ok' => false, 'error' => 'أضف بيانات قاعدة البيانات في ملف .env'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: install.php');
    exit;
}

require HOROF_ROOT . '/includes/helpers.php';
require HOROF_ROOT . '/includes/arabic.php';
require HOROF_ROOT . '/includes/db.php';
require HOROF_ROOT . '/includes/dictionary.php';
require HOROF_ROOT . '/includes/game.php';
seed_vocab_from_file();
