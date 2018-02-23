<?php
declare(strict_types=1);

/**
 * Create instance of Google_Client and set required parameters.
 *
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

    $googleClient = new Google_Client();
    $googleClient->setAuthConfig($pathToClientSecretJson);
    $googleClient->setAccessType('offline');
    $googleClient->addScope($arScopes);
    $googleClient->setRedirectUri($urlToOAuth2callback);

    return $googleClient;
}

/**
 * Initiate Logger
 *
 * @return \Psr\Log\LoggerInterface
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
        'login' => $login,
    ]));
    $logger->pushProcessor(new \Monolog\Processor\WebProcessor);
    \Monolog\Registry::addLogger($logger);

    return $logger;
}

/**
 * Read previously saved Access Token. If there is no Access Token - redirect to
 * /oauth2callback.php for getting a new Access Token from Google.
 *
 * If Access Token expired - try to refresh it with Refresh Token.
 *
 * If there is no Refresh Token - redirect to /oauth2callback.php
 * for getting a new Access Token from Google.
 *
 * Save Access Token when you got one.
 *
 * @param string        $login        User login. Needed only as a name of the
 *                                    .json file with Access Token
 * @param Google_Client $googleClient Instance of Google_Client where to put
 *                                    Access Token
 */
function initAccessToken(string $login, Google_Client $googleClient)
{
    global $logger;

    $accessToken = readAccessToken($login);

    if (!$accessToken) {
        $redirectUri = $googleClient->getRedirectUri(); // 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
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
            $redirectUri = $googleClient->getRedirectUri(); // 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
            $logger->info('No refresh token. Redirecting', ['redirect_uri' => $redirectUri,]);
            header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
            die;
        }

        $logger->info('Refreshing with refresh token', ['refresh_token' => var_export($refreshToken, true)]);

        $googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
        $logger->info('New access token', ['access_token' => var_export($googleClient->getAccessToken(), true)]);
        writeAccessToken($login, $googleClient->getAccessToken());
    } else {
        $logger->info('Access token is not expired',
            ['access_token' => var_export($googleClient->getAccessToken(), true)]);
    }
}

/**
 * Save Access Token to .json file
 *
 * @param string $login User login. Needed only as a name of the .json file
 *                      with Access Token
 * @param array  $accessToken
 */
function writeAccessToken(string $login, array $accessToken)
{
    global $login;
    file_put_contents(
        CREDENTIALS_DIR . '/' . $login . '.json',
        json_encode($accessToken)
    );
}

/**
 * Load Access Token from .json file
 *
 * @param string $login User login. Needed only as a name of the .json file
 *                      with Access Token
 *
 * @return array
 */
function readAccessToken(string $login): array
{
    $token = file_get_contents(CREDENTIALS_DIR . '/' . $login . '.json');

    if ($token === false) {
        return [];
    }

    return json_decode($token, true);
}

/**
 * Main function
 */
function run()
{
    global $googleClient;

    $sheetsService = new Google_Service_Sheets($googleClient);

    $spreadsheetProperties = new Google_Service_Sheets_SpreadsheetProperties();
    $spreadsheetProperties->setTitle('spreadsheet.io ' . date('Y-m-d H:i:s'));

    $newSpreadsheet = new Google_Service_Sheets_Spreadsheet();
    $newSpreadsheet->setProperties($spreadsheetProperties);

    $createdSpreadsheet = $sheetsService->spreadsheets->create($newSpreadsheet);

    $valuesToWrite = [
        ['ID', 'NAME', 'GENDER'],
        ['1', 'Mary', 'Female'],
        ['2', 'John', 'Male'],
        ['=1+2', 'Kate', 'Female'],
        ['4', 'Chris', 'Male'],
    ];

    $body = new Google_Service_Sheets_ValueRange();
    $body->setValues($valuesToWrite);
    $body->setMajorDimension('ROWS');

    $params = [
        'valueInputOption' => 'RAW' // use 'USER_ENTERED' if you need to process formulas like '=1+2'
    ];

    $sheetsService->spreadsheets_values->update(
        $createdSpreadsheet->getSpreadsheetId(),
        'Sheet1!A1',
        $body,
        $params
    );

    $spreadsheetUrl = $createdSpreadsheet->getSpreadsheetUrl();
    $spreadsheetName = $createdSpreadsheet->getProperties()->getTitle();

    echo "Go and see created spreadsheet <a href='$spreadsheetUrl'>$spreadsheetName</a>";
}
