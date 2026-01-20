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
    
    echo json_encode([
        'status' => 'starting',
        'message' => 'Checking database structure...'
    ]);
    echo "\n\n";
    
    // Check if wallet_amount column exists
    $stmt = $conn->query("DESCRIBE employees");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('wallet_amount', $columns)) {
        // Add wallet_amount column
        $conn->exec("ALTER TABLE employees ADD COLUMN wallet_amount DECIMAL(10, 2) DEFAULT 0.00");
        echo json_encode([
            'status' => 'success',
            'action' => 'added wallet_amount column',
            'message' => 'wallet_amount column added successfully!'
        ]);
        echo "\n\n";
    } else {
        echo json_encode([
            'status' => 'info',
            'message' => 'wallet_amount column already exists'
        ]);
        echo "\n\n";
    }
    
    // Check if razorpay_wallet_id column exists
    if (!in_array('razorpay_wallet_id', $columns)) {
        $conn->exec("ALTER TABLE employees ADD COLUMN razorpay_wallet_id VARCHAR(100) NULL");
        echo json_encode([
            'status' => 'success',
            'action' => 'added razorpay_wallet_id column',
            'message' => 'razorpay_wallet_id column added successfully!'
        ]);
        echo "\n\n";
    } else {
        echo json_encode([
            'status' => 'info',
            'message' => 'razorpay_wallet_id column already exists'
        ]);
        echo "\n\n";
    }
    
    // Show final table structure
    $stmt = $conn->query("DESCRIBE employees");
    $tableStructure = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tableStructure[] = $row['Field'] . ' (' . $row['Type'] . ')';
    }
    
    echo json_encode([
        'status' => 'complete',
        'message' => 'Database migration completed!',
        'table_structure' => $tableStructure,
        'next_step' => 'Refresh your React app at http://localhost:5173'
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
