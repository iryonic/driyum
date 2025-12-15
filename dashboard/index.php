<?php
require_once __DIR__ . '/../includes/auth.php';
require_user();

$page_title = 'Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => SITE_URL . '/dashboard/'],
    ['title' => 'Overview']
];

// Get user info
$user = get_user();
if (!$user) {
    logout_user();
    exit;
}

// Get recent orders
$recent_orders = get_user_orders($_SESSION['user_id'], 5, 0);

// Get wishlist count
$wishlist_count = db_fetch_single(
    "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?",
    [$_SESSION['user_id']],
    'i'
)['count'] ?? 0;

// Get total orders count
$total_orders = db_fetch_single(
    "SELECT COUNT(*) as count FROM orders WHERE user_id = ?",
    [$_SESSION['user_id']],
    'i'
)['count'] ?? 0;

// Get total spent
$total_spent = db_fetch_single(
    "SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND order_status != 'cancelled'",
    [$_SESSION['user_id']],
    'i'
)['total'] ?? 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-amber-600 to-amber-800 rounded-xl p-6 text-white mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p class="text-amber-100">Here's what's happening with your account today.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="<?php echo SITE_URL; ?>/shop.php" 
                   class="bg-white text-amber-700 px-6 py-2 rounded-lg font-semibold hover:bg-amber-50 transition">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-amber-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-shopping-bag text-amber-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_orders; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-wallet text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Spent</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo format_price($total_spent); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-pink-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-heart text-pink-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Wishlist Items</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $wishlist_count; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Orders</h2>
                        <a href="<?php echo SITE_URL; ?>/dashboard/orders.php" 
                           class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                            View All
                        </a>
                    </div>
                </div>
                
                <?php if ($recent_orders): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $order['order_number']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo format_price($order['total_amount']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo get_order_status_class($order['order_status']); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="<?php echo SITE_URL; ?>/dashboard/order-view.php?id=<?php echo $order['id']; ?>" 
                                               class="text-amber-600 hover:text-amber-700">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-6 text-center">
                        <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No orders yet</h3>
                        <p class="text-gray-500 mb-4">Start shopping to see your orders here</p>
                        <a href="<?php echo SITE_URL; ?>/shop.php" 
                           class="inline-block bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700">
                            Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Quick Actions</h2>
                
                <div class="space-y-4">
                    <a href="<?php echo SITE_URL; ?>/dashboard/profile.php" 
                       class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-user-edit text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-800">Update Profile</h3>
                            <p class="text-sm text-gray-500">Edit your personal information</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/dashboard/addresses.php" 
                       class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="bg-green-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-map-marker-alt text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-800">Manage Addresses</h3>
                            <p class="text-sm text-gray-500">Add or edit shipping addresses</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/dashboard/wishlist.php" 
                       class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="bg-pink-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-heart text-pink-600"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-800">View Wishlist</h3>
                            <p class="text-sm text-gray-500">See your saved items</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/dashboard/orders.php" 
                       class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="bg-purple-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-history text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-800">Order History</h3>
                            <p class="text-sm text-gray-500">View all your past orders</p>
                        </div>
                    </a>
                </div>
                
                <!-- Account Status -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="font-medium text-gray-800 mb-3">Account Status</h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Member Since</p>
                            <p class="font-medium"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        <div>
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                                Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function for order status colors
function get_order_status_class($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'processing': return 'bg-blue-100 text-blue-800';
        case 'shipped': return 'bg-indigo-100 text-indigo-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
require_once __DIR__ . '/../includes/footer.php';
?>