<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$page_title = 'Checkout';
$breadcrumbs = [
    ['title' => 'Checkout']
];

// Get cart items
$cart_items = get_cart_items();
if (empty($cart_items)) {
    $_SESSION['error'] = 'Your cart is empty. Please add items to your cart before checkout.';
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

// Check stock availability
foreach ($cart_items as $item) {
    if ($item['stock_quantity'] < $item['quantity']) {
        $_SESSION['error'] = 'Sorry, ' . $item['name'] . ' is out of stock or has insufficient quantity.';
        header('Location: ' . SITE_URL . '/cart.php');
        exit;
    }
}

// Handle direct checkout (buy now)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_checkout'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    // Clear current cart and add single product
    if (isset($_SESSION['user_id'])) {
        db_query("DELETE FROM cart WHERE user_id = ?", [$_SESSION['user_id']], 'i');
    } elseif (isset($_SESSION['cart_session_id'])) {
        db_query("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL", [$_SESSION['cart_session_id']], 's');
    }
    
    add_to_cart($product_id, $quantity);
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $guest_checkout_allowed = get_setting('guest_checkout') === '1';
    $payment_method = sanitize_input($_POST['payment_method'] ?? 'cod');
    $coupon_code = sanitize_input($_POST['coupon_code'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');

    $user_id = null;
    $guest_email = null;

    if (is_logged_in()) {
        // Logged-in user
        require_user();
        $user_id = $_SESSION['user_id'];
        $address_id = intval($_POST['address_id'] ?? 0);

        // Validate address belongs to user
        $address = db_fetch_single(
            "SELECT * FROM user_addresses WHERE id = ? AND user_id = ?",
            [$address_id, $user_id],
            'ii'
        );

        if (!$address) {
            $error = 'Please select a valid shipping address.';
        }
    } else {
        // Guest path
        if (!$guest_checkout_allowed) {
            // Guests not allowed to checkout
            require_user();
        }

        // Validate guest required fields
        $guest_email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $address_line1 = sanitize_input($_POST['address_line1'] ?? '');
        $address_line2 = sanitize_input($_POST['address_line2'] ?? '');
        $city = sanitize_input($_POST['city'] ?? '');
        $state = sanitize_input($_POST['state'] ?? '');
        $postal_code = sanitize_input($_POST['postal_code'] ?? '');
        $country = sanitize_input($_POST['country'] ?? 'India');

        if (empty($guest_email) || empty($full_name) || empty($phone) || empty($address_line1) || empty($city) || empty($state) || empty($postal_code)) {
            $error = 'Please fill all required shipping fields including a valid email address.';
        } else {
            // Create guest address
            $address_data = [
                'full_name' => $full_name,
                'phone' => $phone,
                'address_line1' => $address_line1,
                'address_line2' => $address_line2,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'postal_code' => $postal_code,
                'is_default' => 0
            ];

            $inserted = add_guest_address($address_data);
            if ($inserted) {
                $address_id = db_last_insert_id();
            } else {
                $error = 'Failed to save shipping address. Please try again.';
            }
        }
    }

    // If no error so far, attempt to create order
    if (empty($error)) {
        $result = create_order($user_id, $address_id ?? 0, $cart_items, $coupon_code);

        if ($result['success']) {
            // Update order with payment method and notes
            db_query(
                "UPDATE orders SET payment_method = ?, notes = ? WHERE id = ?",
                [$payment_method, $notes, $result['order_id']],
                'ssi'
            );

            // Send confirmation email (use guest email if provided)
            send_order_confirmation($result['order_id'], $guest_email);

            // Redirect to success page
            $_SESSION['order_number'] = $result['order_number'];
            header('Location: ' . SITE_URL . '/order-success.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Get user addresses
$addresses = [];
$coupon_discount = 0;
$coupon_code = $_GET['coupon'] ?? '';

if (is_logged_in()) {
    $addresses = get_user_addresses($_SESSION['user_id']);
    
    // Validate coupon if provided
    if ($coupon_code) {
        $cart_total = calculate_cart_total();
        $coupon_validation = validate_coupon($coupon_code, $cart_total);
        if ($coupon_validation['valid']) {
            if ($coupon_validation['type'] === 'percentage') {
                $coupon_discount = ($cart_total * $coupon_validation['value']) / 100;
                if ($coupon_validation['max_discount'] && $coupon_discount > $coupon_validation['max_discount']) {
                    $coupon_discount = $coupon_validation['max_discount'];
                }
            } else {
                $coupon_discount = $coupon_validation['value'];
            }
            $_SESSION['success'] = 'Coupon applied successfully! Discount: ' . format_price($coupon_discount);
        } else {
            $_SESSION['error'] = $coupon_validation['message'];
        }
    }
}

// Calculate totals
$cart_total = calculate_cart_total();
$tax_rate = get_setting('tax_rate') ?? 18;
$tax_amount = ($cart_total * $tax_rate) / 100;
$shipping_amount = get_setting('shipping_amount') ?? 50;
$free_shipping_amount = get_setting('free_shipping_amount') ?? 500;

if ($cart_total >= $free_shipping_amount) {
    $shipping_amount = 0;
}

$total_amount = $cart_total + $tax_amount + $shipping_amount - $coupon_discount;

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Checkout</h1>
    
    <?php if (!is_logged_in()): ?>
        <!-- Guest Checkout Notice -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-8">
            <div class="flex items-start">
                <div class="bg-amber-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-exclamation-triangle text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-amber-800 mb-2">Create an account or login for faster checkout</h3>
                    <p class="text-amber-700 mb-4">
                        Registered users can save addresses, track orders, and enjoy exclusive offers.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode(SITE_URL . '/checkout.php'); ?>" 
                           class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700 transition">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </a>
                        <a href="<?php echo SITE_URL; ?>/register.php?redirect=<?php echo urlencode(SITE_URL . '/checkout.php'); ?>" 
                           class="bg-white text-amber-600 border border-amber-600 px-6 py-2 rounded-lg hover:bg-amber-50 transition">
                            <i class="fas fa-user-plus mr-2"></i> Create Account
                        </a>
                        <a href="<?php echo SITE_URL; ?>/checkout.php?guest=1" 
                           class="text-amber-600 hover:text-amber-700 font-medium">
                            Continue as guest
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo SITE_URL; ?>/checkout.php">
        <?php echo csrf_field(); ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Order Details -->
            <div class="lg:col-span-2">
                <!-- Shipping Address -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Shipping Address</h2>
                    
                    <?php if (is_logged_in() && !empty($addresses)): ?>
                        <!-- Saved Addresses -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <?php foreach ($addresses as $address): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-amber-500 transition <?php echo $address['is_default'] ? 'border-amber-500 bg-amber-50' : ''; ?>">
                                    <div class="flex items-start">
                                        <input type="radio" name="address_id" value="<?php echo $address['id']; ?>" 
                                               id="address_<?php echo $address['id']; ?>"
                                               class="mt-1 mr-3" 
                                               <?php echo $address['is_default'] ? 'checked' : ''; ?> required>
                                        <label for="address_<?php echo $address['id']; ?>" class="flex-grow cursor-pointer">
                                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($address['full_name']); ?></div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                                <?php if ($address['address_line2']): ?>
                                                    <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($address['city']); ?>, 
                                                <?php echo htmlspecialchars($address['state']); ?> - 
                                                <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                <?php echo htmlspecialchars($address['country']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-2">
                                                <i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($address['phone']); ?>
                                            </div>
                                            <?php if ($address['is_default']): ?>
                                                <span class="inline-block mt-2 px-2 py-1 text-xs bg-amber-100 text-amber-800 rounded">
                                                    Default Address
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="<?php echo SITE_URL; ?>/dashboard/addresses.php" 
                           class="text-amber-600 hover:text-amber-700 font-medium">
                            <i class="fas fa-plus mr-1"></i> Add New Address
                        </a>
                    <?php else: ?>
                        <!-- New Address Form -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input type="text" id="full_name" name="full_name" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                            </div>

                            <?php if (!is_logged_in()): ?>
                                <div class="mt-4">
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" id="email" name="email" required
                                           placeholder="you@example.com"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                                <input type="text" id="address_line1" name="address_line1" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            
                            <div>
                                <label for="address_line2" class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                                <input type="text" id="address_line2" name="address_line2"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                    <input type="text" id="city" name="city" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                                    <input type="text" id="state" name="state" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                                    <input type="text" id="postal_code" name="postal_code" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                <input type="text" id="country" name="country" value="India" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            
                            <?php if (is_logged_in()): ?>
                                <div class="flex items-center">
                                    <input type="checkbox" id="save_address" name="save_address" 
                                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                                    <label for="save_address" class="ml-2 text-sm text-gray-700">
                                        Save this address for future orders
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Payment Method -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Payment Method</h2>
                    
                    <div class="space-y-4">
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-amber-500 transition">
                            <div class="flex items-start">
                                <input type="radio" name="payment_method" value="cod" 
                                       id="payment_cod" class="mt-1 mr-3" checked required>
                                <label for="payment_cod" class="flex-grow cursor-pointer">
                                    <div class="font-medium text-gray-800">Cash on Delivery</div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        Pay when you receive your order
                                    </div>
                                </label>
                                <div class="text-2xl text-gray-400">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-amber-500 transition">
                            <div class="flex items-start">
                                <input type="radio" name="payment_method" value="online" 
                                       id="payment_online" class="mt-1 mr-3">
                                <label for="payment_online" class="flex-grow cursor-pointer">
                                    <div class="font-medium text-gray-800">Online Payment</div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        Pay securely with credit/debit card, UPI, or net banking
                                    </div>
                                </label>
                                <div class="text-2xl text-gray-400">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Gateway Logos -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600 mb-3">We accept:</p>
                        <div class="flex space-x-4">
                            <i class="fab fa-cc-visa text-3xl text-gray-400"></i>
                            <i class="fab fa-cc-mastercard text-3xl text-gray-400"></i>
                            <i class="fab fa-cc-amex text-3xl text-gray-400"></i>
                            <i class="fas fa-university text-3xl text-gray-400"></i>
                            <i class="fas fa-mobile-alt text-3xl text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Order Notes -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Notes (Optional)</h2>
                    <textarea name="notes" rows="3" 
                              placeholder="Any special instructions for your order..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
            </div>
            
            <!-- Right Column - Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md sticky top-24">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Order Summary</h2>
                    </div>
                    
                    <div class="p-6">
                        <!-- Order Items -->
                        <div class="mb-6">
                            <h3 class="font-medium text-gray-700 mb-3">Items (<?php echo count($cart_items); ?>)</h3>
                            <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 flex-shrink-0">
                                            <img src="<?php echo $item['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="w-full h-full object-cover rounded">
                                        </div>
                                        <div class="ml-3 flex-grow">
                                            <div class="text-sm font-medium text-gray-800 truncate">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $item['quantity']; ?> × <?php echo format_price($item['price']); ?>
                                            </div>
                                        </div>
                                        <div class="text-sm font-medium text-gray-800">
                                            <?php echo format_price($item['price'] * $item['quantity']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Price Breakdown -->
                        <div class="space-y-2 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium"><?php echo format_price($cart_total); ?></span>
                            </div>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax (<?php echo $tax_rate; ?>%)</span>
                                <span class="font-medium"><?php echo format_price($tax_amount); ?></span>
                            </div>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium">
                                    <?php if ($shipping_amount > 0): ?>
                                        <?php echo format_price($shipping_amount); ?>
                                    <?php else: ?>
                                        <span class="text-green-600">FREE</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- Coupon Discount -->
                            <?php if ($coupon_discount > 0): ?>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Coupon Discount</span>
                                    <span class="font-medium text-green-600">
                                        -<?php echo format_price($coupon_discount); ?>
                                    </span>
                                </div>
                                <input type="hidden" name="coupon_code" value="<?php echo htmlspecialchars($coupon_code); ?>">
                            <?php endif; ?>
                            
                            <div class="border-t border-gray-200 pt-2">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total</span>
                                    <span class="text-amber-600"><?php echo format_price($total_amount); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Apply Coupon -->
                        <div class="mb-6">
                            <div class="flex">
                                <input type="text" name="coupon_code_input" id="couponCodeInput" 
                                       placeholder="Coupon code" 
                                       value="<?php echo htmlspecialchars($coupon_code); ?>"
                                       class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <button type="button" onclick="applyCoupon()" 
                                        class="bg-gray-800 text-white px-4 py-2 rounded-r-lg hover:bg-gray-900">
                                    Apply
                                </button>
                            </div>
                            <div id="couponMessage" class="mt-2 text-sm"></div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="mb-6">
                            <div class="flex items-start">
                                <input type="checkbox" id="terms_agree" name="terms_agree" required
                                       class="mt-1 mr-3 h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                                <label for="terms_agree" class="text-sm text-gray-700">
                                    I agree to the 
                                    <a href="<?php echo SITE_URL; ?>/terms.php" class="text-amber-600 hover:text-amber-700" target="_blank">
                                        Terms and Conditions
                                    </a>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Place Order Button -->
                        <button type="submit" 
                                class="w-full bg-amber-600 text-white py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                            Place Order
                        </button>
                        
                        <!-- Security Notice -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-center text-sm text-gray-500">
                                <i class="fas fa-lock mr-2 text-green-500"></i>
                                <span>Secure checkout • Your information is protected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Apply coupon
function applyCoupon() {
    const couponCode = document.getElementById('couponCodeInput').value.trim();
    const messageDiv = document.getElementById('couponMessage');
    
    if (!couponCode) {
        messageDiv.className = 'text-red-600';
        messageDiv.innerHTML = 'Please enter a coupon code.';
        return;
    }
    
    // Add coupon to URL and reload
    const url = new URL(window.location.href);
    url.searchParams.set('coupon', couponCode);
    window.location.href = url.toString();
}

// Auto-save address for logged-in users
document.addEventListener('DOMContentLoaded', function() {
    const addressForm = document.querySelector('input[name="address_id"]');
    if (!addressForm) {
        // This is a new address form for guest or new address
        const saveAddressCheckbox = document.getElementById('save_address');
        if (saveAddressCheckbox) {
            // Auto-check for logged-in users
            saveAddressCheckbox.checked = true;
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>