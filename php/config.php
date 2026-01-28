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
function getEnv($key, $default = null)
{
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    $envValue = getenv($key);
    if ($envValue !== false && $envValue !== '') {
        return $envValue;
    }
    return $default;
}

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', getEnv('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', getEnv('DB_NAME', 'smartstayz_bookings'));
if (!defined('DB_USER')) define('DB_USER', getEnv('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', getEnv('DB_PASS', 'root'));

// Stripe Configuration
if (!defined('STRIPE_SECRET_KEY')) define('STRIPE_SECRET_KEY', getEnv('STRIPE_SECRET_KEY', ''));
if (!defined('STRIPE_PUBLIC_KEY')) define('STRIPE_PUBLIC_KEY', getEnv('STRIPE_PUBLIC_KEY', ''));

// BTCPay Server Configuration (for Bitcoin payments)
if (!defined('BTCPAY_SERVER_URL')) define('BTCPAY_SERVER_URL', getEnv('BTCPAY_SERVER_URL', ''));
if (!defined('BTCPAY_STORE_ID')) define('BTCPAY_STORE_ID', getEnv('BTCPAY_STORE_ID', ''));
if (!defined('BTCPAY_API_KEY')) define('BTCPAY_API_KEY', getEnv('BTCPAY_API_KEY', ''));

// Email Configuration
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', getEnv('ADMIN_EMAIL', 'info@smartstayz.com'));
if (!defined('FROM_EMAIL')) define('FROM_EMAIL', getEnv('FROM_EMAIL', 'info@smartstayz.com'));
if (!defined('FROM_NAME')) define('FROM_NAME', getEnv('FROM_NAME', 'SmartStayz'));

// Venmo/CashApp Information
if (!defined('VENMO_USERNAME')) define('VENMO_USERNAME', getEnv('VENMO_USERNAME', ''));
if (!defined('CASHAPP_USERNAME')) define('CASHAPP_USERNAME', getEnv('CASHAPP_USERNAME', ''));

// Property iCal URLs from Airbnb
// To get these URLs: 
// 1. Log into Airbnb host dashboard
// 2. Go to Calendar > Availability Settings
// 3. Click "Export Calendar" 
// 4. Copy the iCal link for each property

if (!defined('PROPERTY_ICAL_URLS')) define('PROPERTY_ICAL_URLS', [
    'stone' => getEnv('PROPERTY_ICAL_STONE', ''),
    'copper' => getEnv('PROPERTY_ICAL_COPPER', ''),
    'cedar' => getEnv('PROPERTY_ICAL_CEDAR', '')
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
if (!defined('CACHE_DURATION')) define('CACHE_DURATION', (int)getEnv('CACHE_DURATION', 3600));

// Timezone
date_default_timezone_set(getEnv('TIMEZONE', 'America/New_York'));

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
 * Send email helper
 */
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $message, $headers = [])
    {
        $defaultHeaders = [
            'From' => FROM_NAME . ' <' . FROM_EMAIL . '>',
            'Reply-To' => FROM_EMAIL,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];

        $headers = array_merge($defaultHeaders, $headers);

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }

        return mail($to, $subject, $message, $headerString);
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
