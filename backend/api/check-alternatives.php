<?php
/**
 * Alternative: Print QR without GD library
 * Uses simpler text-based QR or uploads to printer differently
 */

// Test if we can use system-level tools
echo "GD Status: " . (extension_loaded('gd') ? "Available" : "NOT INSTALLED") . "\n";
echo "ImageMagick: " . (extension_loaded('imagick') ? "Available" : "Not available") . "\n";

// Check if we have exec available (for system commands)
echo "exec() available: " . (function_exists('exec') ? "Yes" : "No") . "\n";

// Try to generate QR using online service differently
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=test";
$imageData = @file_get_contents($qrUrl);
echo "QR Download: " . strlen($imageData ?? '') . " bytes\n";

// Can we use fsockopen to send raw image data to printer?
echo "fsockopen: " . (function_exists('fsockopen') ? "Yes" : "No") . "\n";

// Check all disabled functions
$disabled = explode(",", ini_get('disable_functions'));
echo "\nDisabled functions: " . count($disabled) . "\n";
if (in_array('exec', $disabled) || in_array('system', $disabled)) {
    echo "System functions are DISABLED\n";
}

echo "\nPHP Version: " . phpversion() . "\n";
echo "OS: " . php_uname() . "\n";
?>
