<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/env.php';
load_env_file(dirname(__DIR__) . '/.env');

if (!db_is_configured()) {
    fwrite(STDERR, "Database env vars are missing.\n");
    exit(1);
}

require dirname(__DIR__) . '/includes/db.php';

define('HOROF_ROOT', dirname(__DIR__));

try {
    db();
    fwrite(STDOUT, "Schema is up to date.\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
