<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Manage Orders';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Orders']
];

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    $order_id = intval($_POST['order_id'] ?? 0);
    
    switch ($action) {
        case 'update_status':
            $order_status = sanitize_input($_POST['order_status'] ?? '');
            $payment_status = sanitize_input($_POST['payment_status'] ?? '');
            
            if ($order_id > 0 && in_array($order_status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
                db_query(
                    "UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?",
                    [$order_status, $payment_status, $order_id],
                    'ssi'
                );
                
                // If cancelled, restore stock
                if ($order_status === 'cancelled') {
                    $items = get_order_items($order_id);
                    foreach ($items as $item) {
                        db_query(
                            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                            [$item['quantity'], $item['product_id']],
                            'ii'
                        );
                    }
                }
                
                // Log admin activity
                log_admin_activity($_SESSION['user_id'], 'update_order_status', 
                    "Updated order #{$order_id} status to {$order_status}");
                
                $_SESSION['success'] = 'Order status updated successfully.';
            }
            break;
            
        case 'delete':
            // Check if order can be deleted (only pending or cancelled)
            $order = db_fetch_single("SELECT order_status FROM orders WHERE id = ?", [$order_id], 'i');
            if ($order && in_array($order['order_status'], ['pending', 'cancelled'])) {
                // Delete order items first
                db_query("DELETE FROM order_items WHERE order_id = ?", [$order_id], 'i');
                // Delete order
                db_query("DELETE FROM orders WHERE id = ?", [$order_id], 'i');
                
                $_SESSION['success'] = 'Order deleted successfully.';
            } else {
                $_SESSION['error'] = 'Only pending or cancelled orders can be deleted.';
            }
            break;
    }
    
    header('Location: ' . ADMIN_URL . '/orders.php');
    exit;
}

// Get orders with pagination and filters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters
$where = "1=1";
$params = [];
$types = '';

// Filter by order status
if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
    $where .= " AND o.order_status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Filter by payment status
if (isset($_GET['payment_status']) && in_array($_GET['payment_status'], ['pending', 'paid', 'failed', 'refunded'])) {
    $where .= " AND o.payment_status = ?";
    $params[] = $_GET['payment_status'];
    $types .= 's';
}

// Filter by date range
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $where .= " AND DATE(o.created_at) >= ?";
    $params[] = $_GET['date_from'];
    $types .= 's';
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $where .= " AND DATE(o.created_at) <= ?";
    $params[] = $_GET['date_to'];
    $types .= 's';
}

// Filter by search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_term = "%{$_GET['search']}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

// Get orders
$sql = "SELECT o.*, u.name as customer_name, u.email as customer_email,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE $where
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$orders = db_fetch_all($sql, $params, $types);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id WHERE $where";
$count_params = array_slice($params, 0, -2);
$count_types = substr($types, 0, -2);
$count_result = db_fetch_single($count_sql, $count_params, $count_types);
$total_orders = $count_result['total'] ?? 0;

