<?php
session_start();
require_once 'vendor/autoload.php';  // Make sure the Composer autoload file is required

// Google Client configuration
$client = new Google_Client();
$client->setClientId('YOUR_GOOGLE_CLIENT_ID');
$client->setClientSecret('YOUR_GOOGLE_CLIENT_SECRET');
$client->setRedirectUri('http://yourdomain.com/gmail-login.php');
$client->addScope('email');
$client->addScope('profile');

// Initialize Google service
$google_service = new Google_Service_Oauth2($client);

// Check if the user is already authenticated
if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
    header('Location: ' . filter_var($client->getRedirectUri(), FILTER_SANITIZE_URL));
}

// If there is no access token, the user needs to authenticate
if (!isset($_SESSION['access_token'])) {
    $auth_url = $client->createAuthUrl();
    echo "<a href='$auth_url'>Login with Google</a>";
} else {
    // The user is authenticated, now fetch user details
    $client->setAccessToken($_SESSION['access_token']);
    $user_info = $google_service->userinfo->get();
    
    // Check if the user already exists in your database
    include('config.php'); // DB connection
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = :google_id OR email = :email");
    $stmt->execute([
        ':google_id' => $user_info->id,
        ':email' => $user_info->email
    ]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        // Login the existing user
        $_SESSION['user_id'] = $existing_user['id'];
        header('Location: dashboard.php');  // Redirect to the dashboard
    } else {
        // Register the new user
        $username = $user_info->name;
        $email = $user_info->email;
        $google_id = $user_info->id;
        $user_image = $user_info->picture;

        // Insert the new user into your database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, google_id, user_image, is_active) 
                               VALUES (:username, :email, :password, :google_id, :user_image, 1)");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => '',  // No password for Google users
            ':google_id' => $google_id,
            ':user_image' => $user_image
        ]);

        // Log the new user in
        $_SESSION['user_id'] = $pdo->lastInsertId();
        header('Location: dashboard.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: #87CEEB; /* Light Sky Blue Background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }
        
        .login-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transform: perspective(800px) rotateX(10deg);
            transition: transform 0.3s ease-in-out;
        }

        .login-container:hover {
            transform: perspective(800px) rotateX(0deg);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 2rem;
            text-transform: uppercase;
            font-weight: 500;
        }

        .login-button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #4285F4;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease-in-out;
        }

        .login-button:hover {
            background-color: #357ae8;
            transform: scale(1.05);
        }

        .login-button:active {
            transform: scale(0.98);
        }

        .icon {
            margin-right: 10px;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .login-footer a {
            color: #333;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        .login-footer a:hover {
            color: #4285F4;
        }

        .button-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Login with Gmail</h2>
        <div class="button-wrapper">
            <a href="gmail-login.php" class="login-button">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google Logo" class="icon" width="20">
                Login with Google
            </a>
        </div>
        <div class="login-footer">
            <p>New to our site? <a href="#">Sign up here</a></p>
        </div>
    </div>

</body>
</html>

