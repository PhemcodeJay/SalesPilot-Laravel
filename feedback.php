<?php


// Include the database configuration and PHPMailer classes
require 'config.php'; // Make sure this file contains the correct database connection setup
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';
require '../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables and error array
$name = $email = $phone = $message = '';
$errors = [];

// Check if the database connection is established
if (!$connection) {
    die("Database connection failed.");
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and validate input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);

    // Validate input fields
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email is not valid.';
    }
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }
    if (empty($message)) {
        $errors[] = 'Message is required.';
    }

    // If no errors, insert data into the database and send email
    if (empty($errors)) {
        // Prepare SQL statement with PDO
        $sql = "INSERT INTO contacts (name, email, phone, message) VALUES (:name, :email, :phone, :message)";
        $stmt = $connection->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);

        // Execute the statement
        if ($stmt->execute()) {
            // Setup PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.ionos.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'admin@cybertrendhub.store';  // IONOS email address
                $mail->Password = 'kokochulo@1987#';             // IONOS email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
                $mail->Port = 587;                               // Port 587 for TLS
                $mail->SMTPDebug = 2;                            // Enable debug output for troubleshooting

                // Set email format and recipient details
                $mail->setFrom('admin@cybertrendhub.store', 'CyberTrendHub');
                $mail->addAddress('phemcodejay@gmail.com');       // Primary recipient
                $mail->addReplyTo($email, $name);                 // Reply-to email and name

                // Email content
                $mail->Subject = 'New Feedback Message from ' . $name;
                $mail->Body = "Name: $name\nEmail: $email\nPhone: $phone\nMessage:\n$message";

                // Send the email
                $mail->send();
                echo "Email sent successfully.<br>"; // Debug message

                // Success message and page reload script
                echo "<div class='alert alert-success'>Thank you, $name! Your message has been sent</div>";
               // Redirect to index.html after sending the email
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'index.html';
                        }, 2000); // Redirect after 2 seconds
                    </script>";
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "<div class='text-danger'>Error: " . $stmt->errorInfo() . "</div>"; // Display SQL errors
        }
    } else {
        // Display validation errors
        foreach ($errors as $error) {
            echo "<div class='text-danger'>$error</div>";
        }
    }
}

// No need to explicitly close the connection with PDO as it will close automatically when the script ends
?>
