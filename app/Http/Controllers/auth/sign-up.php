<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/vendor/autoload.php'; // Include the Composer autoloader
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';
require '../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include the database connection settings
include('config.php');

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    // Initialize variables with default values
    $username = $password = $email = $confirmpassword = "";

    // Check if the keys exist in the $_POST array before accessing them
    if (isset($_POST["Username"])) {
        $username = htmlspecialchars($_POST["Username"]);
    }

    if (isset($_POST["Password"])) {
        $password = htmlspecialchars($_POST["Password"]);
    }

    if (isset($_POST["Email"])) {
        $email = htmlspecialchars($_POST["Email"]);
    }

    if (isset($_POST["confirmpassword"])) {
        $confirmpassword = htmlspecialchars($_POST["confirmpassword"]);
    }

    if (isset($_SESSION['id_user']) && !empty($_SESSION['id_user'])) {
        header("Location: reg-success.html");
        exit(); // Add exit to stop the script execution
    }

    // Create an instance of PHPMailer
    $mail = new PHPMailer(true);

    // Call the function to handle form submission
    handleFormSubmission($username, $password, $email, $confirmpassword, $connection, $mail);
}

// Function to handle form submission and insert data in the database
function handleFormSubmission($username, $password, $email, $confirmpassword, $connection, $mail)
{
    // Validate form data
    if (empty($username) || empty($password) || empty($email) || empty($confirmpassword)) {
        echo 'All fields are required!';
        return;
    }
    
    if (strlen($password) > 20 || strlen($password) < 5) {
        echo 'Password must be between 5 and 20 characters!';
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'Invalid email format!';
        return;
    }
    
    if (preg_match('/^[a-zA-Z0-9]+$/', $username) == 0) {
        echo 'Username can only contain letters and numbers!';
        return;
    }

    if ($password !== $confirmpassword) {
        echo 'Passwords do not match!';
        return;
    }

    // Check if Username or Email already exists
    $stmt = $connection->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        echo 'Username or Email already exists, please choose another!';
    } else {
        // Insert new user record
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $activationCode = uniqid();

        $insertStmt = $connection->prepare('INSERT INTO users (username, password, email, confirmpassword) VALUES (?, ?, ?, ?)');
        if ($insertStmt->execute([$username, $passwordHash, $email, $confirmpassword])) {
            $userId = $connection->lastInsertId();

            // Insert activation code
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $activationStmt = $connection->prepare('INSERT INTO activation_codes (user_id, activation_code, expires_at) VALUES (?, ?, ?)');
            if ($activationStmt->execute([$userId, $activationCode, $expiresAt])) {
                // Send activation email
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.ionos.com';
                    $mail->Port = 587;
                    $mail->SMTPAuth = true;
                    $mail->Username = 'admin@cybertrendhub.store'; // Replace with your Gmail email
                    $mail->Password = 'kokochulo@1987#'; // Replace with your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

                    $mail->setFrom('admin@cybertrendhub.store', 'SalesPilot');
                    $mail->addAddress($email);
                    $mail->Subject = 'Activate Your Account';
                    $mail->Body = 'Hello,<br>Click the link below to activate your account:<br><a href="https://salespilot.cybertrendhub.store/activate.php?token=' . $activationCode . '">Activate Account</a>';

                    if ($mail->send()) {
                        header("Location: reg-success.html"); // Redirect after sending activation email
                        exit(); // Add exit to stop the script execution
                    } else {
                        echo 'Error sending activation email: ' . $mail->ErrorInfo;
                    }
                } catch (Exception $e) {
                    echo 'Mailer Error: ' . $e->getMessage();
                }
            } else {
                echo 'Error inserting activation code into the database: ' . $activationStmt->errorInfo()[2];
            }
        } else {
            echo 'Error inserting user record into the database: ' . $insertStmt->errorInfo()[2];
        }
    }
}
?>


