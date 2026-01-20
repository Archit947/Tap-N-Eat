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
    // First, connect without database to create it
    $conn = new PDO("mysql:host=" . $host, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    echo json_encode([
        'status' => 'success',
        'message' => "Database '$db_name' is ready",
        'step' => 'Now import the tables from database.sql file'
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
