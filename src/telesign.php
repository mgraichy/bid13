<?php declare(strict_types=1);

function logToFilesystem(string $fileToSave, string $jsonContents): void
{
    $overrideToSaveInFilesystem = 3;
    error_log(
        $jsonContents,
        $overrideToSaveInFilesystem,
        $fileToSave
    );
}

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
 * @return boolean|null The null is if the given number did not belong to any of the returned options
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
        $fileToSave = dirname(__DIR__, 1) . '/logs/error-logs.log';
        $jsonContents = json_encode(
            [
                'method' => __METHOD__,
                'cURL status' => $curlError,
                'message' => curl_error($ch)
            ],
            JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
        ) . "\n";

        logToFilesystem($fileToSave, $jsonContents);

        curl_close($ch);

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

