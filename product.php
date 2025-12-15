<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . SITE_URL . '/shop.php');
    exit;
}

$product = get_product_by_slug($slug);
if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . SITE_URL . '/shop.php');
    exit;
}

$page_title = $product['name'];
$page_description = $product['short_description'] ?? $product['name'];
$breadcrumbs = [
    ['title' => 'Shop', 'url' => SITE_URL . '/shop.php'],
    ['title' => $product['category_name'], 'url' => SITE_URL . '/shop.php?category=' . $product['category_slug']],
    ['title' => $product['name']]
];

// Get related products
$related_products = db_fetch_all(
    "SELECT * FROM products 
     WHERE category_id = ? AND id != ? AND status = 'active' 
     ORDER BY RAND() LIMIT 4",
    [$product['category_id'], $product['id']],
    'ii'
);

// Check if product is in wishlist
$in_wishlist = false;
if (is_logged_in() && is_user()) {
    $in_wishlist = is_in_wishlist($_SESSION['user_id'], $product['id']);
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Product Details -->
<div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Product Images -->
        <div>
            <!-- Main Image -->
            <div class="bg-white rounded-xl shadow-lg p-4 mb-4">
                <img src="<?php echo htmlspecialchars(get_image_src($product['image_main'])); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     id="mainImage"
                     class="w-full h-64 md:h-96 object-contain rounded-lg">
            </div>
            
            <!-- Thumbnail Images -->
            <?php 
            $images = $product['images'] ? json_decode($product['images'], true) : [];
            if (!empty($images)): 
            ?>
                <div class="flex space-x-2 overflow-x-auto py-2">
                    <button onclick="changeImage(<?php echo json_encode(get_image_src($product['image_main'])); ?>)" 
                            class="flex-shrink-0 border-2 border-amber-500 rounded-lg overflow-hidden">
                        <img src="<?php echo htmlspecialchars(get_image_src($product['image_main'])); ?>" 
                             alt="Main" class="w-20 h-20 object-cover">
                    </button>
                    <?php foreach ($images as $image): ?>
                        <button onclick="changeImage(<?php echo json_encode(get_image_src($image)); ?>)" 
                                class="flex-shrink-0 border border-gray-300 rounded-lg overflow-hidden hover:border-amber-500">
                            <img src="<?php echo htmlspecialchars(get_image_src($image)); ?>" 
                                 alt="Product image" class="w-20 h-20 object-cover">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Info -->
        <div>
            <!-- Breadcrumb -->
            <div class="text-sm text-gray-500 mb-4">
                <a href="<?php echo SITE_URL; ?>/shop.php" class="hover:text-amber-600">Shop</a> / 
                <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo $product['category_slug']; ?>" class="hover:text-amber-600">
                    <?php echo $product['category_name']; ?>
                </a>
            </div>
            
            <!-- Product Name -->
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <!-- SKU -->
            <div class="text-gray-500 mb-4">
                SKU: <?php echo $product['sku'] ?: 'N/A'; ?>
            </div>
            
            <!-- Price -->
            <div class="mb-6">
                <div class="flex items-center">
                    <span class="text-3xl font-bold text-gray-900 mr-4">
                        <?php echo format_price($product['price']); ?>
                    </span>
                    
                    <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                        <?php $discount = calculate_discount_percentage($product['price'], $product['compare_price']); ?>
                        <span class="text-sm text-gray-500 line-through mr-4">
                            <?php echo format_price($product['compare_price']); ?>
                        </span>
                        <span class="bg-red-100 text-red-800 text-sm font-semibold px-2.5 py-0.5 rounded">
                            Save <?php echo $discount; ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Stock Status -->
            <div class="mb-6">
                <?php if ($product['stock_quantity'] > 10): ?>
                    <span class="text-green-600 font-medium">
                        <i class="fas fa-check-circle mr-2"></i> In Stock
                    </span>
                <?php elseif ($product['stock_quantity'] > 0): ?>
                    <span class="text-amber-600 font-medium">
                        <i class="fas fa-exclamation-circle mr-2"></i> Only <?php echo $product['stock_quantity']; ?> left in stock
                    </span>
                <?php else: ?>
                    <span class="text-red-600 font-medium">
                        <i class="fas fa-times-circle mr-2"></i> Out of Stock
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <div class="mb-6">
                <h3 class="font-semibold text-lg mb-2">Description</h3>
                <div class="text-gray-600">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
            
            <!-- Short Description -->
            <?php if ($product['short_description']): ?>
                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2">Highlights</h3>
                    <div class="text-gray-600">
                        <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Nutritional Info -->
            <?php if ($product['nutritional_info']): 
                $nutritional_info = json_decode($product['nutritional_info'], true);
                if ($nutritional_info): ?>
                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2">Nutritional Information</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <table class="w-full">
                            <?php foreach ($nutritional_info as $key => $value): ?>
                                <tr class="border-b border-gray-200">
                                    <td class="py-2 font-medium"><?php echo htmlspecialchars($key); ?></td>
                                    <td class="py-2 text-right"><?php echo htmlspecialchars($value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Weight -->
            <?php if ($product['weight']): ?>
                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2">Weight</h3>
                    <p class="text-gray-600"><?php echo $product['weight']; ?> grams</p>
                </div>
            <?php endif; ?>
            
            <!-- Add to Cart -->
            <div class="bg-gray-50 p-6 rounded-xl">
                <form action="<?php echo SITE_URL; ?>/cart.php" method="POST" id="addToCartForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <!-- Quantity -->
                    <div class="mb-4">
                        <label class="block font-medium text-gray-700 mb-2">Quantity</label>
                        <div class="flex items-center">
                            <button type="button" onclick="decreaseQuantity()" 
                                    class="bg-gray-200 text-gray-700 w-10 h-10 rounded-l-lg hover:bg-gray-300">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" 
                                   max="<?php echo $product['stock_quantity']; ?>" 
                                   class="w-16 h-10 text-center border-y border-gray-300">
                            <button type="button" onclick="increaseQuantity()" 
                                    class="bg-gray-200 text-gray-700 w-10 h-10 rounded-r-lg hover:bg-gray-300">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <button type="submit" 
                                    class="flex-1 bg-amber-600 text-white py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                                <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    class="flex-1 bg-gray-300 text-gray-500 py-3 rounded-lg cursor-not-allowed font-medium">
                                Out of Stock
                            </button>
                        <?php endif; ?>
                        
                        <!-- Wishlist Button -->
                        <?php if (is_logged_in() && is_user()): ?>
                            <?php if ($in_wishlist): ?>
                                <button type="button" onclick="removeFromWishlist()" 
                                        class="flex items-center justify-center bg-red-100 text-red-600 py-3 px-6 rounded-lg hover:bg-red-200 transition">
                                    <i class="fas fa-heart mr-2"></i> Remove from Wishlist
                                </button>
                            <?php else: ?>
                                <button type="button" onclick="addToWishlist()" 
                                        class="flex items-center justify-center bg-gray-100 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-200 transition">
                                    <i class="far fa-heart mr-2"></i> Add to Wishlist
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Buy Now Button -->
                <?php if ($product['stock_quantity'] > 0): ?>
                    <form action="<?php echo SITE_URL; ?>/checkout.php" method="POST" class="mt-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="direct_checkout" value="1">
                        <button type="submit" 
                                class="w-full bg-gray-900 text-white py-3 rounded-lg hover:bg-black transition font-medium">
                            <i class="fas fa-bolt mr-2"></i> Buy Now
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Additional Info -->
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center">
                    <i class="fas fa-shipping-fast text-amber-600 mr-3"></i>
                    <div>
                        <div class="font-medium">Free Shipping</div>
                        <div class="text-gray-500">On orders above ₹<?php echo get_setting('free_shipping_amount'); ?></div>
                    </div>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-undo-alt text-amber-600 mr-3"></i>
                    <div>
                        <div class="font-medium">Easy Returns</div>
                        <div class="text-gray-500">7-day return policy</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($related_products as $related): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $related['slug']; ?>">
                            <div class="relative h-40 overflow-hidden">
                                <img src="<?php echo $related['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                        </a>
                        <div class="p-4">
                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $related['slug']; ?>">
                                <h3 class="font-semibold text-gray-800 group-hover:text-amber-600 mb-2 truncate">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </h3>
                            </a>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-900">
                                    <?php echo format_price($related['price']); ?>
                                </span>
                                <form action="<?php echo SITE_URL; ?>/cart.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $related['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <?php if ($related['stock_quantity'] > 0): ?>
                                        <button type="submit" 
                                                class="text-amber-600 hover:text-amber-700">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Image gallery
function changeImage(src) {
    document.getElementById('mainImage').src = src;
}

// Quantity controls
function increaseQuantity() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantity');
    const min = parseInt(input.min);
    if (parseInt(input.value) > min) {
        input.value = parseInt(input.value) - 1;
    }
}

// Wishlist functions
function addToWishlist() {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_to_wishlist&product_id=<?php echo $product["id"]; ?>'
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

function removeFromWishlist() {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove_from_wishlist&product_id=<?php echo $product["id"]; ?>'
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>