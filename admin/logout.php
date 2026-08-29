<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require HOROF_ROOT . '/includes/admin.php';

admin_session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
