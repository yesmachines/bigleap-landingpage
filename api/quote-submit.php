<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server config missing. Copy api/config.example.php to api/config.php']);
    exit;
}

$config = require $configFile;
$recipientEmail = (string)($config['recipient_email'] ?? 'saneshbigleap@gmail.com');
$web3formsKey = trim((string)($config['web3forms_access_key'] ?? ''));
$gasUrl = trim((string)($config['gas_webapp_url'] ?? ''));

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$firstName = trim((string)($payload['firstName'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$mobile = trim((string)($payload['mobile'] ?? ''));
$company = trim((string)($payload['company'] ?? ''));
$website = trim((string)($payload['website'] ?? ''));
$service = trim((string)($payload['service'] ?? ''));
$message = trim((string)($payload['message'] ?? ''));
$allowedServices = [
    '3D Animation',
    '2D Animation',
    'Product Animation',
    'Character Design',
    'Character Animation',
    'Product Explainer Video',
    'Logo Animation',
];

$errors = [];
if ($firstName === '' || mb_strlen($firstName) < 2) {
    $errors[] = 'Please enter your full name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
$digits = preg_replace('/\D+/', '', $mobile);
if ($digits === null || strlen($digits) < 7 || strlen($digits) > 15) {
    $errors[] = 'Please enter a valid mobile number.';
}
if ($company === '' || mb_strlen($company) < 2) {
    $errors[] = 'Please enter your company name.';
}
if (mb_strlen($company) > 100) {
    $errors[] = 'Company name is too long.';
}
if ($website !== '') {
    $websiteToCheck = preg_match('#^https?://#i', $website) ? $website : 'https://' . $website;
    if (filter_var($websiteToCheck, FILTER_VALIDATE_URL) === false || mb_strlen($website) > 2048) {
        $errors[] = 'Please enter a valid website URL.';
    } else {
        $website = $websiteToCheck;
    }
}
if (!in_array($service, $allowedServices, true)) {
    $errors[] = 'Please select a service.';
}
if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$data = [
    'firstName' => $firstName,
    'email' => $email,
    'mobile' => $mobile,
    'company' => $company,
    'website' => $website,
    'service' => $service,
    'message' => $message,
];

$emailSent = false;
$sheetSaved = false;
$notes = [];

if ($gasUrl !== '') {
    $gasResult = postToGas($gasUrl, $data);
    if ($gasResult['ok']) {
        $emailSent = true;
        $sheetSaved = true;
    } else {
        $notes[] = 'Google Sheet: ' . $gasResult['error'];
    }
}

if (!$emailSent && $web3formsKey !== '') {
    $web3Result = postToWeb3Forms($web3formsKey, $recipientEmail, $data);
    if ($web3Result['ok']) {
        $emailSent = true;
    } else {
        $notes[] = 'Email: ' . $web3Result['error'];
    }
}

if (!$emailSent && !$sheetSaved) {
    http_response_code(502);
    $hint = 'Setup required: add Web3Forms key and/or fix Google Apps Script in api/config.php';
    if ($web3formsKey === '' && $gasUrl === '') {
        $hint = 'Add Web3Forms access key in api/config.php (get free at web3forms.com)';
    } elseif ($web3formsKey === '') {
        $hint = 'Add Web3Forms access key in api/config.php for email (web3forms.com)';
    }
    echo json_encode([
        'success' => false,
        'error' => $hint,
        'details' => $notes,
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'emailSent' => $emailSent,
    'sheetSaved' => $sheetSaved,
    'details' => $notes,
]);

function postToGas(string $url, array $data): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'curl not available'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_USERAGENT => 'BigLeap Quote Form/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status < 200 || $status >= 400) {
        return ['ok' => false, 'error' => 'Script not reachable (HTTP ' . $status . ')'];
    }

    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['success'])) {
        if (stripos($body, 'Script function not found') !== false) {
            return ['ok' => false, 'error' => 'Apps Script not deployed — redeploy with Code.gs'];
        }
        return ['ok' => false, 'error' => 'Apps Script returned an error'];
    }

    return ['ok' => true];
}

function websiteForEmail(string $website): string
{
    $website = trim($website);
    if ($website === '') {
        return '';
    }
    return rtrim(preg_replace('#^https?://#i', '', $website), '/');
}

function postToWeb3Forms(string $accessKey, string $recipientEmail, array $data): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'curl not available'];
    }

    $payload = [
        'access_key' => $accessKey,
        'subject' => 'New Quote Enquiry — ' . $data['firstName'],
        'from_name' => 'BigLeap',
        'name' => $data['firstName'],
        'email' => $data['email'],
        'phone' => $data['mobile'],
        'company' => $data['company'] ?? '',
        'service' => $data['service'] ?? '',
        'message' => $data['message'],
        'replyto' => $data['email'],
    ];
    $site = websiteForEmail($data['website'] ?? '');
    if ($site !== '') {
        $payload['company_site'] = $site;
    }
    $payload = json_encode($payload);

    $ch = curl_init('https://api.web3forms.com/submit');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_USERAGENT => 'BigLeap Quote Form/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Could not reach email service'];
    }

    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['success'])) {
        $msg = is_array($json) ? ($json['message'] ?? 'Invalid access key') : 'Invalid response';
        return ['ok' => false, 'error' => $msg];
    }

    return ['ok' => true];
}
