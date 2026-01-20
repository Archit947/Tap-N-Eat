<?php
/**
 * Create print_queue table for polling-based printing
 * Run this once to create the table
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config/database.php';

try {
    // Use Database class method
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS print_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_name VARCHAR(255) NOT NULL,
        employee_id VARCHAR(50) NOT NULL,
        transaction_id INT,
        meal_type VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        balance DECIMAL(10,2) NOT NULL,
        timestamp DATETIME NOT NULL,
        qr_url TEXT,
        status ENUM('pending', 'printing', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        printed_at TIMESTAMP NULL,
        error_message TEXT NULL,0013332470
        
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    
    echo "✅ print_queue table created successfully!\n";
    
} catch (PDOException $e) {
    die("❌ Error creating table: " . $e->getMessage() . "\n");
}
