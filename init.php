<?php
declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__.'/functions.php';

define('CREDENTIALS_DIR', __DIR__ . '/credentials');

session_start();

$googleClient = initGoogleClient();
$logger = initLogger();
$login = $_GET['login'] ?? 'default_login';
