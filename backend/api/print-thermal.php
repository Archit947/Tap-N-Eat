<?php
date_default_timezone_set('Asia/Kolkata');


header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-PRINT-KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


require_once '../config/env_loader.php';
require_once '../config/database.php';


class PrintQueueHandler {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addJob($payload) {
        try {
            $emp = $payload['employee'];
            $txn = $payload['transaction'];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO print_queue 
                (employee_name, employee_id, transaction_id, meal_type, 
                 amount, balance, timestamp, qr_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            // Extract employee data - handle multiple field name formats
            $empName = $emp['emp_name'] ?? $emp['name'] ?? 'Unknown';
            $empId = $emp['emp_id'] ?? $emp['id'] ?? 'Unknown';
            
            // Extract transaction data
            $mealType = $emp['meal_category'] ?? $txn['meal_type'] ?? $txn['meal_category'] ?? 'Unknown';
            $amount = $emp['amount'] ?? $txn['amount_deducted'] ?? $txn['amount'] ?? 0;
            $balance = $emp['balance'] ?? $txn['new_balance'] ?? $txn['balance'] ?? 0;
            
            // Build timestamp from date and time or use current
            $timestamp = date('Y-m-d H:i:s');
            if (isset($emp['date']) && isset($emp['time'])) {
                $timestamp = $emp['date'] . ' ' . $emp['time'];
            }
            
            $stmt->execute([
                $empName,
                $empId,
                $txn['id'] ?? null,
                $mealType,
                $amount,
                $balance,
                $timestamp,
                $payload['qr_url'] ?? null
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Failed to queue print job: " . $e->getMessage());
            return false;
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents("php://input"), true);

if (!isset($payload['employee'], $payload['transaction'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}


$transactionId = $payload['transaction']['id'];

// Sanitize any incoming base to strip duplicated /frontend or /Tap-N-Eat
$rawBase = $_ENV['QSR_PUBLIC_BASE'] ?? getenv('QSR_PUBLIC_BASE') ?? 'https://qsr.catalystsolutions.eco/Tap-N-Eat';

// Remove any trailing /frontend... segment
$rawBase = preg_replace('#/frontend.*$#i', '', $rawBase);

// If path already contains /Tap-N-Eat multiple times, keep only one
// e.g. https://host/Tap-N-Eat/Tap-N-Eat -> https://host/Tap-N-Eat
$rawBase = preg_replace('#(/Tap-N-Eat)+#i', '/Tap-N-Eat', $rawBase);

$base = rtrim($rawBase, '/');

$qrUrl = $base . '/receipt.php?id=' . $transactionId;


try {
    // Try getConnection() first, fallback to Database class
    if (function_exists('getConnection')) {
        $pdo = getConnection();
    } else {
        $database = new Database();
        $pdo = $database->getConnection();
    }
    
    $queueHandler = new PrintQueueHandler($pdo);

    $printPayload = [
        'employee'    => $payload['employee'],
        'transaction' => $payload['transaction'],
        'qr_url'      => $qrUrl,
    ];

    $queued = $queueHandler->addJob($printPayload);

    if (!$queued) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to queue print job'
        ]);
        exit;
    }

    echo json_encode([
        'status'  => 'success',
        'mode'    => 'network',
        'message' => 'Print job queued successfully',
        'qr_url'  => $qrUrl
    ]);
    
} catch (Exception $e) {
    error_log("Print thermal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
