<?php declare(strict_types=1);

function setUpCurl(string $auth, string $apiUrl, string $phoneNumber): array
{
    $headers = [
        "Authorization: Basic $auth",
        'Accept: application/json',
        // Documentation says this JSON request API only accepts UTF-8, so make this explicit:
        'Content-Type: application/json; charset=utf-8',
    ];

    $requestData = json_encode(['phone_number' => $phoneNumber,]);

    // Add all curl options in a single array for readability.
    // Without further info, I'll leave the timeout options at their defaults:
    $options = [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $requestData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ];

    return $options;
}


/**
 * Checks the validity of the number with TeleSign's Phone ID.
 *
 * @param array $curlRequest
 * @return boolean|null The null is if the given number did not belong to any of the options
 */
function isValidPhoneNumber(array $curlRequest): ?bool
{
    $ch = curl_init();
    curl_setopt_array($ch, $curlRequest);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // If there's an error with cURL itself (and not the TeleSign domain):
    $curlError = curl_errno($ch);
    if ($curlError) {
        return false;
    }

    curl_close($ch);

    // https://developer.telesign.com/enterprise/reference/submitphonenumberforidentityalt
    // says 300 is successful, 301 if only able to get partial data of the phone ID:
    if ($httpCode >= 200 && $httpCode < 302) {
        // Shouldn't json_decode unless it's successful:
        $responseArray = json_decode($response, true);
        // The $data array was checking for non-existent members etc.:
        $extraction = $responseArray['phone_type']['description'] ?? null;
        // str_replace is faster than preg_replace():
        $type = $extraction ? strtolower(trim(str_replace('_', '', $extraction))) : null;

        // PHP8+:
        return match ($type) {
            'prepaid', 'voip', 'invalid', 'payphone', 'restricted' => false,
            'valid', 'fixedline', 'mobile' => true,
            default => null
        };
    }

    return null;
}


// As this is a POST request, I'd rather include a body (and not put the number in the path):
$apiUrl = 'https://rest-ww.telesign.com/v1/phoneid';
$phoneNumber = '15145534258';
// $customerId = '<ID>';
$customerId = '5172B3B5-23B4-4D23-8475-72E342561A23';
// $apiKey = '<API_KEY>';
$apiKey = 'oAfhXKHJJw9d9YcOCYhl3pNJenj1G8Juii7m5dlmji0s0UV2NE5mUWOWjVAMIfKJ8yrlCDNdj2R/YlD+KpSlmg==';
// https://developer.telesign.com/enterprise/docs/authentication#basic-authentication:
$preAuth = base64_encode(mb_convert_encoding("$customerId:$apiKey", 'UTF-8', mb_list_encodings()));
$auth = mb_convert_encoding($preAuth, 'UTF-8', mb_list_encodings());
$curlRequest = setUpCurl($auth, $apiUrl, $phoneNumber);
var_dump($curlRequest);
$curlResponse = isValidPhoneNumber($curlRequest);

// $result = isValidPhoneNumber($apiUrl, $phoneNumber, $auth);
// var_dump($result);


