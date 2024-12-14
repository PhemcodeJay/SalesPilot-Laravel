<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php'); // Ensure this file sets up the $connection variable

$email = $date = $greeting = "N/A";
$total_products_sold = $total_sales = $total_cost = "0.00";



// Default to monthly data
$range = $_GET['range'] ?? 'month'; // Can be 'year', 'month', or 'week'

try {
    // Query for different ranges
    if ($range === 'year') {
        $sql = "
            SELECT
                IFNULL(SUM(s.sales_qty * p.price), 0) AS total_revenue
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE YEAR(s.sale_date) = YEAR(CURDATE())
        ";
    } elseif ($range === 'week') {
        $sql = "
            SELECT
                IFNULL(SUM(s.sales_qty * p.price), 0) AS total_revenue
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE WEEK(s.sale_date) = WEEK(CURDATE()) AND YEAR(s.sale_date) = YEAR(CURDATE())
        ";
    } else {
        // Default to month
        $sql = "
            SELECT
                IFNULL(SUM(s.sales_qty * p.price), 0) AS total_revenue
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE MONTH(s.sale_date) = MONTH(CURDATE()) AND YEAR(s.sale_date) = YEAR(CURDATE())
        ";
    }
    
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);



} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $username = htmlspecialchars($_SESSION["username"]);
    
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
    
            // Determine the time of day for personalized greeting
            $current_hour = (int)date('H');
            if ($current_hour < 12) {
                $time_of_day = "Morning";
            } elseif ($current_hour < 18) {
                $time_of_day = "Afternoon";
            } else {
                $time_of_day = "Evening";
            }
    
            // Personalized greeting
            $greeting = "Hi " . $username . ", Good " . $time_of_day;
        } else {
            // If no user data, fallback to guest greeting and default image
            $greeting = "Hello, Guest";
            $image_to_display = 'uploads/user/default.png';
        }
    } catch (PDOException $e) {
        // Handle database errors
        exit("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        // Handle user not found or other exceptions
        exit("Error: " . $e->getMessage());
    }
    

    
}


try {
    // Calculate total revenue
    $sql = "
    SELECT
        IFNULL(SUM(s.sales_qty * p.price), 0) AS total_revenue
    FROM sales s
    JOIN products p ON s.product_id = p.id
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $result["total_revenue"];

    // Calculate total cost (cost of products sold)
    $sql = "
    SELECT
        IFNULL(SUM(s.sales_qty * p.cost), 0) AS total_cost
    FROM sales s
    JOIN products p ON s.product_id = p.id
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_cost = $result["total_cost"];

    // Fetch total expenses from the expenses table
    $sql = "
    SELECT
        IFNULL(SUM(amount), 0) AS total_expenses
    FROM expenses
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_expenses = $result["total_expenses"];

    // Calculate total expenses (product cost + other expenses)
    $total_expenses_combined = $total_cost + $total_expenses;

    // Calculate profit
    $total_profit = $total_revenue - $total_expenses_combined;

    // Calculate the percentage of total expenses combined compared to revenue
    $percentage_expenses_to_revenue = 0;  // Default value
    if ($total_revenue > 0) {
        // Total expenses combined divided by total revenue * 100
        $percentage_expenses_to_revenue = ($total_expenses_combined / $total_revenue) * 100;
    }

    // Calculate the percentage of total profit combined compared to revenue
    $percentage_profit_to_revenue = 0;  // Default value
    if ($total_revenue > 0) {
        // Total profit combined divided by total revenue * 100
        $percentage_profit_to_revenue = ($total_profit / $total_revenue) * 100;
    }

    // Format the final outputs for display
    $total_revenue = number_format($total_revenue, 2);
    $total_expenses_combined = number_format($total_expenses_combined, 2);
    $total_expenses = number_format($total_expenses, 2);
    $total_cost = number_format($total_cost, 2);
    $total_profit = number_format($total_profit, 2);
    $percentage_expenses_to_revenue = number_format($percentage_expenses_to_revenue,);
    $percentage_profit_to_revenue = number_format($percentage_profit_to_revenue,);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $total_profit = "0.00";
    $percentage_expenses_to_revenue = "0.00";
    $percentage_profit_to_revenue = "0.00";
}



$top_products = [];

try {
    $sql = "
    SELECT
        p.id,
        p.name,
        p.image_path,
        IFNULL(SUM(s.sales_qty), 0) AS total_sold
    FROM sales s
    JOIN products p ON s.product_id = p.id
    GROUP BY p.id, p.name, p.image_path
    ORDER BY total_sold DESC
    LIMIT 5
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
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
    // Handle any errors during database queries
    echo "Error: " . $e->getMessage();
}

$connection = null;
?>



