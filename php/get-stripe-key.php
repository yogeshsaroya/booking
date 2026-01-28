<?php
/**
 * Get Stripe public key
 * Returns the appropriate public key based on STRIPE_MODE
 */

require_once 'config.php';

header('Content-Type: application/json');

echo json_encode([
    'publicKey' => STRIPE_PUBLIC_KEY
]);
