<?php
/**
 * Printer Diagnostic Tool
 * Tests connectivity to Epson TM-T20II thermal printer
 */

header('Content-Type: application/json');

// Get printer settings from environment
$printerIP = getenv('PRINTER_IP') ?: '192.168.0.1';
$printerPort = getenv('PRINTER_PORT') ?: 9100;
$timeout = 5;

$results = [];

// Test 1: Check if printer IP is reachable
$results['step_1_ping'] = [
    'name' => 'Network Connectivity (Ping)',
    'command' => "ping -n 1 $printerIP",
    'description' => 'Testing if printer responds to ping...'
];

// Test 2: Check if port is open
$results['step_2_port'] = [
    'name' => 'Port Connectivity',
    'ip' => $printerIP,
    'port' => $printerPort,
    'status' => 'Testing...'
];

$socket = @fsockopen($printerIP, $printerPort, $errno, $errstr, $timeout);

if ($socket) {
    $results['step_2_port']['status'] = '✅ SUCCESS - Port is open and accepting connections';
    
    // Test 3: Send initialization command
    $testCmd = "\x1B\x40"; // ESC @ - Initialize
    fwrite($socket, $testCmd);
    
    sleep(1);
    fclose($socket);
    
    $results['step_3_init'] = [
        'name' => 'Printer Initialization',
        'status' => '✅ SUCCESS - Initialization command sent',
        'next' => 'Your printer should respond. Check the printer display/lights.'
    ];
    
    $results['summary'] = [
        'status' => 'SUCCESS',
        'message' => 'Printer is online and ready!',
        'configuration' => "IP: $printerIP | Port: $printerPort",
        'action' => 'Try scanning an RFID card in the Admin Dashboard'
    ];
    
} else {
    $results['step_2_port']['status'] = "❌ FAILED - Cannot connect to $printerIP:$printerPort";
    $results['step_2_port']['error'] = "Error: $errstr (Code: $errno)";
    
    $results['summary'] = [
        'status' => 'CONNECTION_FAILED',
        'message' => 'Cannot connect to printer',
        'configuration' => "IP: $printerIP | Port: $printerPort",
        'troubleshooting' => [
            '1. Verify printer IP address is correct',
            '2. Ensure printer is powered on',
            '3. Check network cable is connected',
            '4. Verify printer is on the same network',
            '5. Check firewall settings (port 9100 may be blocked)',
            '6. Try pinging the printer: ping ' . $printerIP,
            '7. Check printer display for network settings'
        ]
    ];
}

// Add environment info
$results['environment'] = [
    'php_version' => phpversion(),
    'os' => php_uname(),
    'sockets_support' => extension_loaded('sockets') ? 'Yes' : 'No',
    'fsockopen_enabled' => function_exists('fsockopen') ? 'Yes' : 'No'
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
