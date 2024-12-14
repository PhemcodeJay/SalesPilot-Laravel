<?php

session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Includes database connection
require __DIR__ .  '/vendor/autoload.php';
require __DIR__ . ('/fpdf/fpdf.php');

// Check if username is set in session
if (!isset($_SESSION["username"])) {
    exit(json_encode(['success' => false, 'message' => "No username found in session."]));
}

$username = htmlspecialchars($_SESSION["username"]);

// Retrieve user information from the Users table
try {
    $user_query = "SELECT username, email, date FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        exit(json_encode(['success' => false, 'message' => "User not found."]));
    }

    $email = htmlspecialchars($user_info['email']);
    $date = htmlspecialchars($user_info['date']);

    // Fetch products from the database including their categories
    $fetch_products_query = "SELECT id, name, description, price, image_path, category, inventory_qty, cost FROM products";
    $stmt = $connection->prepare($fetch_products_query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit(json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]));
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $product_id = $_POST['id'] ?? null;

    try {
        // Ensure $connection is available
        if (!isset($connection)) {
            throw new Exception("Database connection not established.");
        }

        // Handle delete action
        if ($action === 'delete') {
            if (!$product_id) {
                throw new Exception("Product ID is required for deletion.");
            }

            $delete_query = "DELETE FROM products WHERE id = :id";
            $stmt = $connection->prepare($delete_query);
            $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => 'Product deleted']);
            } else {
                echo json_encode(['error' => 'Failed to delete product']);
            }
            exit;
        }

        // Handle update action
        if ($action === 'update') {
            $name = $_POST['name'] ?? null;
            $description = $_POST['description'] ?? null;
            $category = $_POST['category'] ?? null;
            $price = $_POST['price'] ?? null;
            $inventory_qty = $_POST['inventory_qty'] ?? null;
            $cost = $_POST['cost'] ?? null;

            if ($product_id && $name && $description && $category && $price && $inventory_qty && $cost) {
                $update_query = "UPDATE products 
                                 SET name = :name,  
                                     description = :description, 
                                     category = :category, 
                                     price = :price, 
                                     inventory_qty = :inventory_qty, 
                                     cost = :cost
                                 WHERE id = :id";
                $stmt = $connection->prepare($update_query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':inventory_qty', $inventory_qty, PDO::PARAM_INT);
                $stmt->bindParam(':cost', $cost);
                $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo json_encode(['success' => 'Product updated']);
                } else {
                    echo json_encode(['error' => 'Failed to update product']);
                }
            } else {
                echo json_encode(['error' => 'Incomplete form data']);
            }
            exit;
        }

        // Handle save as PDF action
        if ($action === 'save_pdf') {
            if (!$product_id) {
                throw new Exception("Product ID is required for generating PDF.");
            }

            // Fetch product data
            $query = "SELECT * FROM products WHERE id = :id";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // Generate PDF using FPDF
                require 'fpdf.php';
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(40, 10, 'Product Details');
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 10, 'Product Name: ' . $product['name']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Description: ' . $product['description']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Category: ' . $product['category']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Sales Price: $' . number_format($product['price'], 2));
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Inventory Quantity: ' . number_format($product['inventory_qty']));
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Cost: $' . number_format($product['cost'], 2));

                $pdf->Output('D', 'product_' . $product_id . '.pdf');
            } else {
                echo json_encode(['error' => 'Product not found']);
            }
            exit;
        }
    } catch (PDOException $e) {
        // Handle database errors
        error_log("PDO Error: " . $e->getMessage());
        echo json_encode(['error' => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        // Handle other exceptions
        error_log("Error: " . $e->getMessage());
        echo json_encode(['error' => "Error: " . $e->getMessage()]);
    }
}



// Fetch inventory and report notifications
try {
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

    $reportsQuery = $connection->prepare("
        SELECT JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_name')) AS product_name, 
               JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.total_sales')) AS revenue,
               p.image_path
        FROM reports r
        JOIN products p ON JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.product_id')) = p.id
        WHERE JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.total_sales')) > :high_revenue 
           OR JSON_UNQUOTE(JSON_EXTRACT(revenue_by_product, '$.total_sales')) < :low_revenue
        ORDER BY r.report_date DESC
    ");
    $reportsQuery->execute([
        ':high_revenue' => 10000,
        ':low_revenue' => 1000,
    ]);
    $reportsNotifications = $reportsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit(json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]));
}

// Fetch user details for the current session user
try {
    $user_query = "SELECT id, username, date, email, phone, location, is_active, role, user_image FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_info) {
        $email = htmlspecialchars($user_info['email']);
        $date = date('d F, Y', strtotime($user_info['date']));
        $location = htmlspecialchars($user_info['location']);
        $user_id = htmlspecialchars($user_info['id']);
        
        $existing_image = htmlspecialchars($user_info['user_image']);
        $image_to_display = !empty($existing_image) ? $existing_image : 'uploads/user/default.png';

    }
} catch (PDOException $e) {
    exit("Database error: " . $e->getMessage());
}

?>


