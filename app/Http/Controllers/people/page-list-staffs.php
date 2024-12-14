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

    // Retrieve staff from the staff table
    $staff_query = "SELECT staff_id, staff_name, staff_email, staff_phone, position FROM staffs";
    $stmt = $connection->prepare($staff_query);
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $staff_id = $_POST['staff_id'] ?? null;

    try {
        // Ensure $connection is available
        if (!isset($connection)) {
            throw new Exception("Database connection not established.");
        }

        // Handle delete action
        if ($action === 'delete') {
            if (!$staff_id) {
                throw new Exception("Staff ID is required for deletion.");
            }

            $delete_query = "DELETE FROM staffs WHERE staff_id = :staff_id";
            $stmt = $connection->prepare($delete_query);
            $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => 'Staff deleted']);
            } else {
                echo json_encode(['error' => 'Failed to delete staff']);
            }
            exit;
        }

        // Handle update action
        if ($action === 'update') {
            $staff_name = $_POST['staff_name'] ?? null;
            $staff_email = $_POST['staff_email'] ?? null;
            $staff_phone = $_POST['staff_phone'] ?? null;
            $position = $_POST['position'] ?? null;

            if ($staff_id && $staff_name && $staff_email && $staff_phone && $position) {
                $update_query = "UPDATE staffs 
                                 SET staff_name = :staff_name,  
                                     staff_email = :staff_email, 
                                     staff_phone = :staff_phone, 
                                     position = :position
                                 WHERE staff_id = :staff_id";
                $stmt = $connection->prepare($update_query);
                $stmt->bindParam(':staff_name', $staff_name);
                $stmt->bindParam(':staff_email', $staff_email);
                $stmt->bindParam(':staff_phone', $staff_phone);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo json_encode(['success' => 'Staff updated']);
                } else {
                    echo json_encode(['error' => 'Failed to update staff']);
                }
            } else {
                echo json_encode(['error' => 'Incomplete form data']);
            }
            exit;
        }

        // Handle save as PDF action
        if ($action === 'save_pdf') {
            if (!$staff_id) {
                throw new Exception("Staff ID is required for generating PDF.");
            }

            // Fetch staff data
            $query = "SELECT * FROM staffs WHERE staff_id = :staff_id";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
            $stmt->execute();
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($staff) {
                // Generate PDF using FPDF
                require 'fpdf.php';
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(40, 10, 'Staff Details');
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 10, 'Staff Name: ' . $staff['staff_name']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Staff Email: ' . $staff['staff_email']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Staff Phone: ' . $staff['staff_phone']);
                $pdf->Ln();
                $pdf->Cell(40, 10, 'Position: ' . $staff['position']);

                $pdf->Output('D', 'staff_' . $staff_id . '.pdf');
            } else {
                echo json_encode(['error' => 'Staff not found']);
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


