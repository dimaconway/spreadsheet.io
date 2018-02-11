<?php
declare(strict_types=1);
require_once __DIR__ . '/init.php';

global $googleClient;
global $logger;

if (isset($_GET['code'])) {
    $logger->info('Got authCode {auth_code}', ['auth_code' => $_GET['code']]);
    $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['ACCESS_TOKEN'] = $googleClient->getAccessToken();
    $logger->info('Access token from authCode {access_token}', [
        'access_token' => var_export($_SESSION['ACCESS_TOKEN'], true),
    ]);

    $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
    header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
} else {
    $authUrl = $googleClient->createAuthUrl();
    $logger->info('Redirecting to authUrl {auth_url}', ['auth_url' => $authUrl]);
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
}
