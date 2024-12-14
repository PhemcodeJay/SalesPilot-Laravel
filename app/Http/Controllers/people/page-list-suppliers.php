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
    exit("No username found in session.");
}

$username = htmlspecialchars($_SESSION["username"]);

// Retrieve user information from the users table
try {
    $user_query = "SELECT username, email, date FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        exit("User not found.");
    }

    // Retrieve user email and registration date
    $email = htmlspecialchars($user_info['email']);
    $date = htmlspecialchars($user_info['date']);
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
}

// Retrieve supplier from the suppliers table
$supplier_query = "SELECT supplier_id, supplier_name, supplier_email, supplier_phone, supplier_location FROM suppliers";
$stmt = $connection->prepare($supplier_query);
$stmt->execute();
$supplier = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form actions
$action = $_POST['action'] ?? null;
$supplier_id = $_POST['supplier_id'] ?? null;

if ($action && $supplier_id) {
    try {
        // Handle delete action
        if ($action === 'delete') {
            $delete_query = "DELETE FROM suppliers WHERE supplier_id = :supplier_id";
            $stmt = $connection->prepare($delete_query);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
            exit;
        }

        // Handle update action
        elseif ($action === 'update') {
            $supplier_name = $_POST['supplier_name'] ?? null;
            $supplier_email = $_POST['supplier_email'] ?? null;
            $supplier_phone = $_POST['supplier_phone'] ?? null;
            $supplier_location = $_POST['supplier_location'] ?? null;

            if ($supplier_name && $supplier_email && $supplier_phone && $supplier_location) {
                $update_query = "UPDATE suppliers 
                                 SET supplier_name = :supplier_name, supplier_email = :supplier_email, 
                                     supplier_phone = :supplier_phone, supplier_location = :supplier_location
                                 WHERE supplier_id = :supplier_id";
                $stmt = $connection->prepare($update_query);
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->bindParam(':supplier_name', $supplier_name);
                $stmt->bindParam(':supplier_email', $supplier_email);
                $stmt->bindParam(':supplier_phone', $supplier_phone);
                $stmt->bindParam(':supplier_location', $supplier_location);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Incomplete form data']);
            }
            exit;
        }
    } catch (PDOException $e) {
        error_log("PDO Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        exit;
    }
}

// Handle PDF generation (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['supplier_id'])) {
    $supplier_id = $_GET['supplier_id'];
    $query = "SELECT * FROM suppliers WHERE supplier_id = :supplier_id";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
    $stmt->execute();
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($supplier) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Supplier Details');
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 10, 'Name: ' . $supplier['supplier_name']);
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Email: ' . $supplier['supplier_email']);
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Phone: ' . $supplier['supplier_phone']);
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Location: ' . $supplier['supplier_location']);
        $pdf->Output('D', 'supplier_' . $supplier_id . '.pdf');
        exit;
    } else {
        echo 'Supplier not found.';
        exit;
    }
}

// Fetch supplier data from the database
try {
    $query = "SELECT * FROM suppliers";
    $stmt = $connection->prepare($query);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
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
    $inventoryNotifications = $inventoryQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
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
    $reportsNotifications = $reportsQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
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

