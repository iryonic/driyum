<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Order Details';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Orders', 'url' => ADMIN_URL . '/orders.php'],
    ['title' => 'Order Details']
];

// Get order ID
$order_id = intval($_GET['id'] ?? 0);
if (!$order_id) {
    header('Location: ' . ADMIN_URL . '/orders.php');
    exit;
}

// Get order details
$order = get_order_details($order_id);
if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header('Location: ' . ADMIN_URL . '/orders.php');
    exit;
}

// Get order items
$items = get_order_items($order_id);

// Get customer info
$customer = db_fetch_single(
    "SELECT id, name, email, phone, created_at FROM users WHERE id = ?",
    [$order['user_id']],
    'i'
);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $order_status = sanitize_input($_POST['order_status'] ?? '');
        $payment_status = sanitize_input($_POST['payment_status'] ?? '');
        $tracking_number = sanitize_input($_POST['tracking_number'] ?? '');
        $tracking_url = sanitize_input($_POST['tracking_url'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');
        
        if (in_array($order_status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            // Update order
            db_query(
                "UPDATE orders SET 
                 order_status = ?, payment_status = ?, tracking_number = ?, tracking_url = ?, notes = ?, updated_at = NOW()
                 WHERE id = ?",
                [$order_status, $payment_status, $tracking_number, $tracking_url, $notes, $order_id],
                'sssssi'
            );
            
            // If cancelled, restore stock
            if ($order_status === 'cancelled' && $order['order_status'] !== 'cancelled') {
                foreach ($items as $item) {
                    db_query(
                        "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                        [$item['quantity'], $item['product_id']],
                        'ii'
                    );
                }
            }
            
            // Log admin activity
            log_admin_activity($_SESSION['user_id'], 'update_order', 
                "Updated order #{$order['order_number']} status to {$order_status}");
            
            $_SESSION['success'] = 'Order updated successfully.';
            header('Location: ' . ADMIN_URL . '/order-view.php?id=' . $order_id);
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Order #<?php echo $order['order_number']; ?></h1>
                <p class="text-gray-600">Placed on <?php echo date('F j, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-2">
                <a href="<?php echo SITE_URL; ?>/includes/invoice.php?order_id=<?php echo $order_id; ?>" 
                   target="_blank" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-file-invoice mr-2"></i> View Invoice
                </a>
                <a href="<?php echo ADMIN_URL; ?>/orders.php" 
                   class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>
    
    <!-- Order Status Badge -->
    <div class="mb-8">
        <div class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium 
                    <?php echo get_order_status_class($order['order_status']); ?>">
            <i class="fas fa-circle text-xs mr-2"></i>
            <?php echo ucfirst($order['order_status']); ?>
        </div>
        <div class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium ml-4
                    <?php echo get_payment_status_class($order['payment_status']); ?>">
            <i class="fas fa-credit-card text-xs mr-2"></i>
            <?php echo ucfirst($order['payment_status']); ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Order Items -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Order Items</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <?php if ($item['image_main']): ?>
                                                <div class="h-12 w-12 flex-shrink-0 mr-3">
                                                    <img src="<?php echo $item['image_main']; ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                         class="h-12 w-12 object-cover rounded">
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    SKU: <?php echo $item['sku'] ?? 'N/A'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4"><?php echo format_price($item['price']); ?></td>
                                    <td class="px-4 py-4"><?php echo $item['quantity']; ?></td>
                                    <td class="px-4 py-4 font-medium"><?php echo format_price($item['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-right font-medium">Subtotal:</td>
                                <td class="px-4 py-4 font-medium"><?php echo format_price($order['subtotal']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-right font-medium">Tax:</td>
                                <td class="px-4 py-4"><?php echo format_price($order['tax_amount']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-right font-medium">Shipping:</td>
                                <td class="px-4 py-4"><?php echo format_price($order['shipping_amount']); ?></td>
                            </tr>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-right font-medium text-green-600">Discount:</td>
                                    <td class="px-4 py-4 text-green-600">-<?php echo format_price($order['discount_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-right font-bold text-lg">Total:</td>
                                <td class="px-4 py-4 font-bold text-lg"><?php echo format_price($order['total_amount']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Shipping Address</h2>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-gray-600">Name:</span>
                        <span class="font-medium ml-2"><?php echo htmlspecialchars($order['full_name']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-medium ml-2"><?php echo $order['phone']; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Address:</span>
                        <span class="font-medium ml-2"><?php echo htmlspecialchars($order['address_line1']); ?></span>
                    </div>
                    <?php if ($order['address_line2']): ?>
                        <div>
                            <span class="text-gray-600">Address Line 2:</span>
                            <span class="font-medium ml-2"><?php echo htmlspecialchars($order['address_line2']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="text-gray-600">City:</span>
                        <span class="font-medium ml-2"><?php echo htmlspecialchars($order['city']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">State:</span>
                        <span class="font-medium ml-2"><?php echo htmlspecialchars($order['state']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Postal Code:</span>
                        <span class="font-medium ml-2"><?php echo $order['postal_code']; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Country:</span>
                        <span class="font-medium ml-2"><?php echo $order['country']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-8">
            <!-- Order Actions -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Update Order</h2>
                
                <form method="POST" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_status">
                    
                    <!-- Order Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                        <select name="order_status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Payment Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                        <select name="payment_status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <!-- Tracking Info -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tracking Number</label>
                        <input type="text" name="tracking_number" 
                               value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"
                               placeholder="Enter tracking number">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tracking URL</label>
                        <input type="url" name="tracking_url" 
                               value="<?php echo htmlspecialchars($order['tracking_url'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"
                               placeholder="https://tracking.carrier.com/...">
                    </div>
                    
                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                        <textarea name="notes" rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-amber-600 text-white py-3 px-4 rounded-lg hover:bg-amber-700 transition font-medium">
                        <i class="fas fa-save mr-2"></i> Update Order
                    </button>
                </form>
            </div>
            
            <!-- Customer Info -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Customer Information</h2>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-gray-600">Name:</span>
                        <a href="<?php echo ADMIN_URL; ?>/users.php?search=<?php echo urlencode($customer['email']); ?>"
                           class="font-medium ml-2 text-amber-600 hover:text-amber-700">
                            <?php echo htmlspecialchars($customer['name']); ?>
                        </a>
                    </div>
                    <div>
                        <span class="text-gray-600">Email:</span>
                        <span class="font-medium ml-2"><?php echo $customer['email']; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-medium ml-2"><?php echo $customer['phone'] ?? 'Not provided'; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Member Since:</span>
                        <span class="font-medium ml-2"><?php echo date('M Y', strtotime($customer['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a href="mailto:<?php echo $customer['email']; ?>" 
                       class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition mb-2">
                        <i class="fas fa-envelope mr-2"></i> Send Email
                    </a>
                    
                    <?php if ($customer['phone']): ?>
                        <a href="tel:<?php echo $customer['phone']; ?>" 
                           class="block w-full bg-green-600 text-white text-center py-2 px-4 rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-phone mr-2"></i> Call Customer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Timeline -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Order Timeline</h2>
                
                <div class="space-y-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-check text-green-600 text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Order Placed</p>
                            <p class="text-xs text-gray-500"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($order['order_status'] !== 'pending'): ?>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-sync-alt text-blue-600 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Order Confirmed</p>
                                <p class="text-xs text-gray-500">Processing order</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array($order['order_status'], ['shipped', 'delivered'])): ?>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <i class="fas fa-shipping-fast text-indigo-600 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Order Shipped</p>
                                <?php if ($order['tracking_number']): ?>
                                    <p class="text-xs text-gray-500">Tracking: <?php echo $order['tracking_number']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['order_status'] === 'delivered'): ?>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-home text-green-600 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Delivered</p>
                                <p class="text-xs text-gray-500">Order delivered to customer</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions for status classes
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

function get_payment_status_class($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'paid': return 'bg-green-100 text-green-800';
        case 'failed': return 'bg-red-100 text-red-800';
        case 'refunded': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

require_once __DIR__ . '/../includes/admin-footer.php';
?>