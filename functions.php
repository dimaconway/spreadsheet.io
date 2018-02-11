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
    global $login;

    $logDir = $_SERVER['DOCUMENT_ROOT'] . '/logs';

    $logstashFormatter = new \Monolog\Formatter\LogstashFormatter('spreadsheet.io');

    $streamHandler = new \Monolog\Handler\StreamHandler($logDir . '/spreadsheet.io.logstash.log');
    $streamHandler->setFormatter($logstashFormatter);

    $streamErrorHandler = new \Monolog\Handler\StreamHandler(
        $logDir . '/spreadsheet.io.error.logstash.log',
        \Monolog\Logger::ERROR
    );
    $streamErrorHandler->setFormatter($logstashFormatter);

    $logger = new \Monolog\Logger('app');
    $logger->pushHandler($streamHandler);
    $logger->pushHandler($streamErrorHandler);
    $logger->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor);
    $logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor);
    $logger->pushProcessor(new \Monolog\Processor\TagProcessor([
        'session_id' => session_id(),
        'login' => $login,
    ]));
    $logger->pushProcessor(new \Monolog\Processor\WebProcessor);
    \Monolog\Registry::addLogger($logger);

    return $logger;
}

/**
 * @param string        $login
 * @param Google_Client $googleClient
 */
function initAccessToken(string $login, Google_Client $googleClient)
{
    global $logger;

    $accessToken = loadAccessToken($login);

    if (!$accessToken) {
        $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
        $logger->info('No access token. Redirecting', ['redirect_uri' => $redirectUri]);
        header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
        die;
    }

    $googleClient->setAccessToken($accessToken);
    $logger->info('Have an Access token', ['access_token' => var_export($googleClient->getAccessToken(), true)]);

    if ($googleClient->isAccessTokenExpired()) {
        $logger->info('Access token expired', ['access_token' => var_export($googleClient->getAccessToken(), true)]);

        $refreshToken = $googleClient->getRefreshToken();
        if (!$refreshToken) {
            $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
            $logger->info('No refresh token. Redirecting', ['redirect_uri' => $redirectUri,]);
            header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
            die;
        }

        $logger->info('Refreshing with refresh token', ['refresh_token' => var_export($refreshToken, true)]);

        $googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
        saveAccessToken($login, $googleClient->getAccessToken());
        $logger->info('New access token', ['access_token' => var_export($googleClient->getAccessToken(), true)]);
    } else {
        $logger->info('Access token is not expired',
            ['access_token' => var_export($googleClient->getAccessToken(), true)]);
    }
}

function run()
{
    global $googleClient;
    global $logger;

    $sheetsService = new Google_Service_Sheets($googleClient);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
    $spreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
    $range = 'Class Data!A2:E';
    $response = $sheetsService->spreadsheets_values->get($spreadsheetId, $range);
    /** @var Google_Service_Sheets_ValueRange $values */
    $values = $response->getValues();
    echo '<pre>';
    if (count($values) === 0) {
        print "No data found.\n";
    } else {
        print "Name\tMajor:\n";
        foreach ($values as $row) {
            // Print columns A and E, which correspond to indices 0 and 4.
            printf("%s\t%s\n", $row[0], $row[4]);
        }
    }
    echo '</pre>';


    //$newSpreadsheet = new Google_Service_Sheets_Spreadsheet();
    //$newSpreadsheet->setProperties(new Google_Service_Sheets_SpreadsheetProperties([
    //    'title' => 'spreadsheet.io',
    //]));
    //$response = $sheetsService->spreadsheets->create($newSpreadsheet);
    //echo '<pre>', var_export($response, true), '</pre>', "\n";

}

/**
 * @param string $login
 * @param array  $accessToken
 */
function saveAccessToken(string $login, array $accessToken)
{
    global $login;
    file_put_contents(
        CREDENTIALS_DIR . '/' . $login . '.json',
        json_encode($accessToken)
    );
}

/**
 * @param string $login
 *
 * @return array
 */
function loadAccessToken(string $login): array
{
    $token = file_get_contents(CREDENTIALS_DIR . '/' . $login . '.json');

    if ($token === false) {
        return [];
    }

    return json_decode($token, true);
}
