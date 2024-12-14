<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Includes database connection

// Check if username is set in session
if (!isset($_SESSION["username"])) {
    die("No username found in session.");
}

$username = htmlspecialchars($_SESSION["username"]);

// Retrieve user information from the users table
$user_query = "SELECT id, username, email, date FROM users WHERE username = :username";
$stmt = $connection->prepare($user_query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_info) {
    die("User not found.");
}

$email = htmlspecialchars($user_info['email']);
$date = htmlspecialchars($user_info['date']);
$user_id = $user_info['id'];

// Check if the user is logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Log session data for debugging
        error_log("Session ID: " . session_id());
        error_log("Session variables: " . print_r($_SESSION, true));

        // Sanitize and validate form inputs
        $name = htmlspecialchars(trim($_POST['name']));
        $sale_status = htmlspecialchars(trim($_POST['sale_status']));
        $sales_price = filter_var($_POST['sales_price'], FILTER_VALIDATE_FLOAT);
        $total_price = filter_var($_POST['total_price'], FILTER_VALIDATE_FLOAT);
        $sales_qty = filter_var($_POST['sales_qty'], FILTER_VALIDATE_INT);
        $payment_status = htmlspecialchars(trim($_POST['payment_status']));
        $sale_note = htmlspecialchars(trim($_POST['sale_note']));
        $staff_name = htmlspecialchars(trim($_POST['staff_name']));
        $customer_name = htmlspecialchars(trim($_POST['customer_name']));

        // Validate required fields
        if (empty($name) || empty($sale_status) || empty($staff_name) || empty($customer_name)) {
            die("Required fields are missing.");
        }

        

        try {
            $connection->beginTransaction();

            // Retrieve product_id from the products table
            $check_product_query = "SELECT id FROM products WHERE name = :name";
            $stmt = $connection->prepare($check_product_query);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            $product_id = $stmt->fetchColumn();

            if (!$product_id) {
                throw new Exception("Product not found.");
            }

            // Retrieve staff_id from the staffs table
            $check_staff_query = "SELECT staff_id FROM staffs WHERE staff_name = :staff_name";
            $stmt = $connection->prepare($check_staff_query);
            $stmt->bindParam(':staff_name', $staff_name);
            $stmt->execute();
            $staff_id = $stmt->fetchColumn();

            if (!$staff_id) {
                // Staff does not exist, so insert the new staff member
                $insert_staff_query = "INSERT INTO staffs (staff_name) VALUES (:staff_name)";
                $stmt = $connection->prepare($insert_staff_query);
                $stmt->bindParam(':staff_name', $staff_name);
                if ($stmt->execute()) {
                    // Get the last inserted staff_id
                    $staff_id = $connection->lastInsertId();
                } else {
                    throw new Exception("Failed to add new staff member.");
                }  
            };


            // Retrieve customer_id from the customers table
            $check_customer_query = "SELECT customer_id FROM customers WHERE customer_name = :customer_name";
            $stmt = $connection->prepare($check_customer_query);
            $stmt->bindParam(':customer_name', $customer_name);
            $stmt->execute();
            $customer_id = $stmt->fetchColumn();

            if (!$customer_id) {
                // Customer does not exist, so insert the new customer
                $insert_customer_query = "INSERT INTO customers (customer_name) VALUES (:customer_name)";
                $stmt = $connection->prepare($insert_customer_query);
                $stmt->bindParam(':customer_name', $customer_name);
                if ($stmt->execute()) {
                    // Get the last inserted customer_id
                    $customer_id = $connection->lastInsertId();
                } else {
                    throw new Exception("Failed to add new customer.");
                }
            }

            // SQL query for inserting into sales table
            $insert_sale_query = "INSERT INTO sales (product_id, name, staff_id, customer_id, total_price, sales_price, sales_qty, sale_note, sale_status, payment_status, user_id)
                                  VALUES (:product_id, :name, :staff_id, :customer_id, :total_price, :sales_price, :sales_qty, :sale_note, :sale_status, :payment_status, :user_id)";
            $stmt = $connection->prepare($insert_sale_query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':staff_id', $staff_id);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':total_price', $total_price);
            $stmt->bindParam(':sales_price', $sales_price);
            $stmt->bindParam(':sales_qty', $sales_qty);
            $stmt->bindParam(':sale_note', $sale_note);
            $stmt->bindParam(':sale_status', $sale_status);
            $stmt->bindParam(':payment_status', $payment_status);
            $stmt->bindParam(':user_id', $user_id);

            // Execute and commit transaction
            if ($stmt->execute()) {
                $connection->commit();
                header('Location: page-list-sale.php');
                exit();
            } else {
                $connection->rollBack();
                die("Sale insertion failed.");
            }
        } catch (Exception $e) {
            $connection->rollBack();
            error_log("Error: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        }
    }
} else {
    echo "Error: User not logged in.";
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
    $inventoryNotifications = $inventoryQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch reports notifications with product images
    $reportsQuery = $connection->prepare("
        SELECT JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_name')) AS product_name, 
               JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) AS revenue,
               p.image_path
        FROM reports r
        JOIN products p ON JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_name')) = p.name
        WHERE JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) < :low_revenue OR 
              JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.revenue')) > :high_revenue
        ORDER BY r.report_date DESC
    ");
    $reportsQuery->execute([
        ':low_revenue' => 1000,
        ':high_revenue' => 5000,
    ]);
    $reportsNotifications = $reportsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
    exit();
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

