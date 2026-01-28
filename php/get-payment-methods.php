<?php
/**
 * Get enabled payment methods based on configuration
 */

require_once 'config.php';

header('Content-Type: application/json');

// Check if payment methods are configured
$paymentMethods = [
    'stripe' => !empty(STRIPE_SECRET_KEY) && !empty(STRIPE_PUBLIC_KEY),
    'bitcoin' => !empty(BTCPAY_SERVER_URL) && !empty(BTCPAY_STORE_ID) && !empty(BTCPAY_API_KEY),
    'venmo' => !empty(VENMO_USERNAME) || !empty(CASHAPP_USERNAME)
];

echo json_encode([
    'success' => true,
    'paymentMethods' => $paymentMethods
]);
