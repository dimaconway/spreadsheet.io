<?php
declare(strict_types=1);

/**
 * @return Google_Client
 * @throws Google_Exception
 */
function initGoogleClient()
{
    $pathToClientSecretJson = $_SERVER['DOCUMENT_ROOT'] . '/client_secret.json';
    $arScopes = [
        Google_Service_Sheets::SPREADSHEETS,
    ];
    $urlToOAuth2callback = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';

    $client = new Google_Client();
    $client->setAuthConfig($pathToClientSecretJson);
    $client->setAccessType('offline');
    $client->addScope($arScopes);
    $client->setRedirectUri($urlToOAuth2callback);

    return $client;
}

/**
 * @return \Monolog\Logger
 * @throws Exception
 */
function initLogger()
{
    $logDir = $_SERVER['DOCUMENT_ROOT'] . '/logs';

    $logstashFormatter = new \Monolog\Formatter\LogstashFormatter('spreadsheet.io');
    $streamHandler = new \Monolog\Handler\StreamHandler($logDir . '/spreadsheet.io.logstash.log');
    $streamHandler->setFormatter($logstashFormatter);

    $logger = new \Monolog\Logger('app');
    $logger->pushHandler($streamHandler);
    $logger->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor);
    $logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor);
    $logger->pushProcessor(new \Monolog\Processor\TagProcessor([
        'session_id' => session_id(),
    ]));
    $logger->pushProcessor(new \Monolog\Processor\WebProcessor);
    \Monolog\Registry::addLogger($logger);

    return $logger;
}
