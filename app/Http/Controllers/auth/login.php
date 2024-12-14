<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Ensure $connection is set up for the database

// Redirect logged-in users to the dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: user-confirm.html");
    exit();
}

// Initialize error messages
$username_err = $password_err = $login_err = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : '';

    if (empty($username)) {
        $username_err = "Please enter your username.";
    }
    if (empty($password)) {
        $password_err = "Please enter your password.";
    }

    if (empty($username_err) && empty($password_err)) {
        try {
            $stmt = $connection->prepare('SELECT id, username, password FROM users WHERE username = ?');
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id_user"] = $user['id'];
                    $_SESSION["username"] = $user['username'];

                    header("Location: user-confirm.html");
                    exit();
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                $login_err = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            exit("Database error: " . $e->getMessage());
        }
    }
}

// Close database connection
$connection = null;
?>

