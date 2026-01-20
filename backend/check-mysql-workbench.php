<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>MySQL Workbench Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; }
        .error { color: red; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; }
        .info { color: #004085; padding: 15px; background: #cce5ff; border: 1px solid #b8daff; border-radius: 5px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h1>🔌 MySQL Workbench Connection Setup</h1>

<?php
// Load environment variables
require_once __DIR__ . '/config/env_loader.php';

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
$db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'qsr_system';

echo "<div class='info'>";
echo "<h3>📋 Current Configuration (.env file):</h3>";
echo "<pre>";
echo "DB_HOST: $host\n";
echo "DB_USER: $username\n";
echo "DB_PASSWORD: " . (empty($password) ? '(empty)' : str_repeat('*', strlen($password))) . "\n";
echo "DB_NAME: $db_name";
echo "</pre>";
echo "</div>";

// Step 1: Test MySQL Connection
echo "<h2>Step 1: Testing MySQL Connection</h2>";
try {
    $conn = new PDO("mysql:host=" . $host, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Successfully connected to MySQL server!</div>";
    
    // Step 2: Check if database exists
    echo "<h2>Step 2: Checking Database</h2>";
    $stmt = $conn->query("SHOW DATABASES LIKE '$db_name'");
    $dbExists = $stmt->rowCount() > 0;
    
    if ($dbExists) {
        echo "<div class='success'>✅ Database '$db_name' exists in MySQL Workbench!</div>";
        
        // Step 3: Check tables
        echo "<h2>Step 3: Checking Tables</h2>";
        $conn->exec("USE `$db_name`");
        
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<div class='success'>✅ Found " . count($tables) . " table(s):</div>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
            
            // Check if employees table exists
            if (in_array('employees', $tables)) {
                $stmt = $conn->query("DESCRIBE employees");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo "<h3>Employees Table Structure:</h3>";
                echo "<pre>";
                $stmt = $conn->query("DESCRIBE employees");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo $row['Field'] . " - " . $row['Type'] . "\n";
                }
                echo "</pre>";
                
                // Check if wallet_amount column exists
                if (in_array('wallet_amount', $columns)) {
                    echo "<div class='success'>✅ wallet_amount column exists!</div>";
                } else {
                    echo "<div class='error'>⚠️ wallet_amount column is missing!</div>";
                    echo "<p>Run this in MySQL Workbench:</p>";
                    echo "<pre>ALTER TABLE employees ADD COLUMN wallet_amount DECIMAL(10, 2) DEFAULT 0.00;</pre>";
                }
                
                // Count employees
                $stmt = $conn->query("SELECT COUNT(*) FROM employees");
                $count = $stmt->fetchColumn();
                echo "<div class='info'>📊 Total employees: $count</div>";
                
            } else {
                echo "<div class='error'>⚠️ 'employees' table not found!</div>";
                echo "<h3>Create the table in MySQL Workbench:</h3>";
                echo "<button onclick=\"document.getElementById('createTableSQL').style.display='block'\">Show SQL</button>";
                echo "<pre id='createTableSQL' style='display:none'>";
                echo file_get_contents(__DIR__ . '/database.sql');
                echo "</pre>";
            }
            
        } else {
            echo "<div class='error'>⚠️ Database exists but has no tables!</div>";
            echo "<h3>Import tables in MySQL Workbench:</h3>";
            echo "<ol>";
            echo "<li>Open MySQL Workbench</li>";
            echo "<li>Connect to localhost</li>";
            echo "<li>Select database: <code>USE qsr_system;</code></li>";
            echo "<li>Run SQL Script: <code>backend/database.sql</code></li>";
            echo "</ol>";
        }
        
        echo "<h2>✅ Connection Successful!</h2>";
        echo "<div class='success'>";
        echo "<p>Your PHP backend is now connected to MySQL Workbench database!</p>";
        echo "<p><a href='http://localhost:8000/api/employees.php' target='_blank'>Test API: employees.php</a></p>";
        echo "<p><a href='http://localhost:5173' target='_blank'>Open React App</a></p>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>⚠️ Database '$db_name' does not exist!</div>";
        echo "<h3>Create it in MySQL Workbench:</h3>";
        echo "<ol>";
        echo "<li>Open MySQL Workbench</li>";
        echo "<li>Connect to your local MySQL server</li>";
        echo "<li>Run this SQL:</li>";
        echo "</ol>";
        echo "<pre>CREATE DATABASE qsr_system;</pre>";
        echo "<p>Then <a href=''>refresh this page</a></p>";
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Connection Failed!</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Make sure MySQL Workbench is connected (check if MySQL80 service is running)</li>";
    echo "<li>Verify credentials in <code>backend/.env</code> file</li>";
    echo "<li>Check if password is correct: <code>Pa@24224365</code></li>";
    echo "<li>Try connecting manually in MySQL Workbench first</li>";
    echo "</ol>";
    echo "</div>";
}
?>

</body>
</html>
