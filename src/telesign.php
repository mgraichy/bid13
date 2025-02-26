<?php declare(strict_types=1);

function isValidPhoneNumber($apiUrl, $phoneNumber, $auth)
{
    $headers = [
        "Authorization: Basic $auth",
        'Accept: application/json',
        // Documentation says this JSON request API only accepts UTF-8, so make this explicit:
        'Content-Type: application/json; charset=utf-8',
    ];

    // https://developer.telesign.com/enterprise/reference/submitphonenumberforidentityalt#form-submitPhoneNumberForIdentityAlt:
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

    $ch = curl_init();
    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return false; // API request failed
    }

    $data = json_decode($response, true);
    if (!isset($data['numbering']['phone_type'])) {
        return false; // Unexpected API response
    }

    $valid_types = ["FIXED_LINE", "MOBILE", "VALID"];
    return in_array(strtoupper($data['numbering']['phone_type']), $valid_types);
}

// As this is a POST request, I'd rather include a body (and not put the number in the path):
$apiUrl = 'https://rest-ww.telesign.com/v1/phoneid';
$phoneNumber = '1234567890';
// $customerId = '<ID>';
$customerId = '5172B3B5-23B4-4D23-8475-72E342561A23';
// $apiKey = '<API_KEY>';
$apiKey = 'oAfhXKHJJw9d9YcOCYhl3pNJenj1G8Juii7m5dlmji0s0UV2NE5mUWOWjVAMIfKJ8yrlCDNdj2R/YlD+KpSlmg==';
// https://developer.telesign.com/enterprise/docs/authentication#basic-authentication:
$preAuth = base64_encode(mb_convert_encoding("$customerId:$apiKey", 'UTF-8', mb_list_encodings()));
$auth = mb_convert_encoding($preAuth, 'UTF-8', mb_list_encodings());
$result = isValidPhoneNumber($apiUrl, $phoneNumber, $auth);
var_dump($result);


