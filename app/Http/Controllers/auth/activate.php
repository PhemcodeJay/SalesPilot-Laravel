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


