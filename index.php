<?php
declare(strict_types=1);
require_once __DIR__ . '/init.php';

global $googleClient;
global $logger;
global $login;

try {
    initAccessToken($login, $googleClient);
    run();
} catch (\Throwable $throwable) {
    $logger->critical('Uncaught \Throwable appeared', [
        'throwable' => $throwable,
    ]);
}
