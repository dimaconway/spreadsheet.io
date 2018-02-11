<?php
declare(strict_types=1);
require_once __DIR__ . '/init.php';

global $googleClient;
global $logger;

if (empty($_SESSION['ACCESS_TOKEN'])) {
    $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
    $logger->info('No access token. Redirecting to {redirect_uri}', ['redirect_uri' => $redirectUri]);
    header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
} else {
    $googleClient->setAccessToken($_SESSION['ACCESS_TOKEN']);
    if ($googleClient->isAccessTokenExpired()) {
        $logger->info('Access token expired {access_token}', [
            'access_token' => var_export($googleClient->getAccessToken(), true),
        ]);
        $googleClient->fetchAccessTokenWithRefreshToken($googleClient->getRefreshToken());
        $_SESSION['ACCESS_TOKEN'] = $googleClient->getAccessToken();
        $logger->info('New access token {access_token}', [
            'access_token' => var_export($googleClient->getAccessToken(), true),
        ]);
    }
}

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
