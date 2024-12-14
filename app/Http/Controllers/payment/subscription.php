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

// Fetch the logged-in user's information
$user_query = "SELECT username, email, date, phone, location, user_image FROM users WHERE username = :username";
$stmt = $connection->prepare($user_query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_info) {
    throw new Exception("User not found.");
}

$email = htmlspecialchars($user_info['email']);
$date = date('d F, Y', strtotime($user_info['date']));
$location = htmlspecialchars($user_info['location']);
$existing_image = htmlspecialchars($user_info['user_image']);
$image_to_display = !empty($existing_image) ? $existing_image : 'uploads/user/default.png';


try {
    // Process payment form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $amount = $_POST['payment_amount'] ?? 0;
        $method = $_POST['payment_method'] ?? 'Cash';
        $status = $_POST['payment_status'] ?? 'Pending';

        $stmt = $connection->prepare("INSERT INTO payments (payment_amount, payment_method, payment_status) VALUES (?, ?, ?)");
        $stmt->execute([$amount, $method, $status]);
        $paymentId = $connection->lastInsertId();
    }

    // Fetch inventory notifications with product images
    $inventoryQuery = $connection->prepare("
        SELECT i.product_name, i.available_stock, i.inventory_qty, i.sales_qty, p.image_path
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        WHERE i.available_stock < :low_stock OR i.available_stock > :high_stock
        ORDER BY i.last_updated DESC
    ");
    $inventoryQuery->execute([':low_stock' => 10, ':high_stock' => 1000]);
    $inventoryNotifications = $inventoryQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch reports notifications with product images
    $reportsQuery = $connection->prepare("
        SELECT JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_name')) AS product_name, 
               JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) AS revenue,
               p.image_path
        FROM reports r
        JOIN products p ON JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_id')) = p.id
        WHERE JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) > :high_revenue 
           OR JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) < :low_revenue
        ORDER BY r.report_date DESC
    ");
    $reportsQuery->execute([':high_revenue' => 10000, ':low_revenue' => 1000]);
    $reportsNotifications = $reportsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}


// Handle PayPal Webhook if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the incoming webhook payload
    $rawPayload = file_get_contents("php://input");
    $payload = json_decode($rawPayload, true);

    // Validate the event
    if (!empty($payload) && isset($payload['event_type'])) {
        // Event types to listen for
        switch ($payload['event_type']) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                // Subscription activated logic
                $subscriptionId = $payload['resource']['id'];
                $planId = $payload['resource']['plan_id'];
                $userId = getUserIdBySubscription($subscriptionId); // Replace with actual logic to get user ID from your database

                // Determine the plan type based on the plan_id
                $planName = getPlanName($planId);

                if ($planName) {
                    activateSubscription($subscriptionId, $planName, $userId);
                } else {
                    logError("Unknown plan ID: $planId");
                }
                break;

            case 'PAYMENT.SALE.COMPLETED':
                // Payment completed logic
                $saleId = $payload['resource']['id'];
                $amount = $payload['resource']['amount']['total'];
                $userId = getUserIdByPayment($saleId); // Replace with actual logic to get user ID

                // Insert payment information
                recordPayment($saleId, $amount, $userId);
                break;

            // Add additional event types as needed
            default:
                logError("Unhandled event type: " . $payload['event_type']);
                break;
        }

        // Respond with a success status to acknowledge receipt of the webhook
        http_response_code(200);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Respond with an error status if the payload is invalid
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Function to get the plan name based on plan_id
function getPlanName($planId) {
    $plans = [
        'P-92V01000GH171635WM5HYGRQ' => 'starter',
        'P-6TP94103DT2394623M5HYFKY' => 'business',
        'P-7E210255TM029860GM5HYC4A' => 'enterprise'
    ];
    return $plans[$planId] ?? null;
}

// Function to activate the subscription
function activateSubscription($subscriptionId, $planName, $userId) {
    global $connection; // Use the global connection object

    try {
        // Insert subscription data
        $query = "INSERT INTO subscriptions (user_id, subscription_plan, status) VALUES (?, ?, 'active')";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $planName, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // Log the subscription activation
            file_put_contents('webhook_log.txt', "Subscription activated: ID = $subscriptionId, User ID = $userId, Plan = $planName\n", FILE_APPEND);

            // Send email notification
            $email = getUserEmailById($userId);
            $subject = "Subscription Activated";
            $message = "Dear User,\n\nYour subscription ($planName) has been activated successfully.\n\nThank you for subscribing!\n\nBest regards,\nYour Company";
            $headers = "From: no-reply@yourcompany.com";
            mail($email, $subject, $message, $headers);
        } else {
            logError("Subscription insert failed: " . json_encode($stmt->errorInfo()));
        }
    } catch (Exception $e) {
        logError("Error activating subscription: " . $e->getMessage());
    }
}

// Function to record payment
function recordPayment($saleId, $amount, $userId) {
    global $connection; // Use the global connection object

    try {
        // Insert payment data
        $query = "INSERT INTO payments (user_id, payment_method, payment_amount, payment_status, sale_id) 
                  VALUES (?, 'paypal', ?, 'completed', ?)";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $amount, PDO::PARAM_STR);
        $stmt->bindParam(3, $saleId, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // Log the payment record
            file_put_contents('webhook_log.txt', "Payment recorded: Sale ID = $saleId, Amount = $amount, User ID = $userId\n", FILE_APPEND);

            // Send email notification
            $email = getUserEmailById($userId);
            $subject = "Payment Received";
            $message = "Dear User,\n\nWe have received your payment of $amount.\n\nThank you for your support!\n\nBest regards,\nYour Company";
            $headers = "From: no-reply@yourcompany.com";
            mail($email, $subject, $message, $headers);
        } else {
            logError("Payment insert failed: " . json_encode($stmt->errorInfo()));
        }
    } catch (Exception $e) {
        logError("Error recording payment: " . $e->getMessage());
    }
}

// Function to log errors
function logError($message) {
    file_put_contents('webhook_log.txt', "Error: $message\n", FILE_APPEND);
}

// Function to get the user ID based on subscription ID (replace with your own logic)
function getUserIdBySubscription($subscriptionId) {
    global $connection;
    $query = "SELECT user_id FROM subscriptions WHERE subscription_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(1, $subscriptionId, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn();
}

// Function to get the user ID based on sale ID (replace with your own logic)
function getUserIdByPayment($saleId) {
    global $connection;
    $query = "SELECT user_id FROM payments WHERE sale_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(1, $saleId, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn();
}

// Function to get the user email by user ID (replace with your own logic)
function getUserEmailById($userId) {
    global $connection;
    $query = "SELECT email FROM users WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn();
}
?>
