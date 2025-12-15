<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Admin Dashboard';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Dashboard']
];

// Get statistics
$total_orders = db_fetch_single("SELECT COUNT(*) as count FROM orders")['count'] ?? 0;
$total_users = db_fetch_single("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'] ?? 0;
$total_products = db_fetch_single("SELECT COUNT(*) as count FROM products")['count'] ?? 0;
$total_categories = db_fetch_single("SELECT COUNT(*) as count FROM categories")['count'] ?? 0;

// Get revenue (last 30 days)
$revenue = db_fetch_single(
    "SELECT SUM(total_amount) as revenue FROM orders 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
     AND order_status != 'cancelled'"
)['revenue'] ?? 0;

// Get recent orders
$recent_orders = db_fetch_all(
    "SELECT o.*, u.name as customer_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     ORDER BY o.created_at DESC LIMIT 5"
);

// Get low stock products
$low_stock = db_fetch_all(
    "SELECT * FROM products 
     WHERE stock_quantity <= low_stock_threshold 
     AND status = 'active' 
     ORDER BY stock_quantity ASC LIMIT 5"
);

// Get recent users
$recent_users = db_fetch_all(
    "SELECT * FROM users 
     WHERE role = 'user' 
     ORDER BY created_at DESC LIMIT 5"
);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Dashboard</h1>
        <p class="text-gray-600">Welcome back, <?php echo $_SESSION['user_name']; ?>! Here's your store overview.</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo format_price($revenue); ?></h3>
                    <p class="text-xs text-green-600 mt-1">Last 30 days</p>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-wallet text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_orders; ?></h3>
                    <p class="text-xs text-blue-600 mt-1">All time</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></h3>
                    <p class="text-xs text-purple-600 mt-1">Registered users</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Products</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_products; ?></h3>
                    <p class="text-xs text-amber-600 mt-1">Active products</p>
                </div>
                <div class="bg-amber-100 p-3 rounded-lg">
                    <i class="fas fa-box-open text-amber-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts and Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Recent Orders</h2>
                    <a href="<?php echo ADMIN_URL; ?>/orders.php" 
                       class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                        View All
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <a href="<?php echo ADMIN_URL; ?>/order-view.php?id=<?php echo $order['id']; ?>" 
                                       class="text-amber-600 hover:text-amber-700 font-medium">
                                        <?php echo $order['order_number']; ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="px-6 py-4"><?php echo format_price($order['total_amount']); ?></td>
                                <td class="px-6 py-4">
                                    <?php echo get_order_status_badge($order['order_status']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Low Stock Products -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Low Stock Products</h2>
                    <a href="<?php echo ADMIN_URL; ?>/products.php" 
                       class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                        View All
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($low_stock as $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <img src="<?php echo $product['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="h-10 w-10 object-cover rounded">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 truncate max-w-xs">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $product['sku']; ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                          <?php echo $product['stock_quantity'] == 0 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $product['stock_quantity']; ?> left
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?php echo ADMIN_URL; ?>/edit-product.php?id=<?php echo $product['id']; ?>" 
                                       class="text-amber-600 hover:text-amber-700 text-sm">
                                        Restock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Recent Users</h2>
                <a href="<?php echo ADMIN_URL; ?>/users.php" 
                   class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                    View All
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($recent_users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo $user['email']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo get_user_status_badge($user['status']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Pending Orders</p>
                    <h3 class="text-2xl font-bold">
                        <?php echo db_fetch_single("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")['count'] ?? 0; ?>
                    </h3>
                </div>
                <i class="fas fa-clock text-3xl opacity-50"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Today's Orders</p>
                    <h3 class="text-2xl font-bold">
                        <?php echo db_fetch_single("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")['count'] ?? 0; ?>
                    </h3>
                </div>
                <i class="fas fa-calendar-day text-3xl opacity-50"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Out of Stock</p>
                    <h3 class="text-2xl font-bold">
                        <?php echo db_fetch_single("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0 AND status = 'active'")['count'] ?? 0; ?>
                    </h3>
                </div>
                <i class="fas fa-exclamation-triangle text-3xl opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions
function get_order_status_badge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'shipped' => 'bg-indigo-100 text-indigo-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . ucfirst($status) . '</span>';
}

function get_user_status_badge($status) {
    $badges = [
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-yellow-100 text-yellow-800',
        'suspended' => 'bg-red-100 text-red-800'
    ];
    
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . ucfirst($status) . '</span>';
}

require_once __DIR__ . '/../includes/admin-footer.php';
?>