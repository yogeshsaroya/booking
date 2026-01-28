<?php

/**
 * SmartStayz Configuration
 * Update these values with your actual credentials and settings
 */

// Database Configuration (if you want to store bookings)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'smartstayz_bookings');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'root');


// Stripe Configuration
if (!defined('STRIPE_SECRET_KEY')) define('STRIPE_SECRET_KEY', 'sk_test_51SsksaCm3m1aQwAM');
if (!defined('STRIPE_PUBLIC_KEY')) define('STRIPE_PUBLIC_KEY', 'pk_test_51SsksaCm3m1aQwAMQWOohMVFKgeNOJ5cbAXJKxC8yrIoLf0ciWkDNN7d4HuzA4Wv7uHroLnsC6E4e5SzzkQ0DxtD00cVn1SGau');

// BTCPay Server Configuration (for Bitcoin payments)
if (!defined('BTCPAY_SERVER_URL')) define('BTCPAY_SERVER_URL', 'https://your-btcpay-server.com');
if (!defined('BTCPAY_STORE_ID')) define('BTCPAY_STORE_ID', '');
if (!defined('BTCPAY_API_KEY')) define('BTCPAY_API_KEY', '');

// Email Configuration
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'info@smartstayz.com');
if (!defined('FROM_EMAIL')) define('FROM_EMAIL', 'info@smartstayz.com');
if (!defined('FROM_NAME')) define('FROM_NAME', 'SmartStayz');

// Venmo/CashApp Information
if (!defined('VENMO_USERNAME')) define('VENMO_USERNAME', '');
if (!defined('CASHAPP_USERNAME')) define('CASHAPP_USERNAME', '');

// Property iCal URLs from Airbnb
// To get these URLs: 
// 1. Log into Airbnb host dashboard
// 2. Go to Calendar > Availability Settings
// 3. Click "Export Calendar" 
// 4. Copy the iCal link for each property

if (!defined('PROPERTY_ICAL_URLS')) define('PROPERTY_ICAL_URLS', [
    'stone' => 'https://www.airbnb.com/calendar/ical/42680597.ics?t=11f3e596bb3745e2b7f8fd08d205da81',
    'copper' => 'https://www.airbnb.com/calendar/ical/44434858.ics?t=96ceb60ec506418b82d6cd93014b0654',
    'cedar' => 'https://www.airbnb.com/calendar/ical/40961787.ics?t=13020d9706a64e2a84d6152569802d81'
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
if (!defined('CACHE_DURATION')) define('CACHE_DURATION', 3600); // 1 hour in seconds

// Timezone
date_default_timezone_set('America/New_York');

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
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-errors.log');
