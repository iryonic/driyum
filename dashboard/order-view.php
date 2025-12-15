<?php
require_once __DIR__ . '/../includes/auth.php';
require_user();

$page_title = 'Order Details';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => SITE_URL . '/dashboard/'],
    ['title' => 'Orders', 'url' => SITE_URL . '/dashboard/orders.php'],
    ['title' => 'Order Details']
];

$order_id = intval($_GET['id'] ?? 0);
if (!$order_id) {
    header('Location: ' . SITE_URL . '/dashboard/orders.php');
    exit;
}

// Get order details
$order = get_order_details($order_id, $_SESSION['user_id']);
if (!$order) {
    $_SESSION['error'] = 'Order not found or access denied.';
    header('Location: ' . SITE_URL . '/dashboard/orders.php');
    exit;
}

// Get order items
$order_items = get_order_items($order_id);

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'cancel':
            if (in_array($order['order_status'], ['pending', 'processing'])) {
                db_query(
                    "UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ?",
                    [$order_id],
                    'i'
                );
                
                // Restore stock
                foreach ($order_items as $item) {
                    db_query(
                        "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                        [$item['quantity'], $item['product_id']],
                        'ii'
                    );
                }
                
                $_SESSION['success'] = 'Order cancelled successfully.';
                header('Location: ' . SITE_URL . '/dashboard/order-view.php?id=' . $order_id);
                exit;
            }
            break;
            
        case 'reorder':
            // Add all items to cart
            foreach ($order_items as $item) {
                if ($item['product_id']) {
                    add_to_cart($item['product_id'], $item['quantity']);
                }
            }
            $_SESSION['success'] = 'Items added to cart.';
            header('Location: ' . SITE_URL . '/cart.php');
            exit;
            break;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Order Header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Order #<?php echo $order['order_number']; ?></h1>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo get_order_status_class($order['order_status']); ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                    <span class="text-gray-500">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                    </span>
                    <span class="text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <?php echo date('h:i A', strtotime($order['created_at'])); ?>
                    </span>
                </div>
            </div>
            
            <!-- Order Actions -->
            <div class="mt-4 md:mt-0 flex flex-wrap gap-2">
                <?php if (in_array($order['order_status'], ['pending', 'processing'])): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" 
                                class="bg-red-100 text-red-600 px-4 py-2 rounded-lg hover:bg-red-200 transition">
                            <i class="fas fa-times mr-2"></i> Cancel Order
                        </button>
                    </form>
                <?php endif; ?>
                
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="reorder">
                    <button type="submit" 
                            class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition">
                        <i class="fas fa-redo mr-2"></i> Reorder
                    </button>
                </form>
                
                <a href="<?php echo SITE_URL; ?>/dashboard/orders.php" 
                   class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>
    
    <!-- Order Timeline -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Order Timeline</h2>
        
        <?php
        $timeline = [
            'pending' => [
                'icon' => 'fas fa-shopping-cart',
                'color' => 'text-yellow-500',
                'bg' => 'bg-yellow-100',
                'date' => $order['created_at'],
                'title' => 'Order Placed',
                'description' => 'Your order has been received'
            ],
            'processing' => [
                'icon' => 'fas fa-cog',
                'color' => 'text-blue-500',
                'bg' => 'bg-blue-100',
                'title' => 'Processing',
                'description' => 'Preparing your order'
            ],
            'shipped' => [
                'icon' => 'fas fa-shipping-fast',
                'color' => 'text-indigo-500',
                'bg' => 'bg-indigo-100',
                'title' => 'Shipped',
                'description' => 'Your order is on the way'
            ],
            'delivered' => [
                'icon' => 'fas fa-check-circle',
                'color' => 'text-green-500',
                'bg' => 'bg-green-100',
                'title' => 'Delivered',
                'description' => 'Your order has been delivered'
            ]
        ];
        
        $status_order = ['pending', 'processing', 'shipped', 'delivered'];
        $current_status_index = array_search($order['order_status'], $status_order);
        ?>
        
        <div class="relative">
            <!-- Progress Line -->
            <div class="absolute left-0 md:left-1/2 top-6 h-0.5 bg-gray-200 w-full md:w-0 md:h-full md:top-0 md:left-6 transform md:-translate-x-1/2"></div>
            
            <!-- Timeline Steps -->
            <div class="relative grid grid-cols-1 md:grid-cols-4 gap-8">
                <?php foreach ($status_order as $index => $status): ?>
                    <?php
                    $step = $timeline[$status];
                    $is_active = $index <= $current_status_index;
                    $is_current = $index === $current_status_index;
                    ?>
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row md:items-center">
                            <!-- Icon -->
                            <div class="flex items-center justify-center mb-4 md:mb-0 md:mr-4">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center 
                                            <?php echo $is_active ? $step['bg'] . ' ' . $step['color'] : 'bg-gray-100 text-gray-400'; ?> 
                                            <?php echo $is_current ? 'ring-4 ring-opacity-30 ' . str_replace('text-', 'ring-', $step['color']) : ''; ?>">
                                    <i class="<?php echo $step['icon']; ?>"></i>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="md:flex-1">
                                <h3 class="font-medium text-gray-800 <?php echo $is_active ? '' : 'text-gray-500'; ?>">
                                    <?php echo $step['title']; ?>
                                </h3>
                                <p class="text-sm <?php echo $is_active ? 'text-gray-600' : 'text-gray-400'; ?>">
                                    <?php echo $step['description']; ?>
                                </p>
                                <?php if ($is_active && isset($step['date'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?php echo date('M j, Y', strtotime($step['date'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Order Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Order Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Order Items (<?php echo count($order_items); ?>)</h2>
                </div>
                
                <div class="divide-y divide-gray-200">
                    <?php foreach ($order_items as $item): ?>
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row gap-4">
                                <!-- Product Image -->
                                <div class="md:w-20 flex-shrink-0">
                                    <?php if ($item['slug']): ?>
                                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>">
                                            <img src="<?php echo $item['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="w-full h-20 object-cover rounded-lg">
                                        </a>
                                    <?php else: ?>
                                        <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             class="w-full h-20 object-cover rounded-lg">
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="flex-grow">
                                    <div class="flex justify-between">
                                        <div>
                                            <?php if ($item['slug']): ?>
                                                <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>" 
                                                   class="font-medium text-gray-800 hover:text-amber-600">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-500 mt-1">Quantity: <?php echo $item['quantity']; ?></p>
                                        </div>
                                        
                                        <div class="text-right">
                                            <p class="font-medium text-gray-800"><?php echo format_price($item['price']); ?></p>
                                            <p class="text-sm text-gray-500">Total: <?php echo format_price($item['total']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Review Button -->
                            <?php if ($order['order_status'] === 'delivered' && $item['slug']): ?>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>#reviews" 
                                       class="inline-flex items-center text-amber-600 hover:text-amber-700 text-sm">
                                        <i class="fas fa-star mr-2"></i> Write a Review
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Order Summary</h2>
                
                <!-- Price Breakdown -->
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium"><?php echo format_price($order['subtotal']); ?></span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tax</span>
                        <span class="font-medium"><?php echo format_price($order['tax_amount']); ?></span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-medium">
                            <?php if ($order['shipping_amount'] > 0): ?>
                                <?php echo format_price($order['shipping_amount']); ?>
                            <?php else: ?>
                                <span class="text-green-600">FREE</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Discount</span>
                            <span class="font-medium text-green-600">-<?php echo format_price($order['discount_amount']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span class="text-amber-600"><?php echo format_price($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Order Information -->
                <div class="space-y-4">
                    <div>
                        <h3 class="font-medium text-gray-700 mb-2">Payment Information</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p>Method: <?php echo strtoupper($order['payment_method']); ?></p>
                            <p>Status: 
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                      <?php echo $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-700 mb-2">Shipping Information</h3>
                        <div class="text-sm text-gray-600">
                            <p class="font-medium"><?php echo htmlspecialchars($order['full_name']); ?></p>
                            <p><?php echo htmlspecialchars($order['address_line1']); ?></p>
                            <?php if ($order['address_line2']): ?>
                                <p><?php echo htmlspecialchars($order['address_line2']); ?></p>
                            <?php endif; ?>
                            <p>
                                <?php echo htmlspecialchars($order['city']); ?>, 
                                <?php echo htmlspecialchars($order['state']); ?> - 
                                <?php echo htmlspecialchars($order['postal_code']); ?>
                            </p>
                            <p class="mt-1">
                                <i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($order['phone']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($order['notes']): ?>
                        <div>
                            <h3 class="font-medium text-gray-700 mb-2">Order Notes</h3>
                            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                                <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Download Invoice -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="#" onclick="downloadInvoice()" 
                       class="w-full bg-gray-800 text-white py-2 rounded-lg hover:bg-gray-900 transition flex items-center justify-center">
                        <i class="fas fa-download mr-2"></i> Download Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help Section -->
    <div class="mt-8 bg-amber-50 border border-amber-200 rounded-xl p-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
            <div class="mb-4 md:mb-0">
                <h3 class="font-semibold text-amber-800 mb-2">Need help with your order?</h3>
                <p class="text-amber-700">Our support team is here to help you.</p>
            </div>
            <div class="flex space-x-3">
                <a href="<?php echo SITE_URL; ?>/contact.php" 
                   class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700 transition">
                    <i class="fas fa-headset mr-2"></i> Contact Support
                </a>
                <a href="<?php echo SITE_URL; ?>/faq.php" 
                   class="bg-white text-amber-600 border border-amber-600 px-6 py-2 rounded-lg hover:bg-amber-50 transition">
                    <i class="fas fa-question-circle mr-2"></i> View FAQ
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function downloadInvoice() {
    // In a real implementation, this would generate and download a PDF invoice
    alert('Invoice download feature would be implemented here.');
    // window.location.href = '<?php echo SITE_URL; ?>/includes/invoice.php?order_id=<?php echo $order_id; ?>';
}

// Helper function for status classes
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>