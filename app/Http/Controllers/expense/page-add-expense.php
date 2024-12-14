<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Includes database connection

try {
    // Check if username is set in session, redirect to login if not
    if (!isset($_SESSION["username"])) {
        header("Location: loginpage.php"); // Redirect to login page
        exit();
    }

    $username = htmlspecialchars($_SESSION["username"]);

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

    // Retrieve user information from the users table
    $user_query = "SELECT id, username, email, date, phone, location, is_active, role, user_image FROM users WHERE username = :username";
    $stmt = $connection->prepare($user_query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        throw new Exception("User not found.");
    }

    // Retrieve and sanitize user details
    $email = htmlspecialchars($user_info['email']);
    $date = date('d F, Y', strtotime($user_info['date']));
    $location = htmlspecialchars($user_info['location']);
    $user_id = htmlspecialchars($user_info['id']);
    $existing_image = htmlspecialchars($user_info['user_image']);
    $image_to_display = !empty($existing_image) ? $existing_image : 'uploads/user/default.png';

    // Handle form submission for expenses (only on POST request)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            throw new Exception("User is not logged in.");
        }

        // Sanitize and validate form inputs
        $description = htmlspecialchars(trim($_POST['description']));
        $amount = floatval($_POST['amount']);
        $expense_date = htmlspecialchars(trim($_POST['expense_date']));
        $created_by = htmlspecialchars(trim($_POST['created_by']));

        if (empty($description) || empty($amount) || empty($expense_date) || empty($created_by)) {
            throw new Exception("All form fields are required.");
        }

        // Insert into expenses table
        $insert_expense_query = "INSERT INTO expenses (description, amount, expense_date, created_by) 
                                 VALUES (:description, :amount, :expense_date, :created_by)";
        $stmt = $connection->prepare($insert_expense_query);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':expense_date', $expense_date);
        $stmt->bindParam(':created_by', $created_by);
        
        if ($stmt->execute()) {
            header('Location: page-list-expense.php');
            exit();
        } else {
            error_log("Expense insertion failed: " . implode(" | ", $stmt->errorInfo()));
            throw new Exception("Expense insertion failed.");
        }
    }
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    exit("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    exit("Error: " . $e->getMessage());
}
?>

