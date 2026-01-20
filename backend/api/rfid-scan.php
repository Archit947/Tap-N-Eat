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

ensureOrderStatusColumn($db);

$method = $_SERVER['REQUEST_METHOD'];

// Meal time categories
function getMealCategory($time = null) {
    if ($time === null) {
        $time = date('H:i:s');
    }
    
    $hour = (int)date('H', strtotime($time));
    $minute = (int)date('i', strtotime($time));
    $totalMinutes = ($hour * 60) + $minute;
    
    // 6 AM - 12 PM: Breakfast (₹20)
    if ($totalMinutes >= 360 && $totalMinutes < 720) { // 6:00 AM to 11:59 AM
        return [
            'category' => 'Breakfast',
            'amount' => 20.00,
            'time_slot' => '6:00 AM - 12:00 PM'
        ];
    }
    // 12 PM - 1 PM: Mid-Meal (₹30)
    else if ($totalMinutes >= 720 && $totalMinutes < 780) { // 12:00 PM to 12:59 PM
        return [
            'category' => 'Mid-Meal',
            'amount' => 30.00,
            'time_slot' => '12:00 PM - 1:00 PM'
        ];
    }
    // 1 PM - 3 PM: Lunch (₹50)
    else if ($totalMinutes >= 780 && $totalMinutes < 900) { // 1:00 PM to 2:59 PM
        return [
            'category' => 'Lunch',
            'amount' => 50.00,
            'time_slot' => '1:00 PM - 3:00 PM'
        ];
    }
    // 3 PM - 6 PM: Snack (₹30)
    else if ($totalMinutes >= 900 && $totalMinutes < 1080) { // 3:00 PM to 5:59 PM
        return [
            'category' => 'Snack',
            'amount' => 30.00,
            'time_slot' => '3:00 PM - 6:00 PM'
        ];
    }
    // 6 PM - 9 PM: Dinner (₹50)
    else if ($totalMinutes >= 1080 && $totalMinutes < 1260) { // 6:00 PM to 8:59 PM
        return [
            'category' => 'Dinner',
            'amount' => 50.00,
            'time_slot' => '6:00 PM - 9:00 PM'
        ];
    }
    else {
        return [
            'category' => null,
            'amount' => 0,
            'time_slot' => 'Outside meal hours',
            'error' => 'Current time is not within any meal slot'
        ];
    }
}

switch($method) {
    case 'GET':
        // Get meal category for current time or specific time
        $time = $_GET['time'] ?? null;
        $mealInfo = getMealCategory($time);
        echo json_encode([
            'current_time' => date('H:i:s'),
            'meal_info' => $mealInfo
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        // RFID Scan - Deduct amount based on time
        if (!empty($data->rfid_number)) {
            try {
                $db->beginTransaction();
                
                // Find employee by RFID
                $query = "SELECT * FROM employees WHERE rfid_number = :rfid_number";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':rfid_number', $data->rfid_number);
                $stmt->execute();
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$employee) {
                    http_response_code(404);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Employee not found',
                        'rfid' => $data->rfid_number
                    ]);
                    exit;
                }
                
                // Get current meal category
                $mealInfo = getMealCategory();
                
                if ($mealInfo['category'] === null) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => $mealInfo['error'],
                        'time_slot' => $mealInfo['time_slot'],
                        'current_time' => date('H:i:s'),
                        'employee' => [
                            'name' => $employee['emp_name'],
                            'wallet_balance' => $employee['wallet_amount']
                        ]
                    ]);
                    exit;
                }
                
                // Check if employee has sufficient balance
                if ($employee['wallet_amount'] < $mealInfo['amount']) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Insufficient wallet balance',
                        'required' => $mealInfo['amount'],
                        'available' => $employee['wallet_amount'],
                        'employee' => [
                            'name' => $employee['emp_name'],
                            'emp_id' => $employee['emp_id']
                        ]
                    ]);
                    exit;
                }
                
                // Deduct amount from wallet
                $newBalance = $employee['wallet_amount'] - $mealInfo['amount'];
                $updateQuery = "UPDATE employees SET wallet_amount = :new_balance WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':new_balance', $newBalance);
                $updateStmt->bindParam(':id', $employee['id']);
                $updateStmt->execute();
                
                // Log transaction
                $transactionQuery = "INSERT INTO transactions 
                    (employee_id, rfid_number, emp_id, emp_name, transaction_type, order_status, meal_category, amount, 
                     previous_balance, new_balance, transaction_time, transaction_date) 
                    VALUES 
                    (:employee_id, :rfid_number, :emp_id, :emp_name, 'deduction', :order_status, :meal_category, :amount, 
                     :previous_balance, :new_balance, :transaction_time, :transaction_date)";
                
                $transStmt = $db->prepare($transactionQuery);
                $transStmt->bindParam(':employee_id', $employee['id']);
                $transStmt->bindParam(':rfid_number', $employee['rfid_number']);
                $transStmt->bindParam(':emp_id', $employee['emp_id']);
                $transStmt->bindParam(':emp_name', $employee['emp_name']);
                $orderStatus = 'Pending';
                $transStmt->bindParam(':order_status', $orderStatus);
                $transStmt->bindParam(':meal_category', $mealInfo['category']);
                $transStmt->bindParam(':amount', $mealInfo['amount']);
                $transStmt->bindParam(':previous_balance', $employee['wallet_amount']);
                $transStmt->bindParam(':new_balance', $newBalance);
                $currentTime = date('H:i:s');
                $currentDate = date('Y-m-d');
                $transStmt->bindParam(':transaction_time', $currentTime);
                $transStmt->bindParam(':transaction_date', $currentDate);
                $transStmt->execute();

                $transactionId = $db->lastInsertId();
                
                $db->commit();
                
                // Success response
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Transaction successful',
                    'employee' => [
                        'name' => $employee['emp_name'],
                        'emp_id' => $employee['emp_id'],
                        'site' => $employee['site_name']
                    ],
                    'transaction' => [
                        'id' => $transactionId,
                        'meal_category' => $mealInfo['category'],
                        'amount_deducted' => $mealInfo['amount'],
                        'previous_balance' => $employee['wallet_amount'],
                        'new_balance' => $newBalance,
                        'time' => $currentTime,
                        'date' => $currentDate,
                        'order_status' => $orderStatus,
                        'time_slot' => $mealInfo['time_slot']
                    ]
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'RFID number is required'
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
?>
