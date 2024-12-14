<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-TXR1WFJ4GP"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-TXR1WFJ4GP');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta content="" name="Boost your business efficiency with SalesPilot – the ultimate sales management app. Track leads, manage clients, and increase revenue effortlessly with our user-friendly platform.">
  <meta content="" name="Sales productivity tools, Sales and Client management, Business efficiency tools">

    <title>Analytics Report</title>
    <link rel="stylesheet" href="records.css">
    <!-- Favicon -->
    <link rel="shortcut icon" href="http://localhost:8000/assets/images/favicon-blue.ico" />
    <link rel="stylesheet" href="http://localhost:8000/assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="http://localhost:8000/assets/css/backend.css?v=1.0.0">
    <link rel="stylesheet" href="http://localhost:8000/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="http://localhost:8000/assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="http://localhost:8000/assets/vendor/remixicon/fonts/remixicon.css"> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.min.js"></script>
</head>
<style>
    body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.dashboard {
    width: 90%;
    margin: auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.control-panel {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

h1 {
    font-size: 24px;
    margin: 0;
}

.print-btn, .time-btn {
    padding: 10px 20px;
    font-size: 16px;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.print-btn {
    background-color: #007bff;
}

.print-btn:hover {
    background-color: #0056b3;
}

.button-group {
    display: flex;
    gap: 10px;
}

.time-btn {
    background-color: #28a745;
}

.time-btn:hover {
    background-color: #218838;
}

h2 {
    font-size: 20px;
    margin-top: 20px;
    margin-bottom: 10px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.data-table th, .data-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

.data-table th {
    background-color: #007bff;
    color: white;
    font-weight: bold;
}

.data-table tr:nth-child(even) {
    background-color: #f2f2f2;
}

.data-table tr:hover {
    background-color: #ddd;
}


</style>
<body>
    <!-- loader Start -->
<div id="loading">
    <div id="loading-center">
    </div>
</div>
<!-- loader END -->
<!-- Wrapper Start -->
<div class="wrapper">
    
    <div class="iq-sidebar  sidebar-default ">
        <div class="iq-sidebar-logo d-flex align-items-center justify-content-between">
            <a href="http://localhost:8000/dashboard.php" class="header-logo">
                <img src="http://localhost:8000/logonew1.jpg" class="img-fluid rounded-normal light-logo" alt="logo"><h5 class="logo-title light-logo ml-3">SalesPilot</h5>
            </a>
            <div class="iq-menu-bt-sidebar ml-0">
                <i class="las la-bars wrapper-menu"></i>
            </div>
        </div>
        <div class="data-scrollbar" data-scroll="1">
            <nav class="iq-sidebar-menu">
                <ul id="iq-sidebar-toggle" class="iq-menu">
                    <li class="">
                        <a href="http://localhost:8000/dashboard.php" class="svg-icon">                        
                            <svg  class="svg-icon" id="p-dash1" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                            <span class="ml-4">Dashboards</span>
                        </a>
                    </li>
                    <li class=" ">
                        <a href="#product" class="collapsed" data-toggle="collapse" aria-expanded="false">
                            <svg class="svg-icon" id="p-dash2" width="20" height="20"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="ml-4">Products</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="product" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                            <li class="">
                                <a href="http://localhost:8000/page-list-product.php">
                                    <i class="las la-minus"></i><span>List Product</span>
                                </a>
                            </li>
                            <li class="">
                                <a href="http://localhost:8000/page-add-product.php">
                                    <i class="las la-minus"></i><span>Add Product</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class=" ">
                        <a href="#category" class="collapsed" data-toggle="collapse" aria-expanded="false">
                            <svg class="svg-icon" id="p-dash3" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <span class="ml-4">Categories</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="category" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                <li class="">
                                        <a href="http://localhost:8000/page-list-category.php">
                                            <i class="las la-minus"></i><span>List Category</span>
                                        </a>
                                </li>
                             
                        </ul>
                    </li>
                    <li class=" ">
                        <a href="#sale" class="collapsed" data-toggle="collapse" aria-expanded="false">
                            <svg class="svg-icon" id="p-dash4" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                            </svg>
                            <span class="ml-4">Sale</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="sale" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                <li class="">
                                        <a href="http://localhost:8000/page-list-sale.php">
                                            <i class="las la-minus"></i><span>List Sale</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/page-add-sale.php">
                                            <i class="las la-minus"></i><span>Add Sale</span>
                                        </a>
                                </li>
                        </ul>
                    </li>
                    <li class=" ">
                        <a href="#purchase" class="collapsed" data-toggle="collapse" aria-expanded="false">
                            <svg class="svg-icon" id="p-dash5" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <span class="ml-4">Expenses</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="purchase" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                <li class="">
                                        <a href="http://localhost:8000/backend/page-list-expense.php">
                                            <i class="las la-minus"></i><span>List Expenses</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/backend/page-add-expense.php">
                                            <i class="las la-minus"></i><span>Add Expenses</span>
                                        </a>
                                </li>
                        </ul>
                    </li>
                    <li class=" ">
                        <a href="#return" class="collapsed" data-toggle="collapse" aria-expanded="false">
                            <svg class="svg-icon" id="p-dash6" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line>
                            </svg>
                            <span class="ml-4">Inventory</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="return" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                <li class="">
                                        <a href="http://localhost:8000/page-list-inventory.php">
                                            <i class="las la-minus"></i><span>List Inventory</span>
                                        </a>
                                </li>
                               
                        </ul>
                    </li>
                    <li class=" ">
                        <a href="#people" class="collapsed" data-toggle="collapse" aria-expanded="false">
                            <svg class="svg-icon" id="p-dash8" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span class="ml-4">People</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="people" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                <li class="">
                                        <a href="http://localhost:8000/page-list-customers.php">
                                            <i class="las la-minus"></i><span>Customers</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/page-add-customers.php">
                                            <i class="las la-minus"></i><span>Add Customers</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/page-list-staffs.php">
                                            <i class="las la-minus"></i><span>Staffs</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/page-add-staffs.php">
                                            <i class="las la-minus"></i><span>Add Staffs</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/page-list-suppliers.php">
                                            <i class="las la-minus"></i><span>Suppliers</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/page-add-supplier.php">
                                            <i class="las la-minus"></i><span>Add Suppliers</span>
                                        </a>
                                </li>
                        </ul>
                    </li>
                    <li class=" ">
                        <a href="#otherpage" class="collapsed" data-toggle="collapse" aria-expanded="false">
                              <svg class="svg-icon" id="p-dash9" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="9"></rect><rect x="14" y="7" width="3" height="5"></rect>
                            </svg>
                            <span class="ml-4">Analytics</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="otherpage" class="iq-submenu collapse" data-parent="#iq-sidebar-toggle">
                                <li class="">
                                        <a href="http://localhost:8000/analytics.php">
                                            <i class="las la-minus"></i><span>Charts</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/analytics-report.php">
                                            <i class="las la-minus"></i><span>Reports</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/sales-metrics.php">
                                            <i class="las la-minus"></i><span>Category Metrics</span>
                                        </a>
                                </li>
                                <li class="">
                                        <a href="http://localhost:8000/inventory-metrics.php">
                                            <i class="las la-minus"></i><span>Product Metrics</span>
                                        </a>
                                </li>
                                
                        </ul>
                    </li>   
                    </li>
                </ul>
            </nav>
            <div id="sidebar-bottom" class="position-relative sidebar-bottom">
                <div class="card border-none">
                    <div class="card-body p-0">
                        
                    </div>
                </div>
            </div>
            <div class="p-3"></div>
        </div>
        </div>      <div class="iq-top-navbar">
        <div class="iq-navbar-custom">
            <nav class="navbar navbar-expand-lg navbar-light p-0">
                <div class="iq-navbar-logo d-flex align-items-center justify-content-between">
                    <i class="ri-menu-line wrapper-menu"></i>
                    <a href="http://localhost:8000/dashboard.php" class="header-logo">
                        <img src="http://localhost:8000/logonew1.jpg" class="img-fluid rounded-normal" alt="logo">
                        <h5 class="logo-title ml-3">SalesPilot</h5>
    
                    </a>
                </div>
                <div class="iq-search-bar device-search">
                    <form action="#" class="searchbox">
                        <a class="search-link" href="#"><i class="ri-search-line"></i></a>
                        <input type="text" class="text search-input" placeholder="Search here...">
                    </form>
                </div>
                <div class="d-flex align-items-center">
                    <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-label="Toggle navigation">
                        <i class="ri-menu-3-line"></i>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ml-auto navbar-list align-items-center">
                            
                            <li>
                                <a href="#" class="btn border add-btn shadow-none mx-2 d-none d-md-block"
                                    data-toggle="modal" data-target="#new-order"><i class="las la-plus mr-2"></i>New
                                    Invoice</a>
                            </li>
                            <li class="nav-item nav-icon search-content">
                                <a href="#" class="search-toggle rounded" id="dropdownSearch" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="ri-search-line"></i>
                                </a>
                                <div class="iq-search-bar iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownSearch">
                                    <form action="#" class="searchbox p-2">
                                        <div class="form-group mb-0 position-relative">
                                            <input type="text" class="text search-input font-size-12"
                                                placeholder="type here to search...">
                                            <a href="#" class="search-link"><i class="las la-search"></i></a>
                                        </div>
                                    </form>
                                </div>
                            </li>
                            <li class="nav-item nav-icon dropdown">
    <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton"
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" class="feather feather-bell">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <span class="bg-primary "></span>
    </a>
    <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton">
        <div class="card shadow-none m-0">
            <div class="card-body p-0">
                <div class="cust-title p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Notifications</h5>
                        <a class="badge badge-primary badge-card" href="#">
                            <?= count($inventoryNotifications) + count($reportsNotifications) ?>
                        </a>
                    </div>
                </div>
                <div class="px-3 pt-0 pb-0 sub-card">

                    <?php if (!empty($inventoryNotifications)): ?>
                        <?php foreach ($inventoryNotifications as $notification): ?>
                            <a href="#" class="iq-sub-card">
                                <div class="media align-items-center cust-card py-3 border-bottom">
                                    <div>
                                        <img class="avatar-50 rounded-small"
                                            src="<?= htmlspecialchars($notification['image_path']); ?>" 
                                            alt="<?= htmlspecialchars($notification['product_name']); ?>">
                                    </div>
                                    <div class="media-body ml-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="mb-0"><?= htmlspecialchars($notification['product_name']); ?></h6>
                                            <small class="text-dark">
                                                <b>Available: <?= htmlspecialchars($notification['available_stock']); ?></b>
                                            </small>
                                        </div>
                                        <small>Inventory: <?= htmlspecialchars($notification['inventory_qty']); ?>, 
                                        Sales: <?= htmlspecialchars($notification['sales_qty']); ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">No inventory notifications available.</p>
                    <?php endif; ?>

                    <?php if (!empty($reportsNotifications)): ?>
                        <?php foreach ($reportsNotifications as $notification): ?>
                            <a href="#" class="iq-sub-card">
                                <div class="media align-items-center cust-card py-3 border-bottom">
                                    <div>
                                        <img class="avatar-50 rounded-small"
                                            src="<?= htmlspecialchars($notification['image_path']); ?>" 
                                            alt="<?= htmlspecialchars($notification['product_name']); ?>">
                                    </div>
                                    <div class="media-body ml-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="mb-0"><?= htmlspecialchars($notification['product_name']); ?></h6>
                                            <small class="text-dark">
                                                <b>Revenue: <?= htmlspecialchars($notification['revenue']); ?></b>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">No reports notifications available.</p>
                    <?php endif; ?>
                </div>
                <a class="right-ic btn btn-primary btn-block position-relative p-2" href="page-list-inventory.php" role="button">
                    View All
                </a>
            </div>
        </div>
    </div>
</li>

                            <li class="nav-item nav-icon dropdown caption-content">
                                <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton4"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="http://localhost:8000/<?php echo htmlspecialchars($image_to_display); ?>" 
                                        alt="profile-img" class="rounded profile-img img-fluid avatar-70">
                                </a>
                                <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <div class="card shadow-none m-0">
                                        <div class="card-body p-0 text-center">
                                            <div class="media-body profile-detail text-center">
                                                <img src="http://localhost:8000/assets/images/page-img/profile-bg.jpg" alt="profile-bg"
                                                    class="rounded-top img-fluid mb-4">
                                                    <img src="http://localhost:8000/<?php echo htmlspecialchars($image_to_display); ?>" 
                                                      alt="profile-img" class="rounded profile-img img-fluid avatar-70">


                                            </div>
                                            <div class="p-3">
                                              <h5 class="mb-1"><?php echo $email; ?></h5>
                                              <p class="mb-0">Since <?php echo $date; ?></p>
                                                <div class="d-flex align-items-center justify-content-center mt-3">
                                                    <a href="http://localhost:8000/user-profile-edit.php" class="btn border mr-2">Profile</a>
                                                    <a href="logout.php" class="btn border">Sign Out</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <div class="modal fade" id="new-order" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="popup text-left">
                    <h4 class="mb-3">New Invoice</h4>
                    <div class="content create-workform bg-body">
                        <div class="pb-3">
                            <label class="mb-2">Name</label>
                            <input type="text" class="form-control" id="customerName" placeholder="Enter Customer Name">
                        </div>
                        <div class="col-lg-12 mt-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-center">
                                <div class="btn btn-primary mr-4" data-dismiss="modal">Cancel</div>
                                <div class="btn btn-outline-primary" id="createButton">Create</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>      <div class="content-page">

<div class="dashboard" id="dashboard">
    <div class="control-panel">
        <h1 style="font-weight: bold; text-decoration: underline; ">Report</h1> 
    </div>
    <a href="analytics.php" class="btn btn-primary add-list"><i class="las la-plus mr-3"></i>Analytics</a>
    <a href="pdf_generate.php" class="btn btn-primary add-list"><i class="las la-plus mr-3"></i>PDF</a>
    <h2 class="bg-light" style="text-decoration: underline;">Product Sales</h2>
    <div class="table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info">
        <thead>
            <tr class="bg-light">
                <th>Product Name</th>
                <th>Product Sold</th>
            </tr>
        </thead>
        <tbody id="barTableBody">
            <?php foreach ($productMetrics as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['total_sales']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="bg-light" style="text-decoration: underline;">Top Products </h2>
    <div class="table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info">
        <thead>
            <tr class="bg-light">
                <th>Product Name</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody id="pieTableBody">
            <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td>$<?php echo number_format (htmlspecialchars($product['revenue']), 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="bg-light" style="text-decoration: underline;">Inventory </h2>
    <div class="table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info">
        <thead>
            <tr class="bg-light">
                <th>Product Name</th>
                <th>Available Stock</th>
                <th>Inventory Quantity</th>
                <th>Sales Quantity</th>
            </tr>
        </thead>
        <tbody id="candleTableBody">
            <?php foreach ($inventoryMetrics as $inventory): ?>
                <tr>
                    <td><?php echo htmlspecialchars($inventory['name']); ?></td>
                    <td><?php echo htmlspecialchars($inventory['available_stock']); ?></td>
                    <td><?php echo htmlspecialchars($inventory['inventory_qty']); ?></td>
                    <td><?php echo htmlspecialchars($inventory['sales_qty']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="bg-light" style="text-decoration: underline;">Expenditure</h2>
    <div class="table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info">
        <thead>
            <tr class="bg-light">
                <th>Date</th>
                <th>Revenue</th>
                <th>Total Expenses</th>
                <th>Profit</th>
            </tr>
        </thead>
        <tbody id="areaTableBody">
            <?php foreach ($incomeOverview as $income): ?>
                <tr>
                    <td><?php echo htmlspecialchars($income['date']); ?></td>
                    <td>$<?php echo htmlspecialchars($income['revenue']); ?></td>
                    <td>$<?php echo htmlspecialchars($income['total_expenses']); ?></td>
                    <td>$<?php echo  htmlspecialchars($income['profit']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

 
</div>

</div>
</div>
 <!-- Page end  -->
</div>
</div>
</div>
<!-- Wrapper End-->
<footer class="iq-footer">
  <div class="container-fluid">
  <div class="card">
      <div class="card-body">
          <div class="row">
              <div class="col-lg-6">
                  <ul class="list-inline mb-0">
                      <li class="list-inline-item"><a href="http://localhost:8000/privacy-policy.php">Privacy Policy</a></li>
                      <li class="list-inline-item"><a href="http://localhost:8000/terms-of-service.php">Terms of Use</a></li>
                      <li class="list-inline-item"><a href="http://localhost:8000/subscription.php">Subscriptions</a></li>
                      <li class="list-inline-item"><a href="http://localhost:8000/pay.php">Pay Now</a></li>
                      <li class="list-inline-item"><a href="http://localhost:8000/help.html">Help</a></li>
                  </ul>
              </div>
              <div class="col-lg-6 text-right">
                  <span class="mr-1"><script>document.write(new Date().getFullYear())</script>©</span> <a href="http://localhost:8000/dashboard.php" class="">SalesPilot</a>.
              </div>
          </div>
      </div>
  </div>
</div>
</footer>
<!-- Backend Bundle JavaScript -->
<script src="http://localhost:8000/assets/js/backend-bundle.min.js"></script>

<!-- Table Treeview JavaScript -->
<script src="http://localhost:8000/assets/js/table-treeview.js"></script>

<!-- app JavaScript -->
<script src="http://localhost:8000/assets/js/app.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    // Function to fetch data for the dashboard based on the selected range
    function fetchData(range) {
        // Fetch data dynamically based on the range (weekly, monthly, yearly)
        const xhr = new XMLHttpRequest();
        xhr.open("GET", `fetch-data.php?range=${range}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                
                // Populate Product Metrics
                const barTableBody = document.getElementById('barTableBody');
                barTableBody.innerHTML = '';
                data.productMetrics.forEach(product => {
                    const row = `<tr>
                        <td>${product.product_name}</td>
                        <td>$${parseFloat(product.total_sales).toFixed(2)}</td>
                    </tr>`;
                    barTableBody.innerHTML += row;
                });

                // Populate Top 5 Products by Revenue
                const pieTableBody = document.getElementById('pieTableBody');
                pieTableBody.innerHTML = '';
                data.topProducts.forEach(product => {
                    const row = `<tr>
                        <td>${product.name}</td>
                        <td>$${parseFloat(product.revenue).toFixed(2)}</td>
                    </tr>`;
                    pieTableBody.innerHTML += row;
                });

                // Populate Inventory Metrics
                const candleTableBody = document.getElementById('candleTableBody');
                candleTableBody.innerHTML = '';
                data.inventoryMetrics.forEach(inventory => {
                    const row = `<tr>
                        <td>${inventory.name}</td>
                        <td>${inventory.available_stock}</td>
                        <td>${inventory.inventory_qty}</td>
                    </tr>`;
                    candleTableBody.innerHTML += row;
                });

                // Populate Income Overview
                const areaTableBody = document.getElementById('areaTableBody');
                areaTableBody.innerHTML = '';
                data.incomeOverview.forEach(income => {
                    const row = `<tr>
                        <td>${income.date}</td>
                        <td>$${parseFloat(income.revenue).toFixed(2)}</td>
                        <td>$${parseFloat(income.total_expenses).toFixed(2)}</td>
                        <td>$${parseFloat(income.profit).toFixed(2)}</td>
                    </tr>`;
                    areaTableBody.innerHTML += row;
                });
            } else {
                console.error("Failed to fetch data.");
            }
        };
        xhr.send();
    }

    // Add event listeners to the buttons
    document.querySelectorAll('.time-btn').forEach(button => {
        button.addEventListener('click', function() {
            fetchData(this.innerText.toLowerCase());
        });
    });

    // Initial data load (default to 'yearly')
    fetchData('yearly');
    
    // Function to print the dashboard as PDF
    window.printPDF = function() {
        window.print();  // This is a basic implementation, you can customize it further if needed
    };
});

</script>


<script>
document.getElementById('createButton').addEventListener('click', function() {
    // Optional: Validate input or perform any additional checks here
    
    // Redirect to invoice-form.php
    window.location.href = 'invoice-form.php';
});
</script>
</body>
</html>