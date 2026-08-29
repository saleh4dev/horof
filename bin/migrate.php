<?php

declare(strict_types=1);

define('HOROF_ROOT', dirname(__DIR__));
define('HOROF_MIN_WORD', 3);

require HOROF_ROOT . '/includes/env.php';
load_env_file(HOROF_ROOT . '/.env');

if (!db_is_configured()) {
    fwrite(STDERR, "Database env vars are missing.\n");
    exit(1);
}

require HOROF_ROOT . '/includes/arabic.php';
require HOROF_ROOT . '/includes/db.php';
require HOROF_ROOT . '/includes/helpers.php';
require HOROF_ROOT . '/includes/dictionary.php';

try {
    db();
    seed_vocab_from_file();
    fwrite(STDOUT, "Schema is up to date.\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
