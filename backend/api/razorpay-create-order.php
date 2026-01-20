<?php
// Create Razorpay order (server-side).
// Expects JSON: { amount (paise), currency, receipt, notes }

date_default_timezone_set('Asia/Kolkata');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Convert PHP notices/warnings into JSON errors instead of HTML output
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Server error (PHP)',
        'error' => "$errstr in $errfile:$errline"
    ]);
    exit;
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([ 'message' => 'Method not allowed' ]);
    exit;
}

require_once __DIR__ . '/../config/env_loader.php';

$keyId = $_ENV['RAZORPAY_KEY_ID'] ?? getenv('RAZORPAY_KEY_ID');
$keySecret = $_ENV['RAZORPAY_KEY_SECRET'] ?? getenv('RAZORPAY_KEY_SECRET');

if (!$keyId || !$keySecret) {
    http_response_code(500);
    echo json_encode([ 'message' => 'Razorpay keys not configured on server (RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET)' ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$amount = isset($data['amount']) ? (int)$data['amount'] : 0;
$currency = isset($data['currency']) ? (string)$data['currency'] : 'INR';
$receipt = isset($data['receipt']) ? (string)$data['receipt'] : ('VIS-' . time());
$notes = isset($data['notes']) && is_array($data['notes']) ? $data['notes'] : [];

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode([ 'message' => 'Invalid amount' ]);
    exit;
}

$payload = json_encode([
    'amount' => $amount,
    'currency' => $currency,
    'receipt' => $receipt,
    'notes' => $notes,
]);

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);

$responseBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);

curl_close($ch);

if ($responseBody === false) {
    http_response_code(500);
    echo json_encode([ 'message' => 'cURL error creating Razorpay order', 'error' => $curlErr ]);
    exit;
}

$res = json_decode($responseBody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Razorpay returned a non-JSON response',
        'raw' => substr($responseBody, 0, 500)
    ]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode);
    echo json_encode([
        'message' => $res['error']['description'] ?? 'Razorpay order creation failed',
        'razorpay' => $res,
    ]);
    exit;
}

echo json_encode([
    'id' => $res['id'] ?? null,
    'amount' => $res['amount'] ?? $amount,
    'currency' => $res['currency'] ?? $currency,
    'receipt' => $res['receipt'] ?? $receipt,
    'notes' => $res['notes'] ?? $notes,
]);
