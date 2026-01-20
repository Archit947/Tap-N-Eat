<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Load environment variables
require_once __DIR__ . '/config/env_loader.php';

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'qsr_system';

try {
    // Connect without database
    $conn = new PDO("mysql:host=" . $host, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $conn->exec("USE `$db_name`");
    
    // Create employees table
    $sql = "CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rfid_number VARCHAR(50) NOT NULL UNIQUE,
        emp_id VARCHAR(50) NOT NULL UNIQUE,
        emp_name VARCHAR(100) NOT NULL,
        site_name VARCHAR(100) NOT NULL,
        shift VARCHAR(50) NOT NULL,
        wallet_amount DECIMAL(10, 2) DEFAULT 0.00,
        razorpay_wallet_id VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    
    // Create admin_users table
    $sql2 = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql2);
    
    // Insert default admin if not exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $sql3 = "INSERT INTO admin_users (username, password, email) VALUES 
                ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@qsr.com')";
        $conn->exec($sql3);
    }
    
    // Insert sample employees if table is empty
    $stmt = $conn->prepare("SELECT COUNT(*) FROM employees");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $sql4 = "INSERT INTO employees (rfid_number, emp_id, emp_name, site_name, shift, wallet_amount) VALUES
                ('RFID001', 'EMP001', 'John Doe', 'Site A', 'Morning', 1000.00),
                ('RFID002', 'EMP002', 'Jane Smith', 'Site B', 'Evening', 500.00),
                ('RFID003', 'EMP003', 'Mike Johnson', 'Site A', 'Night', 750.00)";
        $conn->exec($sql4);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database setup complete!',
        'database' => $db_name,
        'tables' => ['employees', 'admin_users'],
        'next' => 'Refresh your React app at http://localhost:5173'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'help' => 'Check your MySQL credentials in backend/.env file'
    ]);
}
?>
