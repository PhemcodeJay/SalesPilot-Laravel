<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

error_reporting(E_ALL);
ini_set('display_errors', 1);

include  'config.php'; // Include database connection script
require __DIR__ .  '/vendor/autoload.php';
require __DIR__ .  '/fpdf/fpdf.php';

// Check if the user is logged in
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

}


// Fetch invoices from the database
try {
    $invoices_query = "SELECT invoice_id, invoice_number, customer_name, order_date, mode_of_payment, order_status, total_amount FROM invoices"; 
    $stmt = $connection->prepare($invoices_query);
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching invoices: " . $e->getMessage();
}

// Fetch invoice items from the database
try {
    $invoice_items_query = "SELECT invoice_id, item_name, qty AS quantity, price, total 
                            FROM invoice_items";
    $stmt = $connection->prepare($invoice_items_query);
    $stmt->execute();
    $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching invoice items: " . $e->getMessage();
}

if (isset($_GET['invoice_id'])) {
    $invoiceId = $_GET['invoice_id'];

    // Fetch invoice details
    $invoiceQuery = "SELECT * FROM invoices WHERE invoice_id = ?";
    $stmt = $connection->prepare($invoiceQuery);
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch associated invoice items
    $itemsQuery = "SELECT * FROM invoice_items WHERE invoice_id = ?";
    $itemStmt = $connection->prepare($itemsQuery);
    $itemStmt->execute([$invoiceId]);
    $invoiceItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if invoice exists
    if ($invoice) {
        // Create instance of FPDF
        $pdf = new FPDF();
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Invoice', 0, 1, 'C');
        
        // Add invoice details
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Invoice Number: ' . $invoice['invoice_number'], 0, 1);
        $pdf->Cell(0, 10, 'Customer Name: ' . $invoice['customer_name'], 0, 1);
        $pdf->Cell(0, 10, 'Order Date: ' . $invoice['order_date'], 0, 1);
        $pdf->Cell(0, 10, 'Due Date: ' . $invoice['due_date'], 0, 1);
        $pdf->Cell(0, 10, 'Subtotal: ' . $invoice['subtotal'], 0, 1);
        $pdf->Cell(0, 10, 'Discount: ' . $invoice['discount'], 0, 1);
        $pdf->Cell(0, 10, 'Total Amount: ' . $invoice['total_amount'], 0, 1);
        
        // Add invoice items header
        $pdf->Cell(0, 10, 'Invoice Items:', 0, 1);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 10, 'Item Name', 1);
        $pdf->Cell(30, 10, 'Quantity', 1);
        $pdf->Cell(30, 10, 'Price', 1);
        $pdf->Cell(30, 10, 'Total', 1);
        $pdf->Ln();
        
        // Add invoice items
        $pdf->SetFont('Arial', '', 12);
        foreach ($invoiceItems as $item) {
            $pdf->Cell(50, 10, $item['item_name'], 1);
            $pdf->Cell(30, 10, $item['qty'], 1);
            $pdf->Cell(30, 10, $item['price'], 1);
            $pdf->Cell(30, 10, $item['total'], 1);
            $pdf->Ln();
        }

        // Output the PDF
        $pdf->Output('D', 'invoice_' . $invoiceId . '.pdf'); // Download the file
    } else {
        echo "Invoice not found.";
    }
} 


// Fetch invoice function
function fetchInvoice($invoice_id) {
    global $connection;
    $invoice_query = "SELECT id, invoice_number, customer_name, order_date, total_amount FROM invoices WHERE id = :invoice_id";
    $stmt = $connection->prepare($invoice_query);
    $stmt->bindParam(':invoice_id', $invoice_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch invoice items function
function fetchInvoiceItems($invoice_id) {
    global $connection;
    $items_query = "SELECT item_name, qty AS quantity, price, total FROM invoice_items WHERE id = :invoice_id";
    $stmt = $connection->prepare($items_query);
    $stmt->bindParam(':invoice_id', $invoice_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// AJAX request handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the action is defined
    $action = $_POST['action'] ?? null;
    $invoice_id = $_POST['invoice_id'] ?? null;

    switch ($action) {
        case 'view':
            $invoice_id = $_POST['invoice_id'] ?? null; // Retrieve invoice ID from POST data
        
            if ($invoice_id) {
                $invoice = fetchInvoice($invoice_id); // Fetch invoice details
        
                if ($invoice) {
                    // Fetch associated invoice items
                    $invoiceItems = fetchInvoiceItems($invoice_id);
        
                    // Validate and format the items
                    if (!is_array($invoiceItems)) {
                        error_log('Invoice items fetch returned unexpected type: ' . gettype($invoiceItems));
                        $invoiceItems = []; // Set to empty array if not as expected
                    }
        
                    // Return JSON response with invoice details and items
                    echo json_encode([
                        'success' => true,
                        'invoice_number' => $invoice['invoice_number'],
                        'customer_name' => $invoice['customer_name'],
                        'order_date' => $invoice['order_date'],
                        'total_amount' => $invoice['total_amount'],
                        'items' => array_map(function ($item) {
                            return [
                                'item_name' => $item['item_name'],
                                'quantity' => $item['quantity'],
                                'price' => $item['price'],
                                'total' => $item['quantity'] * $item['price'] // Calculate total for each item
                            ];
                            
                        }, $invoiceItems)
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invoice ID is missing.']);
            }
            break;        


        

        case 'delete':
            if ($invoice_id) {
                $delete_query = "DELETE FROM invoices WHERE invoice_id = :invoice_id";
                $stmt = $connection->prepare($delete_query);
                $stmt->bindParam(':invoice_id', $invoice_id);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Invoice deleted successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete invoice.']);
                }
            }
            break;

        case 'edit':
            if ($invoice_id) {
                header("Location: edit-invoice.php?invoice_id=" . urlencode($invoice_id));
                exit;
            }
            break;
    }
}


// Handle PDF generation (GET request for invoice)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['invoice_id'])) {
    // Sanitize the input
    $invoice_id = filter_var($_GET['invoice_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Fetch the invoice details
    $query = "SELECT * FROM invoices WHERE invoice_id = :invoice_id";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':invoice_id', $invoice_id, PDO::PARAM_INT);
    $stmt->execute();
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    

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
?>

