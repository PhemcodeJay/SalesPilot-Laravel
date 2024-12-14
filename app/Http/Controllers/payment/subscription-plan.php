<?php

// Include the configuration file
require_once 'config.php';

/**
 * Update expired subscriptions based on the subscription plan (monthly).
 * Expiration for monthly plans is set to 30 days after the start date.
 *
 * @param PDO $pdo Database connection
 */
function updateExpiredSubscriptions($pdo) {
    $currentDate = date("Y-m-d");

    // Update subscriptions where the end date has passed and the status is still 'active'
    // For monthly plans, expiration date is calculated as 30 days after start_date
    $query = "UPDATE subscriptions SET status = 'expired', end_date = DATE_ADD(start_date, INTERVAL 30 DAY)
              WHERE start_date < :currentDate AND status = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':currentDate', $currentDate);
    $stmt->execute();
}

/**
 * Check if a user's subscription is active.
 *
 * @param int $userId User ID
 * @param PDO $pdo Database connection
 * @return bool True if the subscription is active, false if expired
 */
function isSubscriptionActive($userId, $pdo) {
    $query = "SELECT status, start_date, end_date FROM subscriptions WHERE user_id = :userId AND status = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subscription) {
        // Check if the subscription is still within the valid period (30 days from start date)
        $currentDate = date("Y-m-d");
        // Calculate expiration date: 30 days after the start date
        $calculatedEndDate = date("Y-m-d", strtotime($subscription['start_date'] . ' + 30 days'));

        if ($calculatedEndDate < $currentDate) {
            // Subscription expired
            return false;
        }
        return true;  // Subscription is active and within valid date range
    }

    return false;  // No active subscription found
}

/**
 * Record a payment for a subscription and set the start date for the subscription.
 * Includes handling of a free trial period for 3 months.
 *
 * @param int $userId User ID
 * @param string $paymentMethod Payment method (e.g., 'paypal', 'binance', 'ngn')
 * @param string $paymentProof Path to the payment proof file
 * @param float $paymentAmount The payment amount (0 if free trial)
 * @param PDO $pdo Database connection
 * @return bool True on success, false on failure
 */
function recordPayment($userId, $paymentMethod, $paymentProof, $paymentAmount, $pdo) {
    // Check if the user already has an active subscription or free trial
    $query = "SELECT status, is_free_trial_used FROM subscriptions WHERE user_id = :userId";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subscription) {
        // If the user has already used the free trial, they need to pay for the subscription
        if ($subscription['is_free_trial_used'] == 1 && $paymentAmount == 0) {
            echo "You have already used the free trial. Please select a paid plan.";
            return false;
        }

        // If the user is paying after the free trial, the payment amount must be greater than 0
        if ($paymentAmount > 0 && $subscription['is_free_trial_used'] == 0) {
            // Mark the user as having used the free trial now
            $updateTrialQuery = "UPDATE subscriptions SET is_free_trial_used = 1 WHERE user_id = :userId";
            $updateTrialStmt = $pdo->prepare($updateTrialQuery);
            $updateTrialStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $updateTrialStmt->execute();
        }
    }

    // Set the current date as the start date for the subscription (payment date)
    $startDate = date("Y-m-d");

    // Determine if the user is on a free trial
    if ($paymentAmount == 0 && $subscription['is_free_trial_used'] == 0) {
        // User is eligible for a free trial for 3 months
        $endDate = date("Y-m-d", strtotime($startDate . ' + 3 months'));
        $status = 'active';  // Free trial is active
    } else {
        // User is paying for the subscription
        $endDate = date("Y-m-d", strtotime($startDate . ' + 30 days'));
        $status = 'active';  // Paid subscription is active
    }

    // Insert or update the subscription with the appropriate start date and end date
    if ($subscription) {
        $query = "UPDATE subscriptions SET start_date = :startDate, end_date = :endDate, status = :status WHERE user_id = :userId";
    } else {
        $query = "INSERT INTO subscriptions (user_id, start_date, end_date, status, is_free_trial_used) 
                  VALUES (:userId, :startDate, :endDate, :status, 0)";
    }

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
    $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);

    if ($stmt->execute()) {
        // Get the subscription ID
        $subscriptionId = $pdo->lastInsertId();

        // Insert the payment record and link it to the subscription
        $query = "INSERT INTO payments (user_id, payment_method, payment_proof, payment_amount, payment_status, subscription_id) 
                  VALUES (:userId, :paymentMethod, :paymentProof, :paymentAmount, 'completed', :subscriptionId)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':paymentMethod', $paymentMethod, PDO::PARAM_STR);
        $stmt->bindParam(':paymentProof', $paymentProof, PDO::PARAM_STR);
        $stmt->bindParam(':paymentAmount', $paymentAmount, PDO::PARAM_STR);
        $stmt->bindParam(':subscriptionId', $subscriptionId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "Payment recorded successfully, subscription activated.";
            return true;
        } else {
            echo "Failed to record payment.";
            return false;
        }
    } else {
        echo "Failed to create subscription.";
        return false;
    }
}

/**
 * Control user access based on subscription status.
 *
 * @param int $userId User ID
 * @param PDO $pdo Database connection
 */
function handleSubscriptionCheck($userId, $pdo) {
    if (!isSubscriptionActive($userId, $pdo)) {
        // Redirect to subscription renewal page if expired or no active subscription
        header("Location: /subscription.php");
        exit();
    } else {
        echo "Access granted";
    }
}

// Run the daily update to mark expired subscriptions
updateExpiredSubscriptions($pdo);

// Example usage for a specific user (replace $userId with actual user ID)
$userId = 1;  // Replace with actual user ID
handleSubscriptionCheck($userId, $pdo);

// Example of how a payment is processed for a user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Example data from the form (ensure these values are sanitized and validated properly)
    $userId = 1;  // Replace with actual user ID from session or request
    $paymentMethod = 'paypal';  // This could come from a form input
    $paymentProof = 'uploads/payment_proofs/payment-proof.jpg';  // Example file path
    $paymentAmount = 49.99;  // Payment amount

    recordPayment($userId, $paymentMethod, $paymentProof, $paymentAmount, $pdo);
}

?>
