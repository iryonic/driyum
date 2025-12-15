<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$page_title = 'Shopping Cart';
$breadcrumbs = [
    ['title' => 'Cart']
];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $cart_item_id = intval($_POST['cart_item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    switch ($action) {
        case 'add':
            if ($product_id > 0) {
                $result = add_to_cart($product_id, $quantity);
                if ($result) {
                    $_SESSION['success'] = 'Product added to cart successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to add product to cart.';
                }
            }
            break;
            
        case 'update':
            if ($cart_item_id > 0) {
                $result = update_cart_quantity($cart_item_id, $quantity);
                if ($result) {
                    $_SESSION['success'] = 'Cart updated successfully.';
                }
            }
            break;
            
        case 'remove':
            if ($cart_item_id > 0) {
                $result = remove_from_cart($cart_item_id);
                if ($result) {
                    $_SESSION['success'] = 'Item removed from cart.';
                }
            }
            break;
            
        case 'clear':
            if (isset($_SESSION['user_id'])) {
                db_query("DELETE FROM cart WHERE user_id = ?", [$_SESSION['user_id']], 'i');
            } elseif (isset($_SESSION['cart_session_id'])) {
                db_query("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL", [$_SESSION['cart_session_id']], 's');
            }
            $_SESSION['success'] = 'Cart cleared successfully.';
            break;
    }
    
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

// Get cart items
$cart_items = get_cart_items();
$cart_total = calculate_cart_total();

// Get tax and shipping
$tax_rate = get_setting('tax_rate') ?? 18;
$tax_amount = ($cart_total * $tax_rate) / 100;
$shipping_amount = get_setting('shipping_amount') ?? 50;
$free_shipping_amount = get_setting('free_shipping_amount') ?? 500;

if ($cart_total >= $free_shipping_amount) {
    $shipping_amount = 0;
}

$total_amount = $cart_total + $tax_amount + $shipping_amount;

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Shopping Cart</h1>
    
    <?php if (empty($cart_items)): ?>
        <div class="text-center py-16 bg-gray-100 rounded-xl">
            <i class="fas fa-shopping-cart text-5xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Your cart is empty</h3>
            <p class="text-gray-500 mb-6">Looks like you haven't added any items to your cart yet.</p>
            <a href="<?php echo SITE_URL; ?>/shop.php" 
               class="inline-block bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-800">Cart Items (<?php echo count($cart_items); ?>)</h2>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to clear your cart?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">
                                    <i class="fas fa-trash-alt mr-1"></i> Clear Cart
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="p-6">
                                <div class="flex flex-col md:flex-row gap-4">
                                    <!-- Product Image -->
                                    <div class="md:w-24 flex-shrink-0">
                                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>">
                                            <img src="<?php echo $item['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="w-full h-24 object-cover rounded-lg">
                                        </a>
                                    </div>
                                    
                                    <!-- Product Info -->
                                    <div class="flex-grow">
                                        <div class="flex justify-between">
                                            <div>
                                                <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>" 
                                                   class="font-medium text-gray-800 hover:text-amber-600">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    Price: <?php echo format_price($item['price']); ?>
                                                </p>
                                            </div>
                                            
                                            <!-- Mobile Actions -->
                                            <div class="md:hidden">
                                                <form method="POST" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Quantity and Stock -->
                                        <div class="flex items-center justify-between mt-4">
                                            <!-- Quantity Controls -->
                                            <div class="flex items-center">
                                                <form method="POST" class="flex items-center">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>"
                                                            class="bg-gray-200 text-gray-700 w-8 h-8 rounded-l hover:bg-gray-300">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <span class="w-12 text-center border-y border-gray-300 h-8 flex items-center justify-center">
                                                        <?php echo $item['quantity']; ?>
                                                    </span>
                                                    <button type="submit" name="quantity" value="<?php echo $item['quantity'] + 1; ?>"
                                                            <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>
                                                            class="bg-gray-200 text-gray-700 w-8 h-8 rounded-r hover:bg-gray-300 <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Remove Button -->
                                                <form method="POST" class="ml-4">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-700 text-sm">
                                                        <i class="fas fa-trash-alt mr-1"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Item Total -->
                                            <div class="font-semibold text-gray-800">
                                                <?php echo format_price($item['price'] * $item['quantity']); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Stock Warning -->
                                        <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                                            <div class="mt-2 text-red-600 text-sm">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Only <?php echo $item['stock_quantity']; ?> left in stock
                                            </div>
                                        <?php elseif ($item['stock_quantity'] <= 5): ?>
                                            <div class="mt-2 text-amber-600 text-sm">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                Low stock: <?php echo $item['stock_quantity']; ?> left
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Continue Shopping -->
                <div class="mt-6">
                    <a href="<?php echo SITE_URL; ?>/shop.php" 
                       class="inline-flex items-center text-amber-600 hover:text-amber-700 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
                    </a>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md sticky top-24">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Order Summary</h2>
                    </div>
                    
                    <div class="p-6">
                        <!-- Price Breakdown -->
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium"><?php echo format_price($cart_total); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax (<?php echo $tax_rate; ?>%)</span>
                                <span class="font-medium"><?php echo format_price($tax_amount); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium">
                                    <?php if ($shipping_amount > 0): ?>
                                        <?php echo format_price($shipping_amount); ?>
                                    <?php else: ?>
                                        <span class="text-green-600">FREE</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($shipping_amount > 0): ?>
                                <div class="bg-amber-50 p-3 rounded-lg">
                                    <p class="text-sm text-amber-800">
                                        <i class="fas fa-truck mr-1"></i>
                                        Add ₹<?php echo number_format($free_shipping_amount - $cart_total, 2); ?> more for free shipping!
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total</span>
                                    <span class="text-amber-600"><?php echo format_price($total_amount); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Coupon Code -->
                        <div class="mb-6">
                            <form id="couponForm" class="flex">
                                <input type="text" id="couponCode" placeholder="Coupon code" 
                                       class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                <button type="button" onclick="applyCoupon()" 
                                        class="bg-gray-800 text-white px-4 py-2 rounded-r-lg hover:bg-gray-900">
                                    Apply
                                </button>
                            </form>
                            <div id="couponMessage" class="mt-2 text-sm hidden"></div>
                        </div>
                        
                        <!-- Checkout Button -->
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo SITE_URL; ?>/checkout.php" 
                               class="block w-full bg-amber-600 text-white text-center py-3 rounded-lg hover:bg-amber-700 transition font-medium mb-3">
                                Proceed to Checkout
                            </a>
                        <?php else: ?>
                            <div class="space-y-3">
                                <a href="<?php echo SITE_URL; ?>/checkout.php" 
                                   class="block w-full bg-amber-600 text-white text-center py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                                    Proceed to Checkout
                                </a>
                                <p class="text-sm text-gray-600 text-center">
                                    Already have an account? 
                                    <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode(SITE_URL . '/checkout.php'); ?>" 
                                       class="text-amber-600 hover:text-amber-700">
                                        Login here
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Secure Checkout Info -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                                <i class="fas fa-shield-alt text-green-500"></i>
                                <span>Secure checkout</span>
                            </div>
                            <div class="flex justify-center space-x-3 mt-3">
                                <i class="fab fa-cc-visa text-2xl text-gray-400"></i>
                                <i class="fab fa-cc-mastercard text-2xl text-gray-400"></i>
                                <i class="fab fa-cc-amex text-2xl text-gray-400"></i>
                                <i class="fab fa-cc-paypal text-2xl text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Apply coupon
function applyCoupon() {
    const couponCode = document.getElementById('couponCode').value.trim();
    const messageDiv = document.getElementById('couponMessage');
    
    if (!couponCode) {
        messageDiv.className = 'mt-2 text-sm text-red-600';
        messageDiv.innerHTML = 'Please enter a coupon code.';
        messageDiv.classList.remove('hidden');
        return;
    }
    
    // In a real implementation, this would be an AJAX call
    messageDiv.className = 'mt-2 text-sm text-green-600';
    messageDiv.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Coupon applied successfully!';
    messageDiv.classList.remove('hidden');
    
    // For now, we'll simulate by reloading with coupon parameter
    setTimeout(() => {
        window.location.href = '<?php echo SITE_URL; ?>/checkout.php?coupon=' + encodeURIComponent(couponCode);
    }, 1000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>