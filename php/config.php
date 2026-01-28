<?php

/**
 * SmartStayz Configuration
 * Load environment variables from .env file
 */

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Skip lines without equals sign
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        
        $_ENV[$key] = $value;
    }
}

// Helper function to get env variable
if (!function_exists('getEnv')) {
    function getEnv($key, $default = null)
    {
        global $_ENV;
        
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'smartstayz_bookings');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'root');

// Stripe Configuration
// Dynamically set keys based on STRIPE_MODE
$stripeMode = $_ENV['STRIPE_MODE'] ?? getenv('STRIPE_MODE') ?: 'test';
if ($stripeMode === 'live') {
    if (!defined('STRIPE_SECRET_KEY')) define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY_LIVE'] ?? getenv('STRIPE_SECRET_KEY_LIVE') ?: '');
    if (!defined('STRIPE_PUBLIC_KEY')) define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY_LIVE'] ?? getenv('STRIPE_PUBLIC_KEY_LIVE') ?: '');
} else {
    if (!defined('STRIPE_SECRET_KEY')) define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY_TEST'] ?? getenv('STRIPE_SECRET_KEY_TEST') ?: '');
    if (!defined('STRIPE_PUBLIC_KEY')) define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY_TEST'] ?? getenv('STRIPE_PUBLIC_KEY_TEST') ?: '');
}

// BTCPay Server Configuration (for Bitcoin payments)
if (!defined('BTCPAY_SERVER_URL')) define('BTCPAY_SERVER_URL', $_ENV['BTCPAY_SERVER_URL'] ?? getenv('BTCPAY_SERVER_URL') ?: '');
if (!defined('BTCPAY_STORE_ID')) define('BTCPAY_STORE_ID', $_ENV['BTCPAY_STORE_ID'] ?? getenv('BTCPAY_STORE_ID') ?: '');
if (!defined('BTCPAY_API_KEY')) define('BTCPAY_API_KEY', $_ENV['BTCPAY_API_KEY'] ?? getenv('BTCPAY_API_KEY') ?: '');

// Email Configuration
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'info@smartstayz.com');
if (!defined('FROM_EMAIL')) define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? getenv('FROM_EMAIL') ?: 'info@smartstayz.com');
if (!defined('FROM_NAME')) define('FROM_NAME', $_ENV['FROM_NAME'] ?? getenv('FROM_NAME') ?: 'SmartStayz');

// Venmo/CashApp Information
if (!defined('VENMO_USERNAME')) define('VENMO_USERNAME', $_ENV['VENMO_USERNAME'] ?? getenv('VENMO_USERNAME') ?: '');
if (!defined('CASHAPP_USERNAME')) define('CASHAPP_USERNAME', $_ENV['CASHAPP_USERNAME'] ?? getenv('CASHAPP_USERNAME') ?: '');

// Property iCal URLs from Airbnb
// To get these URLs: 
// 1. Log into Airbnb host dashboard
// 2. Go to Calendar > Availability Settings
// 3. Click "Export Calendar" 
// 4. Copy the iCal link for each property

if (!defined('PROPERTY_ICAL_URLS')) define('PROPERTY_ICAL_URLS', [
    'stone' => $_ENV['PROPERTY_ICAL_STONE'] ?? '',
    'copper' => $_ENV['PROPERTY_ICAL_COPPER'] ?? '',
    'cedar' => $_ENV['PROPERTY_ICAL_CEDAR'] ?? ''
]);

// Property Information
if (!defined('PROPERTIES')) define('PROPERTIES', [
    'stone' => [
        'name' => 'The Stone',
        'airbnb_id' => '42680597',
        'airbnb_url' => 'https://www.airbnb.com/rooms/42680597',
        'nightly_rate' => 150,
        'cleaning_fee' => 75
    ],
    'copper' => [
        'name' => 'The Copper',
        'airbnb_id' => '44434858',
        'airbnb_url' => 'https://www.airbnb.com/h/melville-copper',
        'nightly_rate' => 150,
        'cleaning_fee' => 75
    ],
    'cedar' => [
        'name' => 'The Cedar',
        'airbnb_id' => '40961787',
        'airbnb_url' => 'https://www.airbnb.com/rooms/40961787',
        'nightly_rate' => 150,
        'cleaning_fee' => 75
    ]
]);

// Cache settings
if (!defined('CACHE_DIR')) define('CACHE_DIR', __DIR__ . '/cache');
if (!defined('CACHE_DURATION')) define('CACHE_DURATION', (int)($_ENV['CACHE_DURATION'] ?? 3600));

// Timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'America/New_York');

/**
 * Create cache directory if it doesn't exist
 */
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

/**
 * Database connection helper
 */
if (!function_exists('getDBConnection')) {

    function getDBConnection()
    {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Log function for debugging
 */
if (!function_exists('logMessage')) {
function logMessage($message, $level = 'INFO')
{
    $logFile = __DIR__ . '/logs/app.log';
    $logDir = dirname($logFile);

    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
}
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-errors.log');
