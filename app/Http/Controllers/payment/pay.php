<?php 

session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);



// Include database connection
include('config.php');
require 'vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: loginpage.php");
    exit;
}

$username = htmlspecialchars($_SESSION["username"]);


// Simulated exchange rates
$exchangeRates = [
    'USD' => 1,
    'KES' => 130,
    'NGN' => 1500,
];

// Fetch exchange rates
function getExchangeRates($baseCurrency = 'USD') {
    $apiKey = 'cc688057dc86274ff7958e5e'; // Replace with your API key
    $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}";

    $response = file_get_contents($url);
    if ($response === FALSE) {
        return null;
    }

    $data = json_decode($response, true);
    return $data['result'] === 'success' ? $data['conversion_rates'] : null;
}

$conversionRates = getExchangeRates('USD') ?: $exchangeRates;

// Base prices and details in USD for each plan
$basePricingPlans = [
    'starter' => [
        'amount' => 5,
        'currency' => 'USD',
        'name' => 'Starter Plan',
        'details' => [
            'description' => 'Perfect for individuals or small startups just getting started.',
            'features' => [
                'Inventory Management',
                'Sales Management',
                'Invoices & Expenses',
                'Analytics & Reports',
                '24/7 Customer Service Support'
            ]
        ],
    ],
    'medium' => [
        'amount' => 15,
        'currency' => 'USD',
        'name' => 'Buisness Plan',
        'details' => [
            'description' => 'Great for small to medium-sized businesses looking to grow.',
            'features' => [
                'Inventory Management',
                'Sales Management',
                'Invoices & Expenses',
                'Analytics & Reports',
                'Customers, Staffs, Suppliers - Records',
                '24/7 Customer Service Support'
                
            ]
        ],
    ],
    'enterprise' => [
        'amount' => 25,
        'currency' => 'USD',
        'name' => 'Enterprise Plan',
        'details' => [
            'description' => 'Comprehensive solution for large businesses with advanced needs.',
            'features' => [
                'Inventory Management',
                'Sales Management',
                'Invoices & Expenses',
                'Analytics & Reports',
                'Customers, Staffs, Suppliers - Records',
                'Custom Integrations and Dedicated Support'
            ]
        ],
    ],
];

// Calculate prices in KES and NGN for each plan
$pricingPlans = [];
foreach ($basePricingPlans as $key => $plan) {
    $pricingPlans[$key] = [
        'name' => $plan['name'],
        'amount_USD' => $plan['amount'],
        'amount_KES' => round($plan['amount'] * $conversionRates['KES'], 2),
        'amount_NGN' => round($plan['amount'] * $conversionRates['NGN'], 2),
        'details' => $plan['details'],
    ];
}

// Set your PayPal client ID and secret
$clientId = 'Abq0Z652p0xd7LntfVIW3gTpX4buCF9UQUSnOH_EBcQzo0B2vrCRV_htZvOt-QCxb6kItlgT38pr1xPt';
$clientSecret = 'EFJotT-21CyvIuDvGfPKzsCk6g0iThtMfiaZmqnaW-FoPXTSBGpW1qm7t4iJX0yfhPFbEMBPMjKjAd_V';

// PayPal's webhook verification URL
$paypalWebhookUrl = 'https://api.paypal.com/v1/notifications/verify-webhook-signature';

// Webhook request body
$bodyReceived = file_get_contents('php://input');
$headers = getallheaders();

// PayPal sends headers for webhook verification
$authAlgo = $headers['paypal-auth-algo'];
$certUrl = $headers['paypal-cert-url'];
$transmissionId = $headers['paypal-transmission-id'];
$transmissionSig = $headers['paypal-transmission-sig'];
$timestamp = $headers['paypal-transmission-time'];

// Prepare the verification request payload
$verificationData = [
    'auth_algo' => $authAlgo,
    'cert_url' => $certUrl,
    'transmission_id' => $transmissionId,
    'transmission_sig' => $transmissionSig,
    'transmission_time' => $timestamp,
    'webhook_id' => 'YOUR_WEBHOOK_ID',
    'webhook_event' => json_decode($bodyReceived),
];

// Send the verification request to PayPal API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypalWebhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verificationData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
]);

$response = curl_exec($ch);
curl_close($ch);

$verificationResponse = json_decode($response, true);

// If PayPal verifies the webhook, process the event
if ($verificationResponse['verification_status'] === 'SUCCESS') {
    $event = json_decode($bodyReceived, true);

    // Check the event type
    if ($event['event_type'] === 'PAYMENT.SALE.COMPLETED') {
        // Handle the payment confirmation
        $subscriptionId = $event['resource']['billing_agreement_id'];
        $payerId = $event['resource']['payer']['payer_info']['payer_id'];

        // Activate the subscription and send an email
        activateSubscription($connection, $subscriptionId, $payerId);
    }

    if ($event['event_type'] === 'BILLING.SUBSCRIPTION.CREATED') {
        // Handle the subscription creation
        $subscriptionId = $event['resource']['id'];
        $subscriberEmail = $event['resource']['subscriber']['email_address'];

        // Activate the subscription and send an email
        activateSubscription($connection, $subscriptionId, $subscriberEmail);
    }
} else {
    // Verification failed
    error_log('Webhook verification failed');
}

// Function to activate the subscription and send an email
function activateSubscription($connection, $subscriptionId, $payerId) {
    try {
        // Check if the subscription already exists
        $stmt = $connection->prepare("SELECT * FROM subscriptions WHERE subscription_id = :subscriptionId");
        $stmt->bindParam(':subscriptionId', $subscriptionId);
        $stmt->execute();

        // Email setup
        $subject = "Subscription Activated";
        $message = "Dear Subscriber,\n\nYour subscription (ID: $subscriptionId) has been activated successfully.\n\nThank you for your support!\n\nBest regards,\nYour Company";
        $headers = "From: no-reply@yourcompany.com";

        if ($stmt->rowCount() === 0) {
            // Insert new subscription
            $stmt = $connection->prepare(
                "INSERT INTO subscriptions (subscription_id, payer_id, status) 
                 VALUES (:subscriptionId, :payerId, 'active')"
            );
            $stmt->bindParam(':subscriptionId', $subscriptionId);
            $stmt->bindParam(':payerId', $payerId);
            $stmt->execute();

            // Send email
            mail($payerId, $subject, $message, $headers);
        } else {
            // Update existing subscription
            $stmt = $connection->prepare(
                "UPDATE subscriptions 
                 SET status = 'active' 
                 WHERE subscription_id = :subscriptionId"
            );
            $stmt->bindParam(':subscriptionId', $subscriptionId);
            $stmt->execute();

            // Send email
            mail($payerId, $subject, $message, $headers);
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
    }
}

?>

