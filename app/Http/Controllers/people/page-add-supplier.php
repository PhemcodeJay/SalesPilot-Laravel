<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Includes database connection

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
    // Check if username is set in session
    if (!isset($_SESSION["username"])) {
        throw new Exception("No username found in session.");
    }

    $username = htmlspecialchars($_SESSION["username"]);

    // Retrieve user information from the users table
    $user_query = "SELECT username, email, date FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        throw new Exception("User not found.");
    }

    // Retrieve user email and registration date
    $email = htmlspecialchars($user_info['email']);
    $date = htmlspecialchars($user_info['date']);
    
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    exit("Error: " . $e->getMessage());
}

try {
    // Ensure this PHP script is accessed through a POST request
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if the user is logged in
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            throw new Exception("User is not logged in.");
        }

        // Sanitize and validate form inputs
        $supplier_name = htmlspecialchars(trim($_POST['supplier_name']));
        $product_name = htmlspecialchars(trim($_POST['product_name']));
        $supplier_email = htmlspecialchars(trim($_POST['supplier_email']));
        $supplier_phone = htmlspecialchars(trim($_POST['supplier_phone']));
        $supplier_location = htmlspecialchars(trim($_POST['supplier_location']));
        $note = htmlspecialchars(trim($_POST['note']));
        $supply_qty = intval($_POST['supply_qty']);

        if (empty($supplier_name) || empty($product_name) || empty($supply_qty)) {
            throw new Exception("Supplier name, product name, and supply quantity are required.");
        }

        // Insert new supplier
        $insert_supplier_query = "INSERT INTO suppliers (supplier_name, product_name, supplier_email, supplier_phone, supplier_location, note, supply_qty) 
                                  VALUES (:supplier_name, :product_name, :supplier_email, :supplier_phone, :supplier_location, :note, :supply_qty)";
        
        $stmt = $connection->prepare($insert_supplier_query);
        $stmt->bindParam(':supplier_name', $supplier_name);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':supplier_email', $supplier_email);
        $stmt->bindParam(':supplier_phone', $supplier_phone);
        $stmt->bindParam(':supplier_location', $supplier_location);
        $stmt->bindParam(':note', $note);
        $stmt->bindParam(':supply_qty', $supply_qty);
        
        // Execute the statement and check for success
        if ($stmt->execute()) {
            // Redirect back to listing page after insertion
            header('Location: page-list-suppliers.php');
            exit();
        } else {
            // Log the error if insertion failed
            error_log("Supplier insertion failed: " . implode(" | ", $stmt->errorInfo()));
            throw new Exception("Supplier insertion failed.");
        }
    } 
} catch (PDOException $e) {
    // Handle database errors
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle other errors
    error_log("Error: " . $e->getMessage());
    exit("Error: " . $e->getMessage());
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

?>


