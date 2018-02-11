<?php
declare(strict_types=1);
require_once __DIR__ . '/init.php';

global $googleClient;
global $logger;
global $login;

try {
    if (isset($_GET['code'])) {
        $logger->info('Got authCode', ['auth_code' => $_GET['code']]);

        $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
        saveAccessToken($login, $googleClient->getAccessToken());
        $logger->info('Access token from authCode', ['access_token' => var_export($googleClient->getAccessToken(), true)]);

        $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
        header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
    } else {
        $authUrl = $googleClient->createAuthUrl();
        $logger->info('Redirecting to authUrl', ['auth_url' => $authUrl]);
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    }
} catch (\Throwable $throwable) {
    $logger->critical('Uncaught \Throwable appeared', [
        'throwable' => $throwable,
    ]);
}
