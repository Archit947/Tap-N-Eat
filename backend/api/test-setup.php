<?php
/**
 * Test script - Check if everything is set up correctly
 */

date_default_timezone_set('Asia/Kolkata');
header('Content-Type: text/html; charset=utf-8');

echo "<h1>QSR Print System - Setup Check</h1>";

// Test 1: Check if config files exist
echo "<h2>1. Config Files</h2>";
if (file_exists('../config/database.php')) {
    echo "✅ database.php exists<br>";
} else {
    echo "❌ database.php NOT found<br>";
}

if (file_exists('../config/env_loader.php')) {
    echo "✅ env_loader.php exists<br>";
} else {
    echo "❌ env_loader.php NOT found<br>";
}

// Test 2: Try to load database
echo "<h2>2. Database Connection</h2>";
try {
    require_once '../config/env_loader.php';
    require_once '../config/database.php';
    
    // Try using Database class
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "✅ Database connection successful<br>";
    
    // Test 3: Check if print_queue table exists
    echo "<h2>3. Print Queue Table</h2>";
    $result = $pdo->query("SHOW TABLES LIKE 'print_queue'");
    if ($result->rowCount() > 0) {
        echo "✅ print_queue table exists<br>";
        
        // Check table structure
        $cols = $pdo->query("DESCRIBE print_queue");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($col = $cols->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ print_queue table NOT found<br>";
        echo "<p><a href='../create-print-queue.php'>Click here to create table</a></p>";
    }
    
    // Test 4: Check API files
    echo "<h2>4. API Files</h2>";
    if (file_exists('print-queue.php')) {
        echo "✅ print-queue.php exists<br>";
    } else {
        echo "❌ print-queue.php NOT found<br>";
    }
    
    if (file_exists('print-thermal.php')) {
        echo "✅ print-thermal.php exists<br>";
    } else {
        echo "❌ print-thermal.php NOT found<br>";
    }
    
    // Test 5: Test receipt.php
    echo "<h2>5. Receipt Page</h2>";
    if (file_exists('../receipt.php')) {
        echo "✅ receipt.php exists<br>";
    } else {
        echo "❌ receipt.php NOT found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If print_queue table doesn't exist, run: <a href='../create-print-queue.php'>create-print-queue.php</a></li>";
echo "<li>Start your local polling service on your PC</li>";
echo "<li>Scan RFID card to test</li>";
echo "</ol>";
?>
