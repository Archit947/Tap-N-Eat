<?php
/**
 * Check PHP GD Library and Image Support
 */

header('Content-Type: application/json');

$checks = [];

// Check GD library
$checks['gd_loaded'] = extension_loaded('gd');
$checks['imagecreatetruecolor'] = function_exists('imagecreatetruecolor');
$checks['imagecreatefromstring'] = function_exists('imagecreatefromstring');
$checks['imagecopyresampled'] = function_exists('imagecopyresampled');
$checks['imagecolorat'] = function_exists('imagecolorat');

// Check allow_url_fopen for external image downloading
$checks['allow_url_fopen'] = ini_get('allow_url_fopen') ? true : false;

// Try to download QR
$checks['network'] = [];
$testUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=test";
$context = stream_context_create(['http' => ['timeout' => 5], 'https' => ['timeout' => 5]]);
$imageData = @file_get_contents($testUrl, false, $context);

if ($imageData && strlen($imageData) > 100) {
    $checks['network']['qr_download'] = 'success - ' . strlen($imageData) . ' bytes';
    
    // Try to create image
    $img = @imagecreatefromstring($imageData);
    if ($img) {
        $checks['network']['image_creation'] = 'success - ' . imagesx($img) . 'x' . imagesy($img);
        imagedestroy($img);
    } else {
        $checks['network']['image_creation'] = 'failed';
    }
} else {
    $checks['network']['qr_download'] = 'failed - no data';
}

// PHP version
$checks['php_version'] = phpversion();

// System
$checks['os'] = php_uname();

echo json_encode($checks, JSON_PRETTY_PRINT);
?>
