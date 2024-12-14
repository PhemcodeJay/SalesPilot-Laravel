<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include PHPMailer's autoloader
require 'config.php'; // Include your PDO configuration file

header('Content-Type: application/json');

// Ensure the upload directory exists and is writable
$uploadDir = 'uploads/payment_proofs/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    echo json_encode(['success' => false, 'message' => 'Failed to create the upload directory.']);
    exit;
}

// Check if form data is posted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate uploaded file
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['payment_proof']['tmp_name'];
        $fileName = basename($_FILES['payment_proof']['name']); // Sanitize file name
        $fileSize = $_FILES['payment_proof']['size'];
        $fileType = $_FILES['payment_proof']['type'];
        $destPath = $uploadDir . $fileName;

        // Validate file type (JPG, PNG, PDF)
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
            exit;
        }

        // Check file size (limit to 15MB)
        if ($fileSize > 15 * 1024 * 1024) { // 15MB
            echo json_encode(['success' => false, 'message' => 'File size exceeds the 5MB limit.']);
            exit;
        }

        // Move the uploaded file to the destination directory
        if (move_uploaded_file($fileTmpPath, $destPath)) {

            // Validate payment method
            if (isset($_POST['payment_method']) && in_array($_POST['payment_method'], ['paypal', 'binance', 'mpesa', 'naira'])) {
                $paymentMethod = htmlspecialchars($_POST['payment_method']); // Sanitize input

                try {
                    // Establish database connection using PDO
                    $connection = new PDO($dsn, $username, $password, $options);

                    // Insert payment information into the database
                    $sql = "INSERT INTO payments (payment_method, payment_proof) VALUES (:payment_method, :payment_proof)";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute([
                        ':payment_method' => $paymentMethod,
                        ':payment_proof' => $destPath,
                    ]);

                    // Send email with the payment proof
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = 'smtp.ionos.com'; // Set your SMTP server
                        $mail->SMTPAuth = true;
                        $mail->Username = 'admin@cybertrendhub.store'; // SMTP username
                        $mail->Password = 'kokochulo@1987#'; // SMTP password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('info@salespilot.cybertrendhub.store', 'SalesPilot');
                        $mail->addAddress('admin@cybertrendhub.store'); // Admin email

                        // Attachments
                        $mail->addAttachment($destPath, $fileName);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'New Payment Proof Uploaded';
                        $mail->Body = 'A new payment proof has been uploaded for payment method: ' . ucfirst($paymentMethod) . '. Please review the attached file.';

                        $mail->send();

                        echo json_encode(['success' => true, 'message' => 'Payment proof uploaded and email sent successfully.']);
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => 'Email sending failed: ' . $mail->ErrorInfo]);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid payment method selected.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error moving the uploaded file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or there was an upload error.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
