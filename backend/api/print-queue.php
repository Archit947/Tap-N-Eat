<?php
/**
 * Print Queue API - Manage print jobs for polling-based printing
 */

date_default_timezone_set('Asia/Kolkata');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Simple API key check
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey !== 'print_secret') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get PDO connection - handle both methods
if (function_exists('getConnection')) {
    $pdo = getConnection();
} else {
    $database = new Database();
    $pdo = $database->getConnection();
}

/* ===============================
   GET - Fetch pending print jobs
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get pending jobs, oldest first, limit 10
        $stmt = $pdo->prepare("
            SELECT id, employee_name, employee_id, transaction_id, 
                   meal_type, amount, balance, timestamp, qr_url
            FROM print_queue 
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT 10
        ");
        $stmt->execute();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark them as 'printing' so other pollers don't grab them
        if (!empty($jobs)) {
            $ids = array_column($jobs, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $updateStmt = $pdo->prepare("
                UPDATE print_queue 
                SET status = 'printing' 
                WHERE id IN ($placeholders)
            ");
            $updateStmt->execute($ids);
        }
        
        echo json_encode([
            'status' => 'ok',
            'jobs' => $jobs,
            'count' => count($jobs)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ===============================
   POST - Add new job or update status
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Update job status (completed/failed)
    if (isset($data['job_id']) && isset($data['status'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE print_queue 
                SET status = ?, printed_at = NOW(), error_message = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['status'],
                $data['error'] ?? null,
                $data['job_id']
            ]);
            
            echo json_encode(['status' => 'ok', 'message' => 'Job updated']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    // Add new print job
    if (isset($data['employee']) && isset($data['transaction'])) {
        try {
            $emp = $data['employee'];
            $txn = $data['transaction'];
            
            $stmt = $pdo->prepare("
                INSERT INTO print_queue 
                (employee_name, employee_id, transaction_id, meal_type, 
                 amount, balance, timestamp, qr_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $emp['name'],
                $emp['id'],
                $txn['id'] ?? null,
                $txn['meal_type'],
                $txn['amount'],
                $txn['balance'],
                $txn['timestamp'],
                $data['qr_url'] ?? null
            ]);
            
            echo json_encode([
                'status' => 'ok',
                'job_id' => $pdo->lastInsertId(),
                'message' => 'Print job queued'
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
