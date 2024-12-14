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
    exit("Error: No username found in session.");
}

$username = htmlspecialchars($_SESSION["username"]);

// Retrieve user information from the users table
$user_query = "SELECT id, username, email, date FROM users WHERE username = :username";
$stmt = $connection->prepare($user_query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_info) {
    exit("Error: User not found.");
}

// Retrieve user email and registration date
$email = htmlspecialchars($user_info['email']);
$date = htmlspecialchars($user_info['date']);

// Retrieve sales data from the sales table only
$query = "SELECT 
            sales_id, 
            sale_date, 
            name AS product_name, 
            total_price, 
            sale_status AS sales_status, 
            sales_qty, 
            payment_status, 
            sales_price 
          FROM sales
          ORDER BY sale_date DESC";

$stmt = $connection->prepare($query);
$stmt->execute();
$sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $sales_id = $_POST['sales_id'] ?? null;

    

    // Delete sale
    if ($action === 'delete') {
        $query = "DELETE FROM sales WHERE sales_id = ?";
        $stmt = $connection->prepare($query);

        if ($stmt->execute([$sales_id])) {
            echo json_encode(['success' => 'Sale deleted successfully.']);
            exit;
        }

        echo json_encode(['error' => 'Failed to delete sale.']);
        exit;
    }

    // Save as PDF
    if ($action === 'save_pdf') {
        $query = "SELECT * FROM sales WHERE sales_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->execute([$sales_id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale) {
            $sales_price = $sale['sales_price'] ?? 'N/A';
            $total_price = $sale['total_price'] ?? 'N/A';

            // Generate PDF
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(40, 10, 'Sales Record');
            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(40, 10, 'Sale Date: ' . $sale['sale_date']);
            $pdf->Ln(8);
            $pdf->Cell(40, 10, 'Product Name: ' . $sale['product_name']);
            $pdf->Ln(8);
            $pdf->Cell(40, 10, 'Unit: ' . $sale['unit']);
            $pdf->Ln(8);
            $pdf->Cell(40, 10, 'Sales Price: $' . number_format($sales_price, 2));
            $pdf->Ln(8);
            $pdf->Cell(40, 10, 'Total Price: $' . number_format($total_price, 2));
            $pdf->Ln(8);
            $pdf->Cell(40, 10, 'Quantity: ' . $sale['sales_qty']);
            $pdf->Ln(8);
            $pdf->Cell(40, 10, 'Sales Status: ' . $sale['sales_status']);
            $pdf->Ln(8);
            $pdf->Cell(40, 10, 'Payment Status: ' . $sale['payment_status']);

            // Save the PDF file
            $pdf_filename = 'sale_' . $sales_id . '.pdf';
            $pdf->Output('F', $pdf_filename);

            echo json_encode(['success' => 'PDF saved successfully.', 'pdf_url' => $pdf_filename]);
            exit;
        }

        echo json_encode(['error' => 'Sale not found.']);
        exit;
    }

    // Invalid action response
    echo json_encode(['error' => 'Invalid action.']);
    exit;
}

// Fetch inventory notifications with product images
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
    $inventoryNotifications = $inventoryQuery->fetchAll();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch reports notifications with product images
try {
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
    echo "Error: " . $e->getMessage();
}

// Prepare user data for display
try {
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
    exit("Database error: " . $e->getMessage());
} catch (Exception $e) {
    exit("Error: " . $e->getMessage());
}
?>

