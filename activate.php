<?php
include('config.php');

// Check if email and activation code exist in query parameters.
if (isset($_GET['Email'], $_GET['code'])) {
    $email = $_GET['Email'];
    $code = $_GET['code'];

    try {
        // Check if the activation code exists for the given email.
        if ($stmt = $con->prepare('SELECT id FROM dbs13455438.activation_codes WHERE Email = ? AND activation_code = ?')) {
            $stmt->bind_param('ss', $email, $code);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // Fetch the user ID for the given email.
                if ($userStmt = $con->prepare('SELECT id FROM dbs13455438.users WHERE email = ?')) {
                    $userStmt->bind_param('s', $email);
                    $userStmt->execute();
                    $userStmt->bind_result($userId);
                    $userStmt->fetch();
                    $userStmt->close();

                    if (!empty($userId)) {
                        // Update activation status in the users table.
                        if ($updateStmt = $con->prepare('UPDATE dbs13455438.users SET activation_code = ? WHERE email = ?')) {
                            $newCode = 'activated';
                            $updateStmt->bind_param('ss', $newCode, $email);
                            $updateStmt->execute();
                            $updateStmt->close();

                            // Add a 3-month free trial subscription.
                            $startDate = date('Y-m-d H:i:s');
                            $endDate = date('Y-m-d H:i:s', strtotime('+3 months'));
                            if ($subStmt = $con->prepare('INSERT INTO dbs13455438.subscriptions (user_id, subscription_plan, start_date, end_date, status, is_free_trial_used) VALUES (?, ?, ?, ?, ?, ?)')) {
                                $plan = 'starter';
                                $status = 'active';
                                $isFreeTrialUsed = 1;
                                $subStmt->bind_param('issssi', $userId, $plan, $startDate, $endDate, $status, $isFreeTrialUsed);
                                $subStmt->execute();
                                $subStmt->close();
                            }

                            // Remove the activation code.
                            if ($deleteStmt = $con->prepare('DELETE FROM dbs13455438.activation_codes WHERE Email = ? AND activation_code = ?')) {
                                $deleteStmt->bind_param('ss', $email, $code);
                                $deleteStmt->execute();
                                $deleteStmt->close();
                            }

                            // Redirect to the login page with a success flag.
                            header('Location: loginpage.php?activated=1');
                            exit;
                        } else {
                            throw new Exception('Failed to update activation status.');
                        }
                    } else {
                        throw new Exception('User not found.');
                    }
                }
            } else {
                throw new Exception('Invalid activation code or email.');
            }
        } else {
            throw new Exception('Database query error.');
        }
    } catch (Exception $e) {
        echo '<div style="color: red; text-align: center; font-family: Arial, sans-serif; padding: 20px;">
                <h2>Error!</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
              </div>';
    }
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
                                            <h2 class="mb-2" style="font-weight: bold; text-decoration: underline;">Account Activated</h2>
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
