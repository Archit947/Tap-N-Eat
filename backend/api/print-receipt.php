<?php
/**
 * Print Receipt API Endpoint
 * Handles receipt printing to thermal printer
 */

date_default_timezone_set('Asia/Kolkata');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../receipt-printer.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->transaction_id)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Transaction ID required'
        ]);
        exit;
    }

    try {
        // Get transaction details
        $query = "SELECT t.*, e.emp_name, e.emp_id, e.site_name, e.shift 
                  FROM transactions t
                  LEFT JOIN employees e ON t.employee_id = e.id
                  WHERE t.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->transaction_id);
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Transaction not found'
            ]);
            exit;
        }

        // Get employee details
        $empQuery = "SELECT * FROM employees WHERE id = :emp_id";
        $empStmt = $db->prepare($empQuery);
        $empStmt->bindParam(':emp_id', $transaction['employee_id']);
        $empStmt->execute();
        $employee = $empStmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Employee not found'
            ]);
            exit;
        }

        // Prepare receipt data
        $receiptEmployee = [
            'name' => $employee['emp_name'],
            'emp_id' => $employee['emp_id'],
            'site' => $employee['site_name']
        ];

        $receiptTransaction = [
            'id' => $transaction['id'],
            'meal_category' => $transaction['meal_category'],
            'time' => $transaction['transaction_time'],
            'date' => $transaction['transaction_date'],
            'amount' => number_format($transaction['amount'], 2, '.', ''),
            'balance' => number_format($transaction['new_balance'], 2, '.', ''),
        ];

        // Print receipt
        $printer = new ReceiptPrinter();
        $printResult = $printer->printReceipt($receiptEmployee, $receiptTransaction);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Receipt processed',
            'print' => $printResult
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>
