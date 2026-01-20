<?php
/**
 * Simple Printer Test
 * Sends a basic test print to the thermal printer
 */

header('Content-Type: application/json');

include_once '../config/database.php';

class SimplePrinterTest {
    private $printerIP;
    private $printerPort;
    private $timeout = 5;
    
    public function __construct() {
        $this->printerIP = getenv('PRINTER_IP') ?: '192.168.0.1';
        $this->printerPort = getenv('PRINTER_PORT') ?: 9100;
    }
    
    public function testConnection() {
        $socket = @fsockopen($this->printerIP, $this->printerPort, $errno, $errstr, $this->timeout);
        
        if (!$socket) {
            return [
                'status' => 'error',
                'message' => "Cannot connect to printer at {$this->printerIP}:{$this->printerPort}",
                'error_details' => "$errstr (Code: $errno)"
            ];
        }
        
        fclose($socket);
        return [
            'status' => 'success',
            'message' => "Printer at {$this->printerIP}:{$this->printerPort} is online"
        ];
    }
    
    public function sendTestPrint() {
        $socket = @fsockopen($this->printerIP, $this->printerPort, $errno, $errstr, $this->timeout);
        
        if (!$socket) {
            return [
                'status' => 'error',
                'message' => "Cannot connect to printer"
            ];
        }
        
        // Build test receipt
        $receipt = "\x1B\x40"; // Initialize
        $receipt .= "\x1B\x61\x01"; // Center
        $receipt .= "\x1B\x45\x01"; // Bold
        $receipt .= "PRINTER TEST\n";
        $receipt .= "\x1B\x45\x00"; // Bold off
        $receipt .= "\x1B\x61\x01";
        $receipt .= "Epson TM-T20II\n";
        $receipt .= str_repeat("-", 32) . "\n";
        $receipt .= "\x1B\x61\x00"; // Left align
        $receipt .= "If you see this,\n";
        $receipt .= "printer is working!\n";
        $receipt .= "\x1B\x61\x01"; // Center
        $receipt .= "\n\nTest Complete\n";
        $receipt .= str_repeat("-", 32) . "\n\n\n";
        $receipt .= "\x1D\x56\x00"; // Cut
        
        fwrite($socket, $receipt);
        sleep(2);
        fclose($socket);
        
        return [
            'status' => 'success',
            'message' => 'Test print sent to printer'
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? 'test';
    $tester = new SimplePrinterTest();
    
    if ($action === 'connection') {
        echo json_encode($tester->testConnection());
    } else if ($action === 'print') {
        echo json_encode($tester->sendTestPrint());
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    echo json_encode([
        'message' => 'Printer test tool',
        'usage' => [
            'connection' => 'POST /api/printer-test.php?action=connection',
            'print' => 'POST /api/printer-test.php?action=print'
        ]
    ]);
}
?>
