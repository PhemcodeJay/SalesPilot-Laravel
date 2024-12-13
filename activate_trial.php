<?php
session_start();
require_once 'config.php'; // Include your DB connection file

// Check if username is set in session
if (!isset($_SESSION["username"])) {
    echo json_encode(['success' => false, 'message' => 'No username found in session. Please log in.']);
    exit();
}

$username = htmlspecialchars($_SESSION["username"]);

try {
    // Step 1: Get the user's ID based on their username
    $query = "SELECT id FROM users WHERE username = ? LIMIT 1";
    $stmt = $connection->prepare($query);
    $stmt->execute([$username]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        echo json_encode(['success' => false, 'message' => 'User not found in the database.']);
        exit();
    }

    $user_id = $user_data['id'];

    // Check if the request is to activate the trial
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_trial') {
        
        // Step 2: Check if the user already has an active subscription
        $query = "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' LIMIT 1";
        $stmt = $connection->prepare($query);
        $stmt->execute([$user_id]);
        $existing_subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_subscription) {
            echo json_encode(['success' => false, 'message' => 'You already have an active subscription.']);
            exit();
        }

        // Step 3: Check if the user has already used the free trial
        $query = "SELECT is_free_trial_used FROM subscriptions WHERE user_id = ? LIMIT 1";
        $stmt = $connection->prepare($query);
        $stmt->execute([$user_id]);
        $trial_status = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($trial_status && $trial_status['is_free_trial_used'] == 1) {
            echo json_encode(['success' => false, 'message' => 'You have already used your free trial.']);
            exit();
        }

        // Step 4: Calculate the 3-month free trial period
        $start_date = new DateTime();
        $end_date = new DateTime();
        $end_date->add(new DateInterval('P3M')); // Add 3 months to the trial

        // Step 5: Insert the free trial into the subscriptions table
        $query = "INSERT INTO subscriptions (user_id, subscription_plan, start_date, end_date, status, is_free_trial_used) 
                  VALUES (?, 'starter', ?, ?, 'active', 1)";
        $stmt = $connection->prepare($query);
        $stmt->execute([
            $user_id,
            $start_date->format('Y-m-d H:i:s'),
            $end_date->format('Y-m-d H:i:s')
        ]);

        if ($stmt->rowCount() > 0) {
            // Step 6: Insert payment record for the free trial
            $subscription_id = $connection->lastInsertId(); // Get the last inserted subscription ID
            $query = "INSERT INTO payments (user_id, payment_method, payment_proof, payment_amount, payment_status, subscription_id) 
                      VALUES (?, 'free', 'N/A', 0.00, 'completed', ?)";
            $stmt = $connection->prepare($query);
            $stmt->execute([$user_id, $subscription_id]);

            if (isset($_SESSION['success_message'])) {
                echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
                unset($_SESSION['success_message']); // Clear the message after displaying it
            } else {
                echo json_encode(['success' => false, 'message' => 'Error recording payment for free trial.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error activating free trial.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Account Activation</title>
    <link rel="stylesheet" href="https://salespilot.cybertrendhub.store/assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="https://salespilot.cybertrendhub.store/assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="https://salespilot.cybertrendhub.store/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://salespilot.cybertrendhub.store/assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="https://salespilot.cybertrendhub.store/assets/vendor/remixicon/fonts/remixicon.css">
</head>
<body>
    <div class="wrapper">
        <section class="login-content">
            <div class="container">
                <div class="row align-items-center justify-content-center height-self-center">
                    <div class="col-lg-8">
                        <div class="card auth-card">
                            <div class="card-body p-0">
                                <div class="d-flex align-items-center auth-content">
                                    <div class="col-lg-7 align-self-center">
                                        <div class="p-3">
                                            <h2 class="mb-2" style="font-weight: bold; text-decoration: underline;">3 Months Trial Activated</h2>
                                            <p style="font-weight: bold; text-decoration: underline;">Click below to log in now</p>
                                            <a href="loginpage.php" class="btn btn-primary mt-3" style="font-weight: bold; text-decoration: underline;">Go to Login</a>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 content-right">
                                        <img src="https://salespilot.cybertrendhub.store/assets/images/login/01.png" class="img-fluid image-right" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://salespilot.cybertrendhub.store/assets/js/backend-bundle.min.js"></script>
    <script src="https://salespilot.cybertrendhub.store/assets/js/table-treeview.js"></script>
    <script src="https://salespilot.cybertrendhub.store/assets/js/app.js"></script>
</body>
</html>

