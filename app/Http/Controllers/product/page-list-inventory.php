<?php
// Start the session with specified settings
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);


// Include database connection
include('config.php'); // Includes database connection
require __DIR__ .  '/vendor/autoload.php';
require __DIR__ . ('/fpdf/fpdf.php');


// Check if username is set in session
if (!isset($_SESSION["username"])) {
    echo json_encode(['success' => false, 'message' => "No username found in session."]);
    exit;
}

$username = htmlspecialchars($_SESSION["username"]);

try {
    
    // Retrieve user information from the users table
    $user_query = "SELECT username, email, date FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        echo json_encode(['success' => false, 'message' => "User not found."]);
        exit;
    }

    // Retrieve user email and registration date
    $email = htmlspecialchars($user_info['email']);
    $date = htmlspecialchars($user_info['date']);

    // Fetch products from the database including their categories
    $fetch_products_query = "SELECT id, name, description, price, image_path, category, inventory_qty, stock_qty, supply_qty FROM products";
    $stmt = $connection->prepare($fetch_products_query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    exit;
}

// Handle POST requests for updating product information
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['field'], $_POST['value'])) {
        $id = intval($_POST['id']);
        $field = htmlspecialchars($_POST['field']);
        $value = htmlspecialchars($_POST['value']);

        // Validate field
        $allowed_fields = ['name', 'description', 'category', 'price'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'message' => 'Invalid field']);
            exit;
        }

        // Prepare and execute the update query
        try {
            $update_query = "UPDATE products SET $field = :value WHERE id = :id";
            $stmt = $connection->prepare($update_query);
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing POST parameters']);
        exit;
    }
}

try {
    // SQL query to fetch sales and product data
    $sql = "
        SELECT 
            s.sale_date, 
            p.id AS product_id, 
            p.name AS product_name, 
            s.sales_qty, 
            p.inventory_qty, 
            p.stock_qty,
            p.supply_qty,
            (p.inventory_qty - s.sales_qty) AS available_stock,
            s.product_id
        FROM 
            sales s
        JOIN 
            products p ON s.product_id = p.id
    ";

    $stmt = $connection->query($sql);
    $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the insert and update queries
    $check_query = "SELECT COUNT(*) FROM inventory WHERE product_id = :product_id";
    $insert_query = "
        INSERT INTO inventory (product_id, product_name, inventory_qty, sales_qty, available_stock, supply_qty, stock_qty)
        VALUES (:product_id, :product_name, :inventory_qty, :sales_qty, :available_stock, :supply_qty, :stock_qty)
    ";
    $update_query = "
        UPDATE inventory 
        SET product_name = :product_name, 
            inventory_qty = :inventory_qty, 
            sales_qty = :sales_qty, 
            available_stock = :available_stock, 
            supply_qty = :supply_qty, 
            stock_qty = :stock_qty
        WHERE product_id = :product_id
    ";

    $check_stmt = $connection->prepare($check_query);
    $insert_stmt = $connection->prepare($insert_query);
    $update_stmt = $connection->prepare($update_query);

    // Loop through the sales data
    foreach ($sales_data as $data) {
        // Check if the product exists in the inventory table
        $check_stmt->bindParam(':product_id', $data['product_id']);
        $check_stmt->execute();
        $exists = $check_stmt->fetchColumn();

        if ($exists) {
            // Update the existing record
            $update_stmt->bindParam(':product_id', $data['product_id']);
            $update_stmt->bindParam(':product_name', $data['product_name']);
            $update_stmt->bindParam(':inventory_qty', $data['inventory_qty']);
            $update_stmt->bindParam(':sales_qty', $data['sales_qty']);
            $update_stmt->bindParam(':available_stock', $data['available_stock']);
            $update_stmt->bindParam(':supply_qty', $data['supply_qty']);
            $update_stmt->bindParam(':stock_qty', $data['stock_qty']);
            $update_stmt->execute();
        } else {
            // Insert a new record
            $insert_stmt->bindParam(':product_id', $data['product_id']);
            $insert_stmt->bindParam(':product_name', $data['product_name']);
            $insert_stmt->bindParam(':inventory_qty', $data['inventory_qty']);
            $insert_stmt->bindParam(':sales_qty', $data['sales_qty']);
            $insert_stmt->bindParam(':available_stock', $data['available_stock']);
            $insert_stmt->bindParam(':supply_qty', $data['supply_qty']);
            $insert_stmt->bindParam(':stock_qty', $data['stock_qty']);
            $insert_stmt->execute();
        }
    }

  

} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    exit;
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


