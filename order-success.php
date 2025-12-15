<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Order Confirmation';
$breadcrumbs = [
    ['title' => 'Order Confirmation']
];

// Check if order was placed
if (!isset($_SESSION['order_number'])) {
    header('Location: ' . SITE_URL . '/shop.php');
    exit;
}

$order_number = $_SESSION['order_number'];
unset($_SESSION['order_number']);

// Get order details
$order = db_fetch_single(
    "SELECT o.*, a.* FROM orders o 
     JOIN user_addresses a ON o.address_id = a.id 
     WHERE o.order_number = ?",
    [$order_number],
    's'
);

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header('Location: ' . SITE_URL . '/shop.php');
    exit;
}

// Get order items
$order_items = get_order_items($order['id']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Success Message -->
    <div class="text-center mb-12">
        <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check text-3xl text-green-600"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Order Confirmed!</h1>
        <p class="text-gray-600 text-lg">
            Thank you for your order. We've sent a confirmation email with your order details.
        </p>
    </div>
    
    <!-- Order Details Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-amber-600 to-amber-800 px-8 py-6 text-white">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Order #<?php echo $order['order_number']; ?></h2>
                    <p class="text-amber-100">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                        at <?php echo date('h:i A', strtotime($order['created_at'])); ?>
                    </p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="inline-block bg-white text-amber-700 px-4 py-2 rounded-lg font-semibold">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="p-8">
            <!-- Order Items -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Order Items</h3>
                <div class="space-y-4">
                    <?php foreach ($order_items as $item): ?>
                        <div class="flex items-center border-b border-gray-100 pb-4">
                            <div class="w-16 h-16 flex-shrink-0">
                                <img src="<?php echo $item['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     class="w-full h-full object-cover rounded">
                            </div>
                            <div class="ml-4 flex-grow">
                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <p class="text-sm text-gray-500">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-800"><?php echo format_price($item['price']); ?></p>
                                <p class="text-sm text-gray-500">Total: <?php echo format_price($item['total']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Shipping Address -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Shipping Address</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($order['full_name']); ?></p>
                        <p class="text-gray-600 mt-2">
                            <?php echo htmlspecialchars($order['address_line1']); ?><br>
                            <?php if ($order['address_line2']): ?>
                                <?php echo htmlspecialchars($order['address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> - 
                            <?php echo htmlspecialchars($order['postal_code']); ?><br>
                            <?php echo htmlspecialchars($order['country']); ?>
                        </p>
                        <p class="text-gray-600 mt-2">
                            <i class="fas fa-phone mr-2"></i> <?php echo htmlspecialchars($order['phone']); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Order Summary</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium"><?php echo format_price($order['subtotal']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium"><?php echo format_price($order['tax_amount']); ?></span>
                            </div>
                            <div class="flex justify-between">
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
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Discount</span>
                                    <span class="font-medium text-green-600">-<?php echo format_price($order['discount_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="border-t border-gray-300 pt-2 mt-2">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total</span>
                                    <span class="text-amber-600"><?php echo format_price($order['total_amount']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Info -->
                        <div class="mt-6 pt-6 border-t border-gray-300">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-600">Payment Method</p>
                                    <p class="font-medium text-gray-800">
                                        <?php echo strtoupper($order['payment_method']); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Payment Status</p>
                                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full 
                                          <?php echo $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Notes -->
            <?php if ($order['notes']): ?>
                <div class="mt-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Order Notes</h3>
                    <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Next Steps -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-envelope text-blue-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">Check Your Email</h3>
            <p class="text-gray-600 text-sm">
                We've sent order confirmation and tracking details to your email.
            </p>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <div class="bg-green-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shipping-fast text-green-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">Track Your Order</h3>
            <p class="text-gray-600 text-sm">
                You can track your order status from your dashboard.
            </p>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <div class="bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-headset text-purple-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">Need Help?</h3>
            <p class="text-gray-600 text-sm">
                Contact our support team for any questions about your order.
            </p>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="text-center">
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="<?php echo SITE_URL; ?>/dashboard/order-view.php?id=<?php echo $order['id']; ?>" 
               class="bg-amber-600 text-white px-8 py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                <i class="fas fa-eye mr-2"></i> View Order Details
            </a>
            <a href="<?php echo SITE_URL; ?>/shop.php" 
               class="bg-white text-amber-600 border border-amber-600 px-8 py-3 rounded-lg hover:bg-amber-50 transition font-medium">
                <i class="fas fa-shopping-bag mr-2"></i> Continue Shopping
            </a>
            <a href="<?php echo SITE_URL; ?>/dashboard/orders.php" 
               class="bg-gray-800 text-white px-8 py-3 rounded-lg hover:bg-gray-900 transition font-medium">
                <i class="fas fa-list mr-2"></i> View All Orders
            </a>
        </div>
        <p class="text-gray-500 mt-6">
            Estimated delivery: <?php echo date('F j, Y', strtotime('+3-7 days')); ?>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>