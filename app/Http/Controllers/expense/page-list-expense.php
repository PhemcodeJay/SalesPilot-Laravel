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
    // Check if username is set in session
    if (!isset($_SESSION["username"])) {
        throw new Exception("No username found in session.");
    }

    $username = htmlspecialchars($_SESSION["username"]);

    // Retrieve user information from the Users table
    $user_query = "SELECT username, email, date FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        throw new Exception("User not found.");
    }

    // Retrieve user email and registration date
    $email = htmlspecialchars($user_info['email']);
    $date = htmlspecialchars($user_info['date']);

  // Retrieve expenses from the expenses table
$expenses_query = "SELECT expense_id, description, amount, expense_date, created_by FROM expenses";
$stmt = $connection->prepare($expenses_query);
$stmt->execute();
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $expense_id = $_POST['expense_id'] ?? null;

    if ($action === 'delete') {
        if ($expense_id) {
            $delete_query = "DELETE FROM expenses WHERE expense_id = :expense_id";
            $stmt = $connection->prepare($delete_query);
            $stmt->bindParam(':expense_id', $expense_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Expense not found or failed to delete.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'No expense ID provided for deletion.']);
        }
        
    } elseif ($action === 'save_pdf') {
        // Handle save as PDF action
        if ($expense_id) {
            $query = "SELECT * FROM expenses WHERE expense_id = :expense_id";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':expense_id', $expense_id);  // Fixed parameter binding
            $stmt->execute();
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($expense) {
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(40, 10, 'Expense Details');
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 10, 'Date: ' . $expense['expense_date']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Description: ' . $expense['description']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Amount: $' . $expense['amount']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Created by: ' . $expense['created_by']);
                
                ob_clean();

                // Output the PDF
                $pdf->Output('D', 'expense_' . $expense_id . '.pdf');
            } else {
                echo 'Expense not found.';
            }
        } else {
            echo 'No expense ID provided.';
        }
        exit;
    } elseif ($action === 'update') {
        // Handle update action
        $description = $_POST['description'] ?? null;
        $amount = $_POST['amount'] ?? null;
        $expense_date = $_POST['expense_date'] ?? null;
        $created_by = $_POST['created_by'] ?? null;

        if ($expense_id && $description && $amount && $expense_date && $created_by) {
            $update_query = "UPDATE expenses 
                             SET description = :description, amount = :amount, expense_date = :expense_date, created_by = :created_by
                             WHERE expense_id = :expense_id";
            $stmt = $connection->prepare($update_query);
            $stmt->bindParam(':expense_id', $expense_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':expense_date', $expense_date);
            $stmt->bindParam(':created_by', $created_by);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Update failed or no changes detected.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Incomplete data for updating expense.']);
        }
        exit;
    }
}


} catch (PDOException $e) {
    // Handle database errors
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle other errors
    error_log("Error: " . $e->getMessage());
    exit("Error: " . $e->getMessage());
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

