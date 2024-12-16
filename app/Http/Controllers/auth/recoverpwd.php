<?php
session_start();

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include necessary files
require __DIR__ . '/vendor/autoload.php'; // Composer autoloader
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';
require '../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reset'])) {
    // Validate CSRF token
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo 'CSRF token validation failed!';
        exit;
    }

    // Sanitize and validate email input
    $email = filter_var(trim($_POST["Email"]), FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        echo 'Email is required!';
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'Invalid email format!';
        return;
    }

    // Check if the email exists in the database
    $stmt = $connection->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);

    if ($stmt->rowCount() === 0) {
        echo 'Email not found!';
        return;
    }

    $userId = $stmt->fetchColumn();
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Insert reset token into the database
    $resetStmt = $connection->prepare('INSERT INTO password_resets (user_id, reset_code, expires_at) VALUES (?, ?, ?)');
    if ($resetStmt->execute([$userId, $resetToken, $expiresAt])) {
        try {
            // Send password reset email using PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.com';
            $mail->Port = 587;
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@cybertrendhub.store'; // Ensure this is correct
            $mail->Password = 'kokochulo@1987#'; // Ensure this is correct
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPDebug = 2;  // Enable detailed debug output

            $mail->setFrom('admin@cybertrendhub.store', 'SalesPilot');
            $mail->addAddress($email);
            $mail->isHTML(true); // Enable HTML formatting
            $mail->Subject = 'Password Reset Request';
            $mail->Body = 'Click the link below to reset your password:<br><a href="http://localhost:8000/recoverpwd.php?token=' . $resetToken . '">Reset Password</a>';

            // Send email
            if ($mail->send()) {
                echo 'Password reset email sent!';
            } else {
                echo 'Failed to send email.';
            }
        } catch (Exception $e) {
            echo 'Mailer Error: ' . $e->getMessage(); // Display detailed error message
            error_log('Mailer Error: ' . $e->getMessage()); // Log errors for debugging
        }
    } else {
        echo 'Error saving reset token: ' . $resetStmt->errorInfo()[2];
    }
}
?>