// Get order statistics
$stats = db_fetch_single(
    "SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(total_amount) as total_revenue
     FROM orders"
);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Orders</h1>
        <p class="text-gray-600">Total Orders: <?php echo $total_orders; ?> | 
           Total Revenue: <?php echo format_price($stats['total_revenue'] ?? 0); ?></p>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_orders'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Total</div>
        </div>
        <div class="bg-yellow-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_orders'] ?? 0; ?></div>
            <div class="text-sm text-yellow-700">Pending</div>
        </div>
        <div class="bg-blue-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-blue-600"><?php echo $stats['processing_orders'] ?? 0; ?></div>
            <div class="text-sm text-blue-700">Processing</div>
        </div>
        <div class="bg-indigo-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-indigo-600"><?php echo $stats['shipped_orders'] ?? 0; ?></div>
            <div class="text-sm text-indigo-700">Shipped</div>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo $stats['delivered_orders'] ?? 0; ?></div>
            <div class="text-sm text-green-700">Delivered</div>
        </div>
        <div class="bg-red-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-red-600"><?php echo $stats['cancelled_orders'] ?? 0; ?></div>
            <div class="text-sm text-red-700">Cancelled</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>" 
                       placeholder="Order #, Customer Name or Email"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Order Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo (isset($_GET['status']) && $_GET['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <!-- Payment Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                <select name="payment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">All Payments</option>
                    <option value="pending" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="failed" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                    <option value="refunded" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                </select>
            </div>
            
            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Buttons -->
            <div class="md:col-span-5 flex justify-end space-x-2">
                <button type="submit" 
                        class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="<?php echo ADMIN_URL; ?>/orders.php" 
                   class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                    Reset Filters
                </a>
                <button type="button" onclick="exportOrders()" 
                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-download mr-2"></i> Export
                </button>
            </div>
        </form>
    </div>
    
    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <?php if ($orders): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order #
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Items
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="<?php echo ADMIN_URL; ?>/order-view.php?id=<?php echo $order['id']; ?>" 
                                           class="text-amber-600 hover:text-amber-700">
                                            <?php echo $order['order_number']; ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $order['customer_email']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $order['item_count']; ?> items</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo format_price($order['total_amount']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" class="inline" id="statusForm<?php echo $order['id']; ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="order_status" 
                                                onchange="updateOrderStatus(<?php echo $order['id']; ?>)" 
                                                class="text-xs border border-gray-300 rounded px-2 py-1 
                                                       <?php echo get_order_status_class($order['order_status']); ?>">
                                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                          <?php echo get_payment_status_class($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?php echo ADMIN_URL; ?>/order-view.php?id=<?php echo $order['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-700" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <a href="<?php echo SITE_URL; ?>/includes/invoice.php?order_id=<?php echo $order['id']; ?>" 
                                           target="_blank" class="text-green-600 hover:text-green-700" title="Invoice">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                        
                                        <?php if (in_array($order['order_status'], ['pending', 'cancelled'])): ?>
                                            <form method="POST" class="inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this order?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_orders > $limit): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo min($offset + 1, $total_orders); ?> to <?php echo min($offset + $limit, $total_orders); ?> of <?php echo $total_orders; ?> orders
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <?php
                                $prev_url = ADMIN_URL . '/orders.php?page=' . ($page - 1);
                                if (isset($_GET['search'])) $prev_url .= '&search=' . urlencode($_GET['search']);
                                if (isset($_GET['status'])) $prev_url .= '&status=' . $_GET['status'];
                                if (isset($_GET['payment_status'])) $prev_url .= '&payment_status=' . $_GET['payment_status'];
                                if (isset($_GET['date_from'])) $prev_url .= '&date_from=' . $_GET['date_from'];
                                if (isset($_GET['date_to'])) $prev_url .= '&date_to=' . $_GET['date_to'];
                                ?>
                                <a href="<?php echo $prev_url; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < ceil($total_orders / $limit)): ?>
                                <?php
                                $next_url = ADMIN_URL . '/orders.php?page=' . ($page + 1);
                                if (isset($_GET['search'])) $next_url .= '&search=' . urlencode($_GET['search']);
                                if (isset($_GET['status'])) $next_url .= '&status=' . $_GET['status'];
                                if (isset($_GET['payment_status'])) $next_url .= '&payment_status=' . $_GET['payment_status'];
                                if (isset($_GET['date_from'])) $next_url .= '&date_from=' . $_GET['date_from'];
                                if (isset($_GET['date_to'])) $next_url .= '&date_to=' . $_GET['date_to'];
                                ?>
                                <a href="<?php echo $next_url; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="p-12 text-center">
                <i class="fas fa-shopping-bag text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-700 mb-2">No orders found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your filters or check back later.</p>
                <a href="<?php echo ADMIN_URL; ?>/orders.php" 
                   class="inline-block bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                    Reset Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateOrderStatus(orderId) {
    const form = document.getElementById('statusForm' + orderId);
    if (confirm('Update order status?')) {
        form.submit();
    }
}

function exportOrders() {
    // Build export URL with current filters
    let url = '<?php echo ADMIN_URL; ?>/includes/export.php?type=orders';
    
    <?php if (isset($_GET['search'])): ?>
        url += '&search=<?php echo urlencode($_GET['search']); ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['status'])): ?>
        url += '&status=<?php echo $_GET['status']; ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['payment_status'])): ?>
        url += '&payment_status=<?php echo $_GET['payment_status']; ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['date_from'])): ?>
        url += '&date_from=<?php echo $_GET['date_from']; ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['date_to'])): ?>
        url += '&date_to=<?php echo $_GET['date_to']; ?>';
    <?php endif; ?>
    
    window.open(url, '_blank');
}

// Helper functions
function get_order_status_class(status) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'processing': return 'bg-blue-100 text-blue-800';
        case 'shipped': return 'bg-indigo-100 text-indigo-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function get_payment_status_class(status) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'paid': return 'bg-green-100 text-green-800';
        case 'failed': return 'bg-red-100 text-red-800';
        case 'refunded': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>