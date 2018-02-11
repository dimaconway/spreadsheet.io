<?php
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions.php';

define('CREDENTIALS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/credentials');

$login = trim(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/login.txt')) ?? 'default_login';

$googleClient = initGoogleClient();
$logger = initLogger();
