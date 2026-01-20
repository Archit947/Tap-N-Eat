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
        if(isset($_GET['id'])) {
            // Get single employee
            $id = $_GET['id'];
            $query = "SELECT * FROM employees WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($employee) {
                echo json_encode($employee);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Employee not found"));
            }
        } else {
            // Get all employees
            $query = "SELECT * FROM employees ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($employees);
        }
        break;

    case 'POST':
        // Create new employee
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->rfid_number) && !empty($data->emp_id) && !empty($data->emp_name) && 
           !empty($data->site_name) && !empty($data->shift)) {
            
            try {
                // Set default wallet amount if not provided
                $wallet_amount = isset($data->wallet_amount) ? $data->wallet_amount : 0.00;
                
                $query = "INSERT INTO employees (rfid_number, emp_id, emp_name, site_name, shift, wallet_amount) 
                          VALUES (:rfid_number, :emp_id, :emp_name, :site_name, :shift, :wallet_amount)";
                
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(':rfid_number', $data->rfid_number);
                $stmt->bindParam(':emp_id', $data->emp_id);
                $stmt->bindParam(':emp_name', $data->emp_name);
                $stmt->bindParam(':site_name', $data->site_name);
                $stmt->bindParam(':shift', $data->shift);
                $stmt->bindParam(':wallet_amount', $wallet_amount);
                
                if($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Employee created successfully",
                        "id" => $db->lastInsertId()
                    ));
                } else {
                    http_response_code(500);
                    echo json_encode(array("message" => "Unable to create employee"));
                }
            } catch(PDOException $e) {
                http_response_code(400);
                if($e->getCode() == 23000) {
                    // Duplicate entry error
                    if(strpos($e->getMessage(), 'rfid_number') !== false) {
                        echo json_encode(array("message" => "RFID number already exists"));
                    } elseif(strpos($e->getMessage(), 'emp_id') !== false) {
                        echo json_encode(array("message" => "Employee ID already exists"));
                    } else {
                        echo json_encode(array("message" => "Duplicate entry"));
                    }
                } else {
                    echo json_encode(array("message" => "Database error: " . $e->getMessage()));
                }
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data"));
        }
        break;

    case 'PUT':
        // Update employee
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $query = "UPDATE employees SET 
                      rfid_number = :rfid_number,
                      emp_id = :emp_id,
                      emp_name = :emp_name,
                      site_name = :site_name,
                      shift = :shift,
                      wallet_amount = :wallet_amount
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            
            $wallet_amount = isset($data->wallet_amount) ? $data->wallet_amount : 0.00;
            
            $stmt->bindParam(':id', $data->id);
            $stmt->bindParam(':rfid_number', $data->rfid_number);
            $stmt->bindParam(':emp_id', $data->emp_id);
            $stmt->bindParam(':emp_name', $data->emp_name);
            $stmt->bindParam(':site_name', $data->site_name);
            $stmt->bindParam(':shift', $data->shift);
            $stmt->bindParam(':wallet_amount', $wallet_amount);
            
            if($stmt->execute()) {
                echo json_encode(array("message" => "Employee updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Unable to update employee"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing employee ID"));
        }
        break;

    case 'DELETE':
        // Delete employee
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $query = "DELETE FROM employees WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data->id);
            
            if($stmt->execute()) {
                echo json_encode(array("message" => "Employee deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Unable to delete employee"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing employee ID"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>
