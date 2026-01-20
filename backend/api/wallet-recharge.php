<?php
// Set timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Search employee by RFID or Employee ID
        if(isset($_GET['search'])) {
            $search = $_GET['search'];
            $query = "SELECT * FROM employees WHERE rfid_number = :search OR emp_id = :search";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':search', $search);
            $stmt->execute();
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($employee) {
                echo json_encode($employee);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Employee not found"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Search parameter required"));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        // Bulk recharge - recharge all employees
        if(isset($data->bulk_recharge) && $data->bulk_recharge === true) {
            if(!empty($data->amount)) {
                $query = "UPDATE employees SET wallet_amount = wallet_amount + :amount";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':amount', $data->amount);
                
                if($stmt->execute()) {
                    $affected_rows = $stmt->rowCount();
                    echo json_encode(array(
                        "message" => "Bulk recharge successful",
                        "employees_recharged" => $affected_rows,
                        "amount_added" => $data->amount
                    ));
                } else {
                    http_response_code(500);
                    echo json_encode(array("message" => "Unable to perform bulk recharge"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Amount is required"));
            }
        }
        // Individual recharge - recharge specific employee
        else if(isset($data->employee_id) && isset($data->amount)) {
            $query = "UPDATE employees SET wallet_amount = wallet_amount + :amount WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':amount', $data->amount);
            $stmt->bindParam(':id', $data->employee_id);
            
            if($stmt->execute()) {
                if($stmt->rowCount() > 0) {
                    // Get updated employee data
                    $query2 = "SELECT * FROM employees WHERE id = :id";
                    $stmt2 = $db->prepare($query2);
                    $stmt2->bindParam(':id', $data->employee_id);
                    $stmt2->execute();
                    $employee = $stmt2->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode(array(
                        "message" => "Wallet recharged successfully",
                        "employee" => $employee,
                        "amount_added" => $data->amount
                    ));
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Employee not found"));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Unable to recharge wallet"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid request data"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>
