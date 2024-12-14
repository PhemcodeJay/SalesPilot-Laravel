<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Includes the updated config.php with the $connection variable

// Check if username is set in session
if (!isset($_SESSION["username"])) {
    throw new Exception("No username found in session.");
}

$username = htmlspecialchars($_SESSION["username"]);

// Retrieve user information from the users table
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

// Retrieve the time range from the request
$range = $_GET['range'] ?? 'yearly';
$startDate = '';
$endDate = '';

// Define the date range based on the selected period
switch ($range) {
    case 'weekly':
        $startDate = date('Y-m-d', strtotime('this week Monday'));
        $endDate = date('Y-m-d', strtotime('this week Sunday'));
        break;
    case 'monthly':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'yearly':
    default:
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
}

try {
    // Fetch product metrics for the first table (Product Name and Total Sales)
    $productMetricsQuery = $connection->prepare("
        SELECT p.name, SUM(s.sales_qty) AS total_sales 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        WHERE DATE(s.sale_date) BETWEEN :startDate AND :endDate 
        GROUP BY p.name
    ");
    $productMetricsQuery->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    $productMetrics = $productMetricsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch top 5 products by revenue
    $revenueByProductQuery = $connection->prepare("
        SELECT p.name, SUM(s.sales_qty * p.price) AS revenue 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        WHERE DATE(s.sale_date) BETWEEN :startDate AND :endDate 
        GROUP BY p.name 
        ORDER BY revenue DESC 
        LIMIT 5
    ");
    $revenueByProductQuery->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    $topProducts = $revenueByProductQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch inventory metrics for the third table
    $inventoryMetricsQuery = $connection->prepare("
        SELECT p.name, i.available_stock, i.inventory_qty, i.sales_qty 
        FROM inventory i 
        JOIN products p ON i.product_id = p.id
    ");
    $inventoryMetricsQuery->execute();
    $inventoryMetrics = $inventoryMetricsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch income overview for the last table
    $revenueQuery = $connection->prepare("
        SELECT DATE(s.sale_date) AS date, SUM(s.sales_qty * p.price) AS revenue 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        WHERE DATE(s.sale_date) BETWEEN :startDate AND :endDate 
        GROUP BY DATE(s.sale_date)
    ");
    $revenueQuery->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    $revenueData = $revenueQuery->fetchAll(PDO::FETCH_ASSOC);

    $totalCostQuery = $connection->prepare("
        SELECT DATE(sale_date) AS date, SUM(sales_qty * cost) AS total_cost 
        FROM sales 
        JOIN products ON sales.product_id = products.id 
        WHERE DATE(sale_date) BETWEEN :startDate AND :endDate 
        GROUP BY DATE(sale_date)
    ");
    $totalCostQuery->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    $totalCostData = $totalCostQuery->fetchAll(PDO::FETCH_ASSOC);

    $expenseQuery = $connection->prepare("
        SELECT DATE(expense_date) AS date, SUM(amount) AS total_expenses 
        FROM expenses 
        WHERE DATE(expense_date) BETWEEN :startDate AND :endDate 
        GROUP BY DATE(expense_date)
    ");
    $expenseQuery->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    $expenseData = $expenseQuery->fetchAll(PDO::FETCH_ASSOC);

    // Combine revenue, total cost, and additional expenses for the income overview
    $incomeOverview = [];
    foreach ($revenueData as $data) {
        $date = $data['date'];
        $revenue = isset($data['revenue']) ? (float)$data['revenue'] : 0;

        $totalCost = 0;
        foreach ($totalCostData as $cost) {
            if ($cost['date'] === $date) {
                $totalCost = isset($cost['total_cost']) ? (float)$cost['total_cost'] : 0;
                break;
            }
        }

        $expenses = 0;
        foreach ($expenseData as $expense) {
            if ($expense['date'] === $date) {
                $expenses = isset($expense['total_expenses']) ? (float)$expense['total_expenses'] : 0;
                break;
            }
        }

        $totalExpenses = $totalCost + $expenses;
        $profit = $revenue - $totalExpenses;

        $incomeOverview[] = [
            'date' => $date,
            'revenue' => number_format($revenue, 2),
            'total_expenses' => number_format($totalExpenses, 2),
            'profit' => number_format($profit, 2)
        ];
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
?>


