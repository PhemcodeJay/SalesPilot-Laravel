<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php');

// Check if username is set in session
if (!isset($_SESSION["username"])) {
    exit("No username found in session."); // Changed to exit instead of throw
}

$username = htmlspecialchars($_SESSION["username"]);

// Retrieve user information from the users table
$user_query = "SELECT username, email, date FROM users WHERE username = :username";
$stmt = $connection->prepare($user_query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_info) {
    exit("User not found."); // Changed to exit instead of throw
}

$email = htmlspecialchars($user_info['email']);
$date = htmlspecialchars($user_info['date']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if the user is logged in
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            exit("User is not logged in."); // Changed to exit instead of throw
        }

        // Sanitize and validate form inputs
        $name = htmlspecialchars(trim($_POST['name']));
        $staff_name = htmlspecialchars(trim($_POST['staff_name']));
        $product_type = htmlspecialchars(trim($_POST['product_type']));
        $category = htmlspecialchars(trim($_POST['category_name']));
        $cost = isset($_POST['cost']) ? floatval($_POST['cost']) : 0; // Added default
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0; // Added default
        $stock_qty = isset($_POST['stock_qty']) ? intval($_POST['stock_qty']) : 0; // Added default
        $supply_qty = isset($_POST['supply_qty']) ? intval($_POST['supply_qty']) : 0; // Added default
        $description = htmlspecialchars(trim($_POST['description']));

        // Handle category logic
        if ($category === 'New') {
            $new_category = htmlspecialchars(trim($_POST['new_category']));

            // Check if the new category already exists
            $select_category_query = "SELECT category_id FROM categories WHERE category_name = :category_name";
            $stmt = $connection->prepare($select_category_query);
            $stmt->bindParam(':category_name', $new_category);
            $stmt->execute();
            $category_result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category_result) {
                // Insert new category into categories table
                $insert_category_query = "INSERT INTO categories (category_name) VALUES (:category_name)";
                $stmt = $connection->prepare($insert_category_query);
                $stmt->bindParam(':category_name', $new_category);
                $stmt->execute();

                // Retrieve the newly inserted category_id
                $category_id = $connection->lastInsertId();
            } else {
                // Use the existing category_id
                $category_id = $category_result['category_id'];
            }
        } else {
            // Fetch the category_id from the existing category
            $select_category_query = "SELECT category_id FROM categories WHERE category_name = :category_name";
            $stmt = $connection->prepare($select_category_query);
            $stmt->bindParam(':category_name', $category);
            $stmt->execute();
            $category_result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category_result) {
                exit("Category not found."); // Changed to exit instead of throw
            }

            $category_id = $category_result['category_id'];
        }

        // File upload handling
        $upload_dir = 'uploads/products/';
        $image_name = $_FILES['pic']['name'];
        $image_tmp = $_FILES['pic']['tmp_name'];
        $image_path = $upload_dir . basename($image_name);

        // Move uploaded file to designated directory
        if (!move_uploaded_file($image_tmp, $image_path)) {
            exit("File upload failed."); // Changed to exit instead of throw
        }

        // Check if the product already exists (to update it)
        $check_product_query = "SELECT id FROM products WHERE name = :name AND category_id = :category_id";
        $stmt = $connection->prepare($check_product_query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        $existing_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_product) {
            // Update existing product
            $update_product_query = "UPDATE products
                                     SET staff_name = :staff_name, product_type = :product_type, category_id = :category_id, 
                                         cost = :cost, price = :price, stock_qty = :stock_qty, supply_qty = :supply_qty, 
                                         description = :description, image_path = :image_path
                                     WHERE id = :product_id";
            $stmt = $connection->prepare($update_product_query);
            $stmt->bindParam(':staff_name', $staff_name);
            $stmt->bindParam(':product_type', $product_type);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':cost', $cost);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock_qty', $stock_qty);
            $stmt->bindParam(':supply_qty', $supply_qty);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image_path', $image_path);
            $stmt->bindParam(':product_id', $existing_product['id']);
            $stmt->execute();
        } else {
            // Insert new product
            $insert_product_query = "INSERT INTO products (name, staff_name, product_type, category_id, cost, price, stock_qty, supply_qty, description, image_path)
                                     VALUES (:name, :staff_name, :product_type, :category_id, :cost, :price, :stock_qty, :supply_qty, :description, :image_path)";
            $stmt = $connection->prepare($insert_product_query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':staff_name', $staff_name);
            $stmt->bindParam(':product_type', $product_type);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':cost', $cost);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock_qty', $stock_qty);
            $stmt->bindParam(':supply_qty', $supply_qty);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image_path', $image_path);
            $stmt->execute();
        }

        header('Location: page-list-product.php');
        exit(); // Use exit after redirect
    } catch (PDOException $e) {
        error_log("PDO Error: " . $e->getMessage());
        exit("Database Error: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        exit("Error: " . $e->getMessage());
    }
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

?>


