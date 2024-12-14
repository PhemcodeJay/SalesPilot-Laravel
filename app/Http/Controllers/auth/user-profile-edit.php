<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Database connection

try {
    // Check if username is set in session
    if (!isset($_SESSION["username"])) {
        throw new Exception("No username found in session.");
    }

    $username = htmlspecialchars($_SESSION["username"]);

    // Retrieve user information from the users table
    $user_query = "SELECT id, username, date, email, phone, location, is_active, role, user_image FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        throw new Exception("User not found.");
    }

    // Retrieve user details
    $email = htmlspecialchars($user_info['email']);
    $date = htmlspecialchars($user_info['date']);
    $location = htmlspecialchars($_POST['location']);
    $user_id = htmlspecialchars($user_info['id']);
    $existing_image = htmlspecialchars($user_info['user_image']);
    $image_to_display = $existing_image ?: 'uploads/user/default.png'; // Use default image if none exists

    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and retrieve form inputs
        $username = htmlspecialchars($_POST['username']);
        $email = htmlspecialchars($_POST['email']);
        $location = htmlspecialchars($_POST['location']);
        $is_active = isset($_POST['is_active']) ? htmlspecialchars($_POST['is_active']) : null;
        $role = isset($_POST['role']) ? htmlspecialchars($_POST['role']) : null;

        // Handle file upload
        if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] == UPLOAD_ERR_OK) {
            // Generate unique file name to avoid collisions (using time() and basename)
            $user_image = time() . '_' . basename($_FILES['user_image']['name']);
            $target_dir = "uploads/user/";
            $target_file = $target_dir . $user_image;

            // Ensure the target directory exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            // Check file size (limit to 10MB)
            if ($_FILES['user_image']['size'] > 10000000) {
                exit("Error: File is too large. Maximum allowed size is 10MB.");
            }

            // Only allow JPEG and PNG files
            $allowed_types = ['image/jpeg', 'image/png'];
            $file_type = mime_content_type($_FILES['user_image']['tmp_name']);
            if (!in_array($file_type, $allowed_types)) {
                exit("Error: Invalid file type. Only JPEG and PNG are allowed.");
            }

            // Move the uploaded file
            if (move_uploaded_file($_FILES["user_image"]["tmp_name"], $target_file)) {
                $image_to_save = $target_file;
            } else {
                // Log file upload failure for debugging
                error_log("File upload failed for user ID: $user_id");
                $image_to_save = $existing_image ?: 'uploads/user/default.png'; // Use existing or default image if upload fails
            }
        } else {
            $image_to_save = $existing_image ?: 'uploads/user/default.png'; // Use existing or default image if no new image is uploaded
        }

        // Update user record
        $sql = "UPDATE users SET username=?, email=?, location=?, is_active=?, role=?, user_image=? WHERE id=?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$username, $email, $location, $is_active, $role, $image_to_save, $user_id]);

        echo "User updated successfully!";
    }

} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    exit("Error: " . $e->getMessage());
}

try {
    // Fetch inventory notifications with product images
    $inventoryQuery = $connection->prepare("
        SELECT i.product_name, i.available_stock, i.inventory_qty, i.sales_qty, p.image_path
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        WHERE i.available_stock < :low_stock OR i.available_stock > :high_stock
        ORDER BY i.last_updated DESC
    ");
    $inventoryQuery->execute([
        ':low_stock' => 10,
        ':high_stock' => 1000,
    ]);
    $inventoryNotifications = $inventoryQuery->fetchAll();

    // Fetch reports notifications with product images
    $reportsQuery = $connection->prepare("
        SELECT JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_name')) AS product_name, 
               JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) AS revenue,
               p.image_path
        FROM reports r
        JOIN products p ON JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_id')) = p.id
        WHERE JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) > :high_revenue 
           OR JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) < :low_revenue
        ORDER BY r.report_date DESC
    ");
    $reportsQuery->execute([
        ':high_revenue' => 10000,
        ':low_revenue' => 1000,
    ]);
    $reportsNotifications = $reportsQuery->fetchAll();
} catch (PDOException $e) {
    // Handle any errors during database queries
    echo "Error: " . $e->getMessage();
}

try {
    // Prepare and execute the query to fetch user information from the users table
    $user_query = "SELECT id, username, date, email, phone, location, is_active, role, user_image FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    // Fetch user data
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_info) {
        // Retrieve user details and sanitize output
        $email = htmlspecialchars($user_info['email']);
        $date = date('d F, Y', strtotime($user_info['date']));
        $location = htmlspecialchars($user_info['location']);
        $user_id = htmlspecialchars($user_info['id']);
        
        // Check if a user image exists, use default if not
        $existing_image = htmlspecialchars($user_info['user_image']);
        $image_to_display = !empty($existing_image) ? $existing_image : 'uploads/user/default.png';

    }
} catch (PDOException $e) {
    // Handle database errors
    exit("Database error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle user not found or other exceptions
    exit("Error: " . $e->getMessage());
}

// Fetch the subscription details for the logged-in user (replace `$_SESSION['user_id']` with actual user id)
$user_id = 1; // Example user ID, replace with dynamic value like $_SESSION['user_id'] or from URL parameter
$sql = "SELECT * FROM subscriptions WHERE user_id = :user_id ORDER BY start_date DESC LIMIT 1";
$stmt = $connection->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Check if subscription exists using rowCount() instead of num_rows
if ($stmt->rowCount() > 0) {
    // Get subscription data
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    $subscription_status = $subscription['status'];
    $subscription_plan = $subscription['subscription_plan'];
    $start_date = $subscription['start_date'];
    $end_date = $subscription['end_date'];
    $is_free_trial_used = $subscription['is_free_trial_used'];
} else {
    $subscription_status = 'No active subscription';
    $subscription_plan = '';
    $start_date = '';
    $end_date = '';
    $is_free_trial_used = 0;
}
?>


