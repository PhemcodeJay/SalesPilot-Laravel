<?php
// Include the database connection
require 'config.php'; // Ensure this file contains your PDO connection settings

// Initialize variables
$message = '';
$invoiceId = '';
$invoiceNumber = '';
$customerName = '';
$orderDate = '';
$dueDate = '';
$subtotal = '';
$discount = '';
$totalAmount = '';
$invoiceItems = [];

// Check if an invoice ID is provided to fetch existing data
if (isset($_GET['invoice_id'])) {
    $invoiceId = $_GET['invoice_id'];

    // Fetch invoice details
    $invoiceQuery = "SELECT * FROM invoices WHERE invoice_id = ?";
    $stmt = $connection->prepare($invoiceQuery);
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($invoice) {
        // Populate the variables with fetched data
        $invoiceNumber = $invoice['invoice_number'];
        $customerName = $invoice['customer_name'];
        $orderDate = $invoice['order_date'];
        $dueDate = $invoice['due_date'];
        $subtotal = $invoice['subtotal'];
        $discount = $invoice['discount'];
        $totalAmount = $invoice['total_amount'];

        // Fetch associated invoice items
        $itemsQuery = "SELECT * FROM invoice_items WHERE invoice_id = ?";
        $itemStmt = $connection->prepare($itemsQuery);
        $itemStmt->execute([$invoiceId]);
        $invoiceItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle POST request to update invoice and items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the invoice ID from the POST data
    $invoiceId = $_POST['invoice_id'];
    $invoiceNumber = $_POST['invoice_number'];
    $customerName = $_POST['customer_name'];
    $orderDate = $_POST['order_date'];
    $dueDate = $_POST['due_date'];
    $subtotal = $_POST['subtotal'];
    $discount = $_POST['discount'];
    $totalAmount = $subtotal - ($subtotal * ($discount / 100)); // Calculate total amount

    // Prepare the SQL statement to update the invoice details
    $updateInvoiceQuery = "UPDATE invoices 
                            SET invoice_number = ?, 
                                customer_name = ?, 
                                order_date = ?, 
                                due_date = ?, 
                                subtotal = ?, 
                                discount = ?, 
                                total_amount = ?
                            WHERE invoice_id = ?";

    try {
        $stmt = $connection->prepare($updateInvoiceQuery);
        $stmt->execute([$invoiceNumber, $customerName, $orderDate, $dueDate, $subtotal, $discount, $totalAmount, $invoiceId]);

        // Update items or insert new ones
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $items = $_POST['items']; // This should be an array of items
            
            // First, delete all existing items for this invoice
            $deleteItemsQuery = "DELETE FROM invoice_items WHERE invoice_id = ?";
            $deleteStmt = $connection->prepare($deleteItemsQuery);
            $deleteStmt->execute([$invoiceId]);

            foreach ($items as $item) {
                $itemId = $item['id']; // Unique identifier for the item
                $itemName = $item['item_name'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $total = $quantity * $price; // Calculate total for the item

                // Insert new item
                $insertItemQuery = "INSERT INTO invoice_items (invoice_id, item_name, qty, price, total) 
                                    VALUES (?, ?, ?, ?, ?)";
                
                $itemStmt = $connection->prepare($insertItemQuery);
                $itemStmt->execute([$invoiceId, $itemName, $quantity, $price, $total]);
            }
        }

        // Success message
        $message = "Invoice and items updated successfully.";

        // Redirect to pages-invoice.php after successful update
        header("Location: pages-invoice.php?message=" . urlencode($message));
        exit(); // Ensure no further code is executed after the redirect
    } catch (PDOException $e) {
        // Handle any errors during the update
        $message = "Error updating invoice: " . $e->getMessage();
        // Optionally log the error or handle it as needed
        error_log($message); // Log the error for debugging
    }
}
?>

