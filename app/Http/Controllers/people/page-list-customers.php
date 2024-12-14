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

try {
    // Check if user is logged in
    if (!isset($_SESSION["username"])) {
        throw new Exception("No username found in session.");
    }

    $username = htmlspecialchars($_SESSION["username"]);

    // Fetch user information
    $user_query = "SELECT username, email, date, phone, location, user_image FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        throw new Exception("User not found.");
    }

    $email = htmlspecialchars($user_info['email']);
    $date = date('d F, Y', strtotime($user_info['date']));
    $location = htmlspecialchars($user_info['location']);
    $existing_image = htmlspecialchars($user_info['user_image']);
    $image_to_display = !empty($existing_image) ? $existing_image : 'uploads/user/default.png';

    // Retrieve customers from the customers table
$customers_query = "SELECT customer_id, customer_name, customer_email, customer_phone, customer_location FROM customers";
$stmt = $connection->prepare($customers_query);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $customer_id = $_POST['customer_id'] ?? null;

    if ($action === 'delete') {
        // Handle delete action
        if ($customer_id) {
            $delete_query = "DELETE FROM customers WHERE customer_id = :customer_id";
            $stmt = $connection->prepare($delete_query);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->execute();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo 'No customer ID provided.';
        }
    } elseif ($action === 'save_pdf') {
        // Handle save as PDF action
        if ($customer_id) {
            $query = "SELECT * FROM customers WHERE customer_id = :customer_id";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(40, 10, 'Customer Details');
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 10, 'Name: ' . $customer['customer_name']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Email: ' . $customer['customer_email']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Phone: ' . $customer['customer_phone']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Location: ' . $customer['customer_location']);
                $pdf->Output('D', 'customer_' . $customer_id . '.pdf');
            } else {
                echo 'Customer not found.';
            }
        } else {
            echo 'No customer ID provided.';
        }
        exit;
    } elseif ($action === 'update') {
        // Handle update action
        $customer_name = $_POST['customer_name'] ?? null;
        $customer_email = $_POST['customer_email'] ?? null;
        $customer_phone = $_POST['customer_phone'] ?? null;
        $customer_location = $_POST['customer_location'] ?? null;

        if ($customer_id && $customer_name && $customer_email && $customer_phone && $customer_location) {
            $update_query = "UPDATE customers 
                             SET customer_name = :customer_name, customer_email = :customer_email, 
                                 customer_phone = :customer_phone, customer_location = :customer_location
                             WHERE customer_id = :customer_id";
            $stmt = $connection->prepare($update_query);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':customer_name', $customer_name);
            $stmt->bindParam(':customer_email', $customer_email);
            $stmt->bindParam(':customer_phone', $customer_phone);
            $stmt->bindParam(':customer_location', $customer_location);
            $stmt->execute();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo 'Incomplete form data.';
        }
    }
}


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
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    exit("Error: " . $e->getMessage());
}
?>


