<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Load environment variables
require_once __DIR__ . '/config/env_loader.php';

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
$db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'qsr_system';

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create transactions table
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        rfid_number VARCHAR(50) NOT NULL,
        emp_id VARCHAR(50) NOT NULL,
        emp_name VARCHAR(100) NOT NULL,
        transaction_type ENUM('deduction', 'recharge') NOT NULL,
        order_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        meal_category VARCHAR(50) NULL,
        amount DECIMAL(10, 2) NOT NULL,
        previous_balance DECIMAL(10, 2) NOT NULL,
        new_balance DECIMAL(10, 2) NOT NULL,
        transaction_time TIME NOT NULL,
        transaction_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Transactions table created successfully!',
        'table' => 'transactions',
        'next_step' => 'You can now scan RFID cards for meal deductions'
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
