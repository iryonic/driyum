<?php
require_once __DIR__ . '/../includes/auth.php';
require_user();

$page_title = 'My Orders';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => SITE_URL . '/dashboard/'],
    ['title' => 'My Orders']
];

// Get orders with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$orders = get_user_orders($_SESSION['user_id'], $limit, $offset);

// Get total orders count
$total_orders = db_fetch_single(
    "SELECT COUNT(*) as count FROM orders WHERE user_id = ?",
    [$_SESSION['user_id']],
    'i'
)['count'] ?? 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Orders</h1>
        <p class="text-gray-600">View and manage all your orders</p>
    </div>
    
    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <?php if ($orders): ?>
            <div class="overflow-x-auto table-responsive">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order #
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
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <?php
                            // Get order items count
                            $item_count = $order['item_count'] ?? 0;
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $order['order_number']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $item_count; ?> item(s)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo format_price($order['total_amount']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo get_order_status_badge($order['order_status']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?php echo SITE_URL; ?>/dashboard/order-view.php?id=<?php echo $order['id']; ?>" 
                                       class="text-amber-600 hover:text-amber-700 mr-4">
                                        View
                                    </a>
                                    <?php if ($order['order_status'] === 'pending' || $order['order_status'] === 'processing'): ?>
                                        <a href="#" onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                                           class="text-red-600 hover:text-red-700">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
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
                                <a href="<?php echo SITE_URL; ?>/dashboard/orders.php?page=<?php echo $page - 1; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < ceil($total_orders / $limit)): ?>
                                <a href="<?php echo SITE_URL; ?>/dashboard/orders.php?page=<?php echo $page + 1; ?>" 
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
                <h3 class="text-xl font-medium text-gray-700 mb-2">No orders yet</h3>
                <p class="text-gray-500 mb-6">You haven't placed any orders yet.</p>
                <a href="<?php echo SITE_URL; ?>/shop.php" 
                   class="inline-block bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                    Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=cancel_order&order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}
</script>

<?php
// Helper function for order status badge
function get_order_status_badge($status) {
    $badges = [
        'pending' => [
            'class' => 'bg-yellow-100 text-yellow-800',
            'icon' => 'fas fa-clock'
        ],
        'processing' => [
            'class' => 'bg-blue-100 text-blue-800',
            'icon' => 'fas fa-cog'
        ],
        'shipped' => [
            'class' => 'bg-indigo-100 text-indigo-800',
            'icon' => 'fas fa-shipping-fast'
        ],
        'delivered' => [
            'class' => 'bg-green-100 text-green-800',
            'icon' => 'fas fa-check-circle'
        ],
        'cancelled' => [
            'class' => 'bg-red-100 text-red-800',
            'icon' => 'fas fa-times-circle'
        ]
    ];
    
    $badge = $badges[$status] ?? $badges['pending'];
    
    return sprintf(
        '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium %s">
            <i class="%s mr-1"></i> %s
        </span>',
        $badge['class'],
        $badge['icon'],
        ucfirst($status)
    );
}

require_once __DIR__ . '/../includes/footer.php';
?>