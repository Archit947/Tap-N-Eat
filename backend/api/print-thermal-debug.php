<?php
/**
 * Diagnostic version of print-thermal.php
 * Shows detailed error messages for debugging
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Asia/Kolkata');

/* ===============================
   CORS & HEADERS
=============================== */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-PRINT-KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/* ===============================
   LOAD ENV & DATABASE
=============================== */
try {
    if (!file_exists('../config/env_loader.php')) {
        throw new Exception('env_loader.php not found');
    }
    require_once '../config/env_loader.php';
    
    if (!file_exists('../config/database.php')) {
        throw new Exception('database.php not found');
    }
    require_once '../config/database.php';
    
    // Test database connection - handle both methods
    if (function_exists('getConnection')) {
        $testPdo = getConnection();
    } else {
        $testDb = new Database();
        $testPdo = $testDb->getConnection();
    }
    
    if (!$testPdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if print_queue table exists
    $result = $testPdo->query("SHOW TABLES LIKE 'print_queue'");
    if ($result->rowCount() === 0) {
        throw new Exception('print_queue table does not exist. Please run create-print-queue.php first');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Setup error: ' . $e->getMessage(),
        'hint' => 'Check if files are uploaded and table is created'
    ]);
    exit;
}

/* ===============================
   PRINT QUEUE HANDLER
=============================== */
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
            throw $e; // Re-throw to show in response
        }
    }
}

/* ===============================
   HANDLE REQUEST
=============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents("php://input");
$payload = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON: ' . json_last_error_msg(),
        'received' => substr($rawInput, 0, 200)
    ]);
    exit;
}

if (!isset($payload['employee'], $payload['transaction'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing employee or transaction data',
        'received' => [
            'has_employee' => isset($payload['employee']),
            'has_transaction' => isset($payload['transaction']),
            'keys' => array_keys($payload)
        ]
    ]);
    exit;
}

/* ===============================
   BUILD QR URL (PUBLIC)
=============================== */
$transactionId = $payload['transaction']['id'] ?? null;

if (!$transactionId) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Transaction ID missing',
        'transaction' => $payload['transaction']
    ]);
    exit;
}

// Sanitize any incoming base to strip duplicated /frontend or /Tap-N-Eat
$rawBase = $_ENV['QSR_PUBLIC_BASE'] ?? getenv('QSR_PUBLIC_BASE') ?? 'https://qsr.catalystsolutions.eco/Tap-N-Eat';

// Remove any trailing /frontend... segment
$rawBase = preg_replace('#/frontend.*$#i', '', $rawBase);

// If path already contains /Tap-N-Eat multiple times, keep only one
// e.g. https://host/Tap-N-Eat/Tap-N-Eat -> https://host/Tap-N-Eat
$rawBase = preg_replace('#(/Tap-N-Eat)+#i', '/Tap-N-Eat', $rawBase);

$base = rtrim($rawBase, '/');

$qrUrl = $base . '/receipt.php?id=' . $transactionId;

/* ===============================
   ADD TO PRINT QUEUE
=============================== */
try {
    // Get PDO connection - handle both methods
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
        throw new Exception('Failed to queue print job');
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
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
