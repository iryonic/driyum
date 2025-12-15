<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

// Get admin notifications (guarded if notifications table doesn't exist)
$notifications = [];
try {
    $table_exists = db_fetch_single("SHOW TABLES LIKE 'notifications'");
    if ($table_exists) {
        $notifications = db_fetch_all(
            "SELECT * FROM notifications 
             WHERE (user_id = ? OR user_id IS NULL) 
             AND is_read = 0 
             ORDER BY created_at DESC 
             LIMIT 10",
            [$_SESSION['user_id']],
            'i'
        );
    }
} catch (\mysqli_sql_exception $e) {
    // If the table doesn't exist or query fails, proceed without notifications
    $notifications = [];
}

// Get pending orders count
$admin_pending_orders = db_fetch_single(
    "SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'"
)['count'] ?? 0;

// Get low stock count
$admin_low_stock = db_fetch_single(
    "SELECT COUNT(*) as count FROM products 
     WHERE stock_quantity <= low_stock_threshold 
     AND status = 'active'"
)['count'] ?? 0;

// Get today's orders
$admin_today_orders = db_fetch_single(
    "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()"
)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Panel - <?php echo SITE_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/css/admin.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    
    <style>
        .adnav{
            scrollbar-width: thin;
            scrollbar-color: #f59e0b #1f2937;
        }
        .sidebar-link:hover .sidebar-icon {
            transform: scale(1.1);
        }
        
        .sidebar-link.active {
            background-color: rgba(245, 158, 11, 0.1);
            border-left-color: #f59e0b;
        }
        
        .notification-dot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <span><?php echo $_SESSION['success']; ?></span>
                <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <span><?php echo $_SESSION['error']; ?></span>
                <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
    
    <!-- Admin Layout -->
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside id="adminSidebar" class="w-64 bg-gray-900 text-white flex flex-col fixed lg:static inset-y-0 left-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-800">
                <div class="flex items-center">
                    <img src="<?php echo SITE_URL; ?>/assets/images/driyumlogo.jpg" 
                         alt="<?php echo SITE_NAME; ?>" 
                         class="h-10 w-10 rounded-lg mr-3">
                    <div>
                        <h1 class="text-xl font-bold"><?php echo SITE_NAME; ?></h1>
                        <p class="text-gray-400 text-sm">Admin Panel</p>
                    </div>
                </div>
            </div>
            
            <!-- User Info -->
            <div class="p-4 border-b border-gray-800">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-amber-600 flex items-center justify-center mr-3">
                        <span class="font-bold"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></span>
                    </div>
                    <div>
                        <p class="font-medium"><?php echo $_SESSION['user_name']; ?></p>
                        <p class="text-gray-400 text-sm">Administrator</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="p-4 border-b border-gray-800">
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-gray-800 rounded-lg p-3 text-center">
                        <div class="text-amber-400 text-lg font-bold"><?php echo $admin_pending_orders; ?></div>
                        <div class="text-gray-400 text-xs">Pending</div>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-3 text-center">
                        <div class="text-red-400 text-lg font-bold"><?php echo $admin_low_stock; ?></div>
                        <div class="text-gray-400 text-xs">Low Stock</div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 adnav">
                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo ADMIN_URL; ?>/dashboard.php" 
                           class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active border-l-4 border-amber-500' : ''; ?>">
                            <i class="fas fa-tachometer-alt sidebar-icon text-gray-400 mr-3 transition-transform"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Products -->
                    <li>
                        <div class="px-4 py-2 text-gray-400 text-sm font-medium uppercase tracking-wider">
                            <i class="fas fa-box mr-2"></i> Products
                        </div>
                        <ul class="ml-4 space-y-1">
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/products.php" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'add-product.php', 'edit-product.php']) ? 'active border-l-4 border-amber-500' : ''; ?>">
                                    <i class="fas fa-box-open text-gray-400 mr-3 text-sm"></i>
                                    <span>All Products</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/add-product.php" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition <?php echo basename($_SERVER['PHP_SELF']) == 'add-product.php' ? 'active border-l-4 border-amber-500' : ''; ?>">
                                    <i class="fas fa-plus-circle text-gray-400 mr-3 text-sm"></i>
                                    <span>Add New</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/categories.php" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active border-l-4 border-amber-500' : ''; ?>">
                                    <i class="fas fa-tags text-gray-400 mr-3 text-sm"></i>
                                    <span>Categories</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Orders -->
                    <li>
                        <div class="px-4 py-2 text-gray-400 text-sm font-medium uppercase tracking-wider">
                            <i class="fas fa-shopping-bag mr-2"></i> Orders
                        </div>
                        <ul class="ml-4 space-y-1">
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/orders.php" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition <?php echo in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order-view.php']) ? 'active border-l-4 border-amber-500' : ''; ?>">
                                    <i class="fas fa-list text-gray-400 mr-3 text-sm"></i>
                                    <span>All Orders</span>
                                    <?php if (($stats['pending_orders'] ?? 0) > 0): ?>
                                        <span class="ml-auto bg-amber-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                            <?php echo $stats['pending_orders']; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/orders.php?status=pending" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition">
                                    <i class="fas fa-clock text-gray-400 mr-3 text-sm"></i>
                                    <span>Pending</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/orders.php?status=processing" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition">
                                    <i class="fas fa-sync-alt text-gray-400 mr-3 text-sm"></i>
                                    <span>Processing</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Customers -->
                    <li>
                        <div class="px-4 py-2 text-gray-400 text-sm font-medium uppercase tracking-wider">
                            <i class="fas fa-users mr-2"></i> Customers
                        </div>
                        <ul class="ml-4 space-y-1">
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/users.php" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active border-l-4 border-amber-500' : ''; ?>">
                                    <i class="fas fa-user-friends text-gray-400 mr-3 text-sm"></i>
                                    <span>All Users</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Marketing -->
                    <li>
                        <div class="px-4 py-2 text-gray-400 text-sm font-medium uppercase tracking-wider">
                            <i class="fas fa-bullhorn mr-2"></i> Marketing
                        </div>
                        <ul class="ml-4 space-y-1">
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/coupons.php" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition <?php echo basename($_SERVER['PHP_SELF']) == 'coupons.php' ? 'active border-l-4 border-amber-500' : ''; ?>">
                                    <i class="fas fa-tag text-gray-400 mr-3 text-sm"></i>
                                    <span>Coupons</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Settings -->
                    <li>
                        <div class="px-4 py-2 text-gray-400 text-sm font-medium uppercase tracking-wider">
                            <i class="fas fa-cog mr-2"></i> Settings
                        </div>
                        <ul class="ml-4 space-y-1">
                            <li>
                                <a href="<?php echo ADMIN_URL; ?>/settings.php" 
                                   class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 transition <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active border-l-4 border-amber-500' : ''; ?>">
                                    <i class="fas fa-store text-gray-400 mr-3 text-sm"></i>
                                    <span>Store Settings</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Reports -->
                    <li>
                        <a href="<?php echo ADMIN_URL; ?>/reports.php" 
                           class="sidebar-link flex items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active border-l-4 border-amber-500' : ''; ?>">
                            <i class="fas fa-chart-bar sidebar-icon text-gray-400 mr-3 transition-transform"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Footer -->
            <div class="p-4 border-t border-gray-800">
                <a href="<?php echo SITE_URL; ?>" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition mb-2">
                    <i class="fas fa-store text-gray-400 mr-3"></i>
                    <span>View Store</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/logout.php" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-800 transition text-red-400 hover:text-red-300">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b border-gray-200">
                <div class="px-6 py-4 flex items-center justify-between">
                    <!-- Left: Sidebar Toggle & Breadcrumbs -->
                    <div class="flex items-center">
                        <button id="sidebarToggle" class="lg:hidden mr-4 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <!-- Breadcrumbs -->
                        <?php if (isset($breadcrumbs)): ?>
                            <nav class="flex" aria-label="Breadcrumb">
                                <ol class="flex items-center space-x-2 text-sm">
                                    <li>
                                        <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="text-gray-500 hover:text-amber-600">
                                            <i class="fas fa-home"></i>
                                        </a>
                                    </li>
                                    <?php foreach ($breadcrumbs as $crumb): ?>
                                        <li class="flex items-center">
                                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                            <?php if (isset($crumb['url'])): ?>
                                                <a href="<?php echo $crumb['url']; ?>" class="text-gray-500 hover:text-amber-600">
                                                    <?php echo $crumb['title']; ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-amber-600 font-medium"><?php echo $crumb['title']; ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right: Notifications & Search -->
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative hidden md:block">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent w-64" 
                                   placeholder="Search...">
                        </div>
                        
                        <!-- Notifications -->
                        <div class="relative">
                            <button id="notificationButton" 
                                    class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <?php if (count($notifications) > 0): ?>
                                    <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full notification-dot"></span>
                                <?php endif; ?>
                            </button>
                            
                            <!-- Notification Dropdown -->
                            <div id="notificationDropdown" 
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2 z-50 hidden">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <h3 class="font-medium text-gray-800">Notifications</h3>
                                    <span class="text-xs text-gray-500"><?php echo count($notifications); ?> unread</span>
                                </div>
                                
                                <div class="max-h-96 overflow-y-auto">
                                    <?php if (count($notifications) > 0): ?>
                                        <?php foreach ($notifications as $notification): ?>
                                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                                <div class="flex items-start">
                                                    <div class="flex-shrink-0 mt-1">
                                                        <?php if ($notification['type'] === 'order'): ?>
                                                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                                <i class="fas fa-shopping-bag text-blue-600 text-sm"></i>
                                                            </div>
                                                        <?php elseif ($notification['type'] === 'stock'): ?>
                                                            <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                                                                <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                                                                <i class="fas fa-info-circle text-gray-600 text-sm"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ml-3 flex-1">
                                                        <p class="text-sm text-gray-800"><?php echo $notification['message']; ?></p>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            <?php echo time_ago($notification['created_at']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="px-4 py-6 text-center">
                                            <i class="fas fa-bell-slash text-3xl text-gray-300 mb-2"></i>
                                            <p class="text-gray-500 text-sm">No new notifications</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="px-4 py-3 border-t border-gray-200">
                                    <a href="#" class="block text-center text-amber-600 hover:text-amber-700 text-sm font-medium">
                                        View All Notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="relative">
                            <button id="userMenuButton" 
                                    class="flex items-center space-x-3 focus:outline-none">
                                <div class="h-8 w-8 rounded-full bg-amber-600 flex items-center justify-center">
                                    <span class="text-white font-bold"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></span>
                                </div>
                                <div class="hidden md:block text-left">
                                    <p class="text-sm font-medium text-gray-800"><?php echo $_SESSION['user_name']; ?></p>
                                    <p class="text-xs text-gray-500">Administrator</p>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </button>
                            
                            <!-- User Dropdown -->
                            <div id="userDropdown" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden">
                                <a href="<?php echo SITE_URL; ?>/dashboard/profile.php" 
                                   class="block px-4 py-2 text-gray-800 hover:bg-gray-50 hover:text-amber-700">
                                    <i class="fas fa-user-circle mr-2"></i> My Profile
                                </a>
                                <a href="<?php echo ADMIN_URL; ?>/settings.php" 
                                   class="block px-4 py-2 text-gray-800 hover:bg-gray-50 hover:text-amber-700">
                                    <i class="fas fa-cog mr-2"></i> Settings
                                </a>
                                <div class="border-t my-1"></div>
                                <a href="<?php echo SITE_URL; ?>" 
                                   class="block px-4 py-2 text-gray-800 hover:bg-gray-50 hover:text-amber-700">
                                    <i class="fas fa-store mr-2"></i> View Store
                                </a>
                                <div class="border-t my-1"></div>
                                <a href="<?php echo SITE_URL; ?>/logout.php" 
                                   class="block px-4 py-2 text-gray-800 hover:bg-gray-50 hover:text-red-600">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats Bar -->
                <div class="bg-gray-50 border-t border-gray-200 px-6 py-3">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                <i class="fas fa-shopping-bag text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Today's Orders</p>
                                <p class="font-medium"><?php echo $admin_today_orders; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                <i class="fas fa-rupee-sign text-green-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Today's Revenue</p>
                                <p class="font-medium">₹<?php 
                                    $today_revenue = db_fetch_single(
                                        "SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'"
                                    )['revenue'] ?? 0;
                                    echo number_format($today_revenue, 2);
                                ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center mr-3">
                                <i class="fas fa-users text-amber-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">New Customers</p>
                                <p class="font-medium"><?php 
                                    $new_customers = db_fetch_single(
                                        "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE() AND role = 'user'"
                                    )['count'] ?? 0;
                                    echo $new_customers;
                                ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <?php if (isset($page_title)): ?>
                        <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $page_title; ?></h1>
                    <?php endif; ?>
                    <?php if (isset($page_description)): ?>
                        <p class="text-gray-600"><?php echo $page_description; ?></p>
                    <?php endif; ?>
                </div>