<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

require 'config.php'; // Include database connection script

try {
    // Ensure the user is logged in
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
        $username = htmlspecialchars($_SESSION["username"]);

        try {
            $user_query = "SELECT id, username, date, email, phone, location, is_active, role, user_image FROM users WHERE username = :username";
            $stmt = $connection->prepare($user_query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_info) {
                // Set user data and sanitize
                $email = htmlspecialchars($user_info['email']);
                $date = date('d F, Y', strtotime($user_info['date']));
                $location = htmlspecialchars($user_info['location']);
                $user_id = htmlspecialchars($user_info['id']);
                $image_to_display = !empty($user_info['user_image']) ? htmlspecialchars($user_info['user_image']) : 'uploads/user/default.png';

                // Generate personalized greeting
                $current_hour = (int)date('H');
                $time_of_day = ($current_hour < 12) ? "Morning" : (($current_hour < 18) ? "Afternoon" : "Evening");
                $greeting = "Hi " . $username . ", Good " . $time_of_day;
            } else {
                $greeting = "Hello, Guest";
                $image_to_display = 'uploads/user/default.png';
            }
        } catch (Exception $e) {
            exit("Error: " . $e->getMessage());
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get and sanitize form data
        $invoiceData = [
            'invoice_number'     => $_POST['invoice_number'] ?? '',
            'customer_name'      => $_POST['customer_name'] ?? '',
            'invoice_description' => $_POST['invoice_description'] ?? '',
            'order_date'         => $_POST['order_date'] ?? '',
            'order_status'       => $_POST['order_status'] ?? '',
            'order_id'           => $_POST['order_id'] ?? '',
            'delivery_address'    => $_POST['delivery_address'] ?? '',
            'mode_of_payment'     => $_POST['mode_of_payment'] ?? '',
            'due_date'           => $_POST['due_date'] ?? '',
            'subtotal'           => $_POST['subtotal'] ?? 0,
            'discount'           => $_POST['discount'] ?? 0,
            'total_amount'       => $_POST['total_amount'] ?? 0,
        ];

        // Extract item details from form data
        $items = $_POST['item_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];

        // Validate that items are not empty
        if (empty($items) || empty($quantities) || empty($prices)) {
            throw new Exception("No items were added to the invoice.");
        }

        // Begin database transaction
        try {
            $connection->beginTransaction();

            // Insert main invoice data
            $invoiceQuery = "
                INSERT INTO invoices 
                (invoice_number, customer_name, invoice_description, order_date, order_status, order_id, 
                 delivery_address, mode_of_payment, due_date, subtotal, discount, total_amount)
                VALUES 
                (:invoice_number, :customer_name, :invoice_description, :order_date, :order_status, :order_id, 
                :delivery_address, :mode_of_payment, :due_date, :subtotal, :discount, :total_amount)
            ";

            $stmt = $connection->prepare($invoiceQuery);
            $stmt->execute($invoiceData);
            $invoiceId = $connection->lastInsertId();

            // Insert each item linked to the invoice
            $itemQuery = "
                INSERT INTO invoice_items (invoice_id, item_name, qty, price, total)
                VALUES (:invoice_id, :item_name, :qty, :price, :total)
            ";

            $itemStmt = $connection->prepare($itemQuery);

            foreach ($items as $index => $itemName) {
                // Calculate totals for each item
                $quantity = (int)($quantities[$index] ?? 0);
                $price = (float)($prices[$index] ?? 0);
                $total = $quantity * $price;

                // Bind parameters and execute for each item
                $itemStmt->execute([
                    ':invoice_id' => $invoiceId,
                    ':item_name'  => $itemName,
                    ':qty'        => $quantity,
                    ':price'      => $price,
                    ':total'      => $total
                ]);
            }

            $connection->commit();
            header("Location: pages-invoice.php");
            exit(); // Ensure no further code is executed
        } catch (PDOException $e) {
            $connection->rollBack();
            throw new Exception("Database error while processing invoice: " . $e->getMessage());
        }
        
    }

    // Fetch inventory notifications
    $inventoryQuery = $connection->prepare("
        SELECT i.product_name, i.available_stock, i.inventory_qty, i.sales_qty, p.image_path
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        WHERE i.available_stock < :low_stock OR i.available_stock > :high_stock
        ORDER BY i.last_updated DESC
    ");
    $inventoryQuery->execute([':low_stock' => 10, ':high_stock' => 1000]);
    $inventoryNotifications = $inventoryQuery->fetchAll();

    // Fetch report notifications
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
    $reportsQuery->execute([':high_revenue' => 10000, ':low_revenue' => 1000]);
    $reportsNotifications = $reportsQuery->fetchAll();

    try {
        // Prepare and execute the query to fetch detailed user information
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
        // Handle other exceptions
        exit("Error: " . $e->getMessage());
    }

} catch (PDOException $e) {
    exit("Database error: " . $e->getMessage());
} catch (Exception $e) {
    exit("Error: " . $e->getMessage());
}
?>

