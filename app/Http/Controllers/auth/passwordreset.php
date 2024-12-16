<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/vendor/autoload.php'; // Include the Composer autoloader
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $password = htmlspecialchars($_POST["Password"]);
    $confirmPassword = htmlspecialchars($_POST["ConfirmPassword"]);
    $resetToken = htmlspecialchars($_POST["reset_code"]);

    if (empty($password) || empty($confirmPassword) || empty($resetToken)) {
        echo 'All fields are required!';
        return;
    }

    if ($password !== $confirmPassword) {
        echo 'Passwords do not match!';
        return;
    }

    if (strlen($password) > 20 || strlen($password) < 5) {
        echo 'Password must be between 5 and 20 characters!';
        return;
    }

    $stmt = $connection->prepare('SELECT user_id, expires_at FROM password_resets WHERE reset_code = ?');
    $stmt->execute([$resetToken]);

    if ($stmt->rowCount() == 0) {
        echo 'Invalid or expired reset token!';
        return;
    }

    $resetData = $stmt->fetch();
    if (new DateTime() > new DateTime($resetData['expires_at'])) {
        echo 'Reset token has expired!';
        return;
    }

    $userId = $resetData['user_id'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $updateStmt = $connection->prepare('UPDATE users SET password = ? WHERE id = ?');
    if ($updateStmt->execute([$passwordHash, $userId])) {
        $deleteStmt = $connection->prepare('DELETE FROM password_resets WHERE reset_code = ?');
        $deleteStmt->execute([$resetToken]);

        echo 'Password has been reset successfully!';
    } else {
        echo 'Error updating password: ' . $updateStmt->errorInfo()[2];
    }
}
?>



