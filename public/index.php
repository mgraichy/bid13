<?php declare(strict_types=1);

require realpath(__DIR__ . '/../autoload.php');
require realpath(__DIR__ . '/../src/telesign.php');

use App\ScatterPlot;


// Keep the CSV out of the public directory:
$file = dirname(__DIR__, 1) . '/php-quiz-question-2-out.csv';
$scatterPlot = new ScatterPlot($file);

$scatterPlot->handle()
    ->createScatterPlot()
    ->printScatterPlotToClient();


/**
// As this is a POST request, I'd rather include a body (and not put the number in the path):
$apiUrl = 'https://rest-ww.telesign.com/v1/phoneid';
$phoneNumber = '<COUNTRY_CODE_AND_NUMBER>';
$customerId = '<ID>';
$apiKey = '<API_KEY>';
// https://developer.telesign.com/enterprise/docs/authentication#basic-authentication:
$preAuth = base64_encode(mb_convert_encoding("$customerId:$apiKey", 'UTF-8', mb_list_encodings()));
$auth = mb_convert_encoding($preAuth, 'UTF-8', mb_list_encodings());

$curlRequest = setUpCurl($auth, $apiUrl, $phoneNumber);
$curlResponse = isValidPhoneNumber($curlRequest);

if ($curlResponse) {
    echo 'true';
} else if ($curlResponse === false) {
    echo 'false';
} else {
    echo 'Number not available';
}
*/