<?php
require_once 'config/env_loader.php';
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
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
    
    echo "SUCCESS: Transactions table created!\n";
    
    // Verify table exists
    $result = $conn->query("SHOW TABLES LIKE 'transactions'");
    if ($result->rowCount() > 0) {
        echo "VERIFIED: Transactions table exists in database.\n";
    }
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
