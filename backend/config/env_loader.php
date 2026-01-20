<?php
// Simple .env file loader
function loadEnv($path) {
    if (!file_exists($path)) {
        error_log("Warning: .env file not found at " . $path);
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip empty lines and comments
        $trimmed = trim($line);
        if (empty($trimmed) || strpos($trimmed, '#') === 0) {
            continue;
        }

        // Parse line
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Skip empty names or values that are just whitespace
            if (!empty($name) && !empty($value)) {
                // Set environment variable
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
    }
    return true;
}

// Load .env file
$envPath = __DIR__ . '/../.env';
if (!loadEnv($envPath)) {
    error_log("Failed to load .env file from: " . $envPath);
}
?>
