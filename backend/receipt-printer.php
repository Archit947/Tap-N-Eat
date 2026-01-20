<?php
/**
 * Receipt Printer - ESC/POS Thermal Printer Handler
 * Handles all receipt printing logic for Epson TM-T20III
 */

class ReceiptPrinter {
    private $printerIp;
    private $printerPort;
    private $receiptBaseUrl;

    public function __construct() {
        require_once __DIR__ . '/config/env_loader.php';
        
        $this->printerIp = $_ENV['PRINTER_IP'] ?? getenv('PRINTER_IP') ?: '192.168.0.105';
        $this->printerPort = (int)($_ENV['PRINTER_PORT'] ?? getenv('PRINTER_PORT') ?: 9100);
        $this->receiptBaseUrl = $_ENV['RECEIPT_BASE_URL'] ?? getenv('RECEIPT_BASE_URL') ?: 'https://yourdomain.com/receipt.php?id=';
    }

    /**
     * Generate QR code bitmap using online API
     */
    private function generateQRBitmap($text, $size = 200) {
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=" . $size . "x" . $size . "&data=" . urlencode($text);
        
        $qrImage = @file_get_contents($qrApiUrl);
        if (!$qrImage) {
            return null;
        }
        
        $img = imagecreatefromstring($qrImage);
        if (!$img) {
            return null;
        }
        
        $width = imagesx($img);
        $height = imagesy($img);
        
        // Resize to thermal printer width (~384 pixels for 58mm @ 8 dots/mm)
        $targetWidth = 384;
        $resized = imagecreatetruecolor($targetWidth, $targetWidth);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $targetWidth, $targetWidth, $width, $height);
        
        // Convert to 1-bit monochrome
        imagefilter($resized, IMG_FILTER_GRAYSCALE);
        imagefilter($resized, IMG_FILTER_CONTRAST, 10);
        
        $bitmap = [];
        for ($y = 0; $y < $targetWidth; $y++) {
            $row = '';
            for ($x = 0; $x < $targetWidth; $x += 8) {
                $byte = 0;
                for ($i = 0; $i < 8 && $x + $i < $targetWidth; $i++) {
                    $pixel = imagecolorat($resized, $x + $i, $y);
                    $rgb = imagecolorsforindex($resized, $pixel);
                    $gray = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
                    if ($gray < 128) {
                        $byte |= (1 << (7 - $i));
                    }
                }
                $row .= chr($byte);
            }
            $bitmap[] = $row;
        }
        
        imagedestroy($img);
        imagedestroy($resized);
        
        return [
            'width' => $targetWidth,
            'height' => $targetWidth,
            'data' => $bitmap
        ];
    }

    /**
     * Send receipt to thermal printer via TCP/IP
     */
    public function printReceipt(array $employee, array $transaction) {
        $qrUrl = $this->receiptBaseUrl . urlencode($transaction['id']);

        $fp = @fsockopen($this->printerIp, $this->printerPort, $errno, $errstr, 5);
        if (!$fp) {
            return [
                'ok' => false,
                'message' => "Printer connection failed: $errstr ($errno)",
            ];
        }

        $w = function ($data) use ($fp) {
            fwrite($fp, $data);
        };

        try {
            // Reset
            $w("\x1B\x40");
            // Center
            $w("\x1B\x61\x01");

            // Header
            $w("CATALYST\n");
            $w("PARTNERING FOR SUSTAINABILITY\n");
            $w("--------------------------------\n");

            // Meal title
            $w($transaction['meal_category'] . "\n");
            $w("--------------------------------\n");

            // Left align details
            $w("\x1B\x61\x00");
            $w("Employee: {$employee['name']}\n");
            $w("Emp ID  : {$employee['emp_id']}\n");
            $w("Site    : {$employee['site']}\n");
            $w("Time    : {$transaction['time']}\n");
            $w("Date    : {$transaction['date']}\n");
            $w("--------------------------------\n");

            // Amount
            $w("Amount: Rs. {$transaction['amount']}\n");
            $w("--------------------------------\n");

            // Balance
            $w("Available Balance\n");
            $w("Rs. {$transaction['balance']}\n");
            $w("--------------------------------\n");

            // Generate and print QR bitmap
            $qrBitmap = $this->generateQRBitmap($qrUrl, 200);
            if ($qrBitmap) {
                // Center image
                $w("\x1B\x61\x01");
                
                // Print as raster image (ESC/POS raster mode)
                $width = $qrBitmap['width'];
                $widthBytes = $width / 8;
                $height = $qrBitmap['height'];
                
                // Set raster mode: GS v 0 (print raster image)
                $w("\x1D\x76\x30\x00");
                // Width low/high
                $w(chr($widthBytes & 0xFF) . chr(($widthBytes >> 8) & 0xFF));
                // Height low/high
                $w(chr($height & 0xFF) . chr(($height >> 8) & 0xFF));
                
                // Send bitmap data
                foreach ($qrBitmap['data'] as $row) {
                    $w($row);
                }
            } else {
                // Fallback: print URL as text with label
                $w("\x1B\x61\x01");
                $w("SCAN FOR RECEIPT\n");
                $w($qrUrl . "\n");
            }

            // Feed and cut
            $w("\n\n");
            $w("\x1D\x56\x01");

            fclose($fp);

            return [
                'ok' => true,
                'message' => 'Receipt printed successfully',
                'printer_ip' => $this->printerIp,
                'printer_port' => $this->printerPort,
                'qr_url' => $qrUrl,
            ];
        } catch (Exception $e) {
            fclose($fp);
            return [
                'ok' => false,
                'message' => 'Print error: ' . $e->getMessage(),
            ];
        }
    }

    public function getPrinterInfo() {
        return [
            'ip' => $this->printerIp,
            'port' => $this->printerPort,
            'base_url' => $this->receiptBaseUrl,
        ];
    }
}
?>
