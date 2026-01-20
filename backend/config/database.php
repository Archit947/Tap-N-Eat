<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load environment variables
require_once __DIR__ . '/env_loader.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Get credentials from environment variables
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'qsr_system';
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // If connection fails and password is set, try without password as fallback
            if (!empty($this->password)) {
                try {
                    error_log("Retrying connection without password for user: " . $this->username);
                    $this->conn = new PDO(
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                        $this->username,
                        ''
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->conn->exec("set names utf8");
                    return $this->conn;
                } catch(PDOException $fallbackException) {
                    // Both attempts failed
                    error_log("Database connection error (both with and without password): " . $fallbackException->getMessage());
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'error' => 'Database connection failed',
                        'message' => $fallbackException->getMessage(),
                        'hint' => 'Check MySQL credentials in backend/.env file'
                    ]);
                    exit();
                }
            }
            
            // No fallback, just report the error
            error_log("Database connection error: " . $exception->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Database connection failed',
                'message' => $exception->getMessage(),
                'hint' => 'Check MySQL credentials in backend/.env file'
            ]);
            exit();
        }

        return $this->conn;
    }
}

/* ===============================
   HELPER FUNCTION FOR NEW CODE
=============================== */
function getConnection() {
    $db = new Database();
    return $db->getConnection();
}
?>
