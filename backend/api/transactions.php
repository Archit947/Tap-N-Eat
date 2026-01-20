<?php
// Set timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

function ensureOrderStatusColumn(PDO $db) {
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM transactions LIKE 'order_status'");
        if ($checkStmt->rowCount() === 0) {
            $db->exec("ALTER TABLE transactions ADD COLUMN order_status VARCHAR(20) NOT NULL DEFAULT 'Pending' AFTER transaction_type");
        }
    } catch (Exception $e) {
        error_log('order_status column check failed: ' . $e->getMessage());
    }
}

function ensureVisitorOrdersTable(PDO $db) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS visitor_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            razorpay_order_id VARCHAR(64) UNIQUE,
            razorpay_payment_id VARCHAR(64) DEFAULT NULL,
            amount INT NOT NULL,
            currency VARCHAR(8) NOT NULL DEFAULT 'INR',
            meal_slot VARCHAR(50) DEFAULT NULL,
            qty INT DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'Paid',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {
        error_log('visitor_orders table check failed: ' . $e->getMessage());
    }
}

ensureOrderStatusColumn($db);
ensureVisitorOrdersTable($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get transactions with filters
        $employeeId = $_GET['employee_id'] ?? null;
        $date = $_GET['date'] ?? null;
        $mealCategory = $_GET['meal_category'] ?? null;
        $limit = $_GET['limit'] ?? 100;
        
        $queryEmp = "SELECT 'employee' AS source, t.id, t.employee_id, t.emp_name, t.emp_id, t.rfid_number, t.transaction_type, t.order_status, t.meal_category, t.amount, t.previous_balance, t.new_balance, t.transaction_date, t.transaction_time, e.site_name, e.shift, t.created_at
                 FROM transactions t
                 LEFT JOIN employees e ON t.employee_id = e.id
                 WHERE 1=1";
        $queryVis = "SELECT 'visitor' AS source, vo.id, NULL AS employee_id, 'Visitor' AS emp_name, CONCAT('VIS-', LPAD(vo.id, 4, '0')) AS emp_id, NULL AS rfid_number, 'visitor' AS transaction_type, vo.status AS order_status, vo.meal_slot AS meal_category, (vo.amount/100) AS amount, NULL AS previous_balance, NULL AS new_balance, DATE(vo.created_at) AS transaction_date, TIME(vo.created_at) AS transaction_time, NULL AS site_name, NULL AS shift, vo.created_at
                 FROM visitor_orders vo
                 WHERE 1=1";
        
        $params = [];
        
        if ($employeeId) {
            $queryEmp .= " AND t.employee_id = :employee_id";
            $params[':employee_id'] = $employeeId;
            // When filtering by employee, do not show visitor orders
            $queryVis .= " AND 1=0";
            
            // Explicitly clear date filter if looking up employee history
            $date = null;
        }
        
        if ($date) {
            $queryEmp .= " AND t.transaction_date = :date";
            $queryVis .= " AND DATE(vo.created_at) = :date";
            $params[':date'] = $date;
        }
        
        if ($mealCategory) {
            $queryEmp .= " AND t.meal_category = :meal_category";
            $queryVis .= " AND vo.meal_slot = :meal_category";
            $params[':meal_category'] = $mealCategory;
        }
        $union = "SELECT * FROM ((" . $queryEmp . ") UNION ALL (" . $queryVis . ")) AS all_tx
                  ORDER BY transaction_date DESC, transaction_time DESC, created_at DESC
                  LIMIT :limit";

        $stmt = $db->prepare($union);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'count' => count($transactions),
            'transactions' => $transactions
        ]);
        break;

    case 'POST':
        $payload = json_decode(file_get_contents("php://input"), true) ?? [];
        $transactionId = $payload['transaction_id'] ?? $payload['id'] ?? null;
        $newStatus = $payload['status'] ?? null;
        $allowedStatuses = ['Pending', 'Delivered', 'Cancelled'];

        if (!$transactionId || !$newStatus || !in_array($newStatus, $allowedStatuses, true)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'transaction_id and valid status are required',
                'allowed_statuses' => $allowedStatuses
            ]);
            break;
        }

        try {
            $update = $db->prepare("UPDATE transactions SET order_status = :status WHERE id = :id");
            $update->bindValue(':status', $newStatus);
            $update->bindValue(':id', (int)$transactionId, PDO::PARAM_INT);
            $update->execute();

            if ($update->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Transaction not found'
                ]);
                break;
            }

            $fetch = $db->prepare("SELECT t.*, e.site_name, e.shift FROM transactions t LEFT JOIN employees e ON t.employee_id = e.id WHERE t.id = :id");
            $fetch->bindValue(':id', (int)$transactionId, PDO::PARAM_INT);
            $fetch->execute();
            $updated = $fetch->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'message' => 'Order status updated',
                'transaction' => $updated
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update order status',
                'details' => $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
?>
