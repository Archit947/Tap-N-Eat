<?php
// Verify Razorpay payment signature (server-side).
// Expects JSON: { razorpay_order_id, razorpay_payment_id, razorpay_signature }

date_default_timezone_set('Asia/Kolkata');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$responseSent = false;

// Fallback handler to surface fatal errors instead of empty 500 responses
register_shutdown_function(function() use (&$responseSent) {
    $err = error_get_last();
    if ($responseSent || !$err) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (in_array($err['type'], $fatalTypes, true)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'verified' => false,
            'message' => 'Server fatal error',
            'error' => $err['message'] . ' in ' . ($err['file'] ?? '') . ':' . ($err['line'] ?? '')
        ]);
        $responseSent = true;
    }
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Razorpay verify PHP warning: $errstr in $errfile:$errline");
    http_response_code(500);
    echo json_encode([
        'verified' => false,
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
    echo json_encode([ 'verified' => false, 'message' => 'Method not allowed' ]);
    exit;
}

require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../config/database.php';

try {

// Hard preflight checks so we fail with clear messages instead of fatal errors
if (!extension_loaded('pdo_mysql')) {
    http_response_code(500);
    echo json_encode([
        'verified' => false,
        'message' => 'Missing PHP extension',
        'error' => 'pdo_mysql extension is not enabled on the server'
    ]);
    $responseSent = true;
    exit;
}

if (!extension_loaded('curl')) {
    http_response_code(500);
    echo json_encode([
        'verified' => false,
        'message' => 'Missing PHP extension',
        'error' => 'cURL extension is not enabled on the server'
    ]);
    $responseSent = true;
    exit;
}

$keyId = $_ENV['RAZORPAY_KEY_ID'] ?? getenv('RAZORPAY_KEY_ID');
$keySecret = $_ENV['RAZORPAY_KEY_SECRET'] ?? getenv('RAZORPAY_KEY_SECRET');
if (!$keyId || !$keySecret) {
    http_response_code(500);
    echo json_encode([
        'verified' => false,
        'message' => 'Razorpay keys not configured on server',
        'missing' => [
            'key_id' => (bool)$keyId,
            'key_secret' => (bool)$keySecret
        ]
    ]);
    exit;
}

function ensureVisitorOrdersTable(PDO $db) {
    $sql = "CREATE TABLE IF NOT EXISTS visitor_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        razorpay_order_id VARCHAR(64) UNIQUE,
        razorpay_payment_id VARCHAR(64) DEFAULT NULL,
        amount INT NOT NULL,
        currency VARCHAR(8) NOT NULL DEFAULT 'INR',
        meal_slot VARCHAR(50) DEFAULT NULL,
        qty INT DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'Paid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'verified' => false,
        'message' => 'Invalid JSON payload',
        'details' => json_last_error_msg()
    ]);
    exit;
}

$orderId = isset($data['razorpay_order_id']) ? (string)$data['razorpay_order_id'] : '';
$paymentId = isset($data['razorpay_payment_id']) ? (string)$data['razorpay_payment_id'] : '';
$signature = isset($data['razorpay_signature']) ? (string)$data['razorpay_signature'] : '';

if (!$orderId || !$paymentId || !$signature) {
    http_response_code(400);
    echo json_encode([ 'verified' => false, 'message' => 'Missing required fields' ]);
    exit;
}

$payload = $orderId . '|' . $paymentId;
$expected = hash_hmac('sha256', $payload, $keySecret);

$verified = hash_equals($expected, $signature);

$debug = (($_ENV['RAZORPAY_DEBUG'] ?? getenv('RAZORPAY_DEBUG')) === '1');
$persistError = null;

// If verified, persist visitor order (fetch order details from Razorpay)
if ($verified) {
    try {
        $db = getConnection();
        ensureVisitorOrdersTable($db);

        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is not enabled');
        }

        // Fetch order details from Razorpay
        $ch = curl_init('https://api.razorpay.com/v1/orders/' . urlencode($orderId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        $orderBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($orderBody === false) {
            throw new RuntimeException('Razorpay order fetch failed: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Razorpay order fetch HTTP ' . $httpCode . ' body: ' . $orderBody);
        }

        $orderData = json_decode($orderBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid Razorpay order JSON: ' . json_last_error_msg());
        }

        $amount = (int)($orderData['amount'] ?? 0);
        $currency = $orderData['currency'] ?? 'INR';
        $notes = $orderData['notes'] ?? [];
        $meal = $notes['meal'] ?? ($notes['slot'] ?? null);
        $qty = isset($notes['qty']) ? (int)$notes['qty'] : 1;

        // Upsert by razorpay_order_id
        $ins = $db->prepare("INSERT INTO visitor_orders (razorpay_order_id, razorpay_payment_id, amount, currency, meal_slot, qty, status)
                             VALUES (:oid, :pid, :amount, :currency, :meal, :qty, 'Paid')
                             ON DUPLICATE KEY UPDATE razorpay_payment_id = VALUES(razorpay_payment_id), status = 'Paid'");
        $ins->bindValue(':oid', $orderId);
        $ins->bindValue(':pid', $paymentId);
        $ins->bindValue(':amount', $amount, PDO::PARAM_INT);
        $ins->bindValue(':currency', $currency);
        $ins->bindValue(':meal', $meal);
        $ins->bindValue(':qty', $qty, PDO::PARAM_INT);
        $ins->execute();
    } catch (Exception $e) {
        // Log but don't fail verification response
        error_log('Visitor order log failed: ' . $e->getMessage());
        $persistError = $e->getMessage();
    }
}

echo json_encode([
    'verified' => $verified,
    'message' => $verified ? 'Verified' : 'Invalid signature',
    'persist_error' => $debug ? $persistError : null
]);
$responseSent = true;
} catch (Throwable $e) {
    error_log('Razorpay verify unhandled: ' . $e->getMessage());
    if (!$responseSent) {
        http_response_code(500);
        echo json_encode([
            'verified' => false,
            'message' => 'Server exception',
            'error' => $e->getMessage()
        ]);
        $responseSent = true;
    }
    // Ensure shutdown handler knows response was sent
    $responseSent = true;
}
