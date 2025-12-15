<?php
$page_title = 'Home';
$page_description = 'Premium quality snacks and dry fruits online store';
require_once __DIR__ . '/includes/header.php';

// Get featured products
$featured_products = get_featured_products(8);
$categories = get_categories();
?>

<!-- Hero Section -->
<section class="mb-16">
    <div class="relative bg-gradient-to-r from-amber-700 to-amber-900 rounded-2xl overflow-hidden">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80" 
                 alt="Snacks Banner" class="w-full h-full object-cover opacity-30">
        </div>
        <div class="relative px-8 py-24 md:py-32 text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                Premium Snacks & Dry Fruits
            </h1>
            <p class="text-xl text-amber-100 mb-8 max-w-2xl mx-auto">
                Discover our curated collection of healthy snacks, delicious dry fruits, and gourmet treats. 
                Freshness guaranteed!
            </p>
            <a href="<?php echo SITE_URL; ?>/shop.php" 
               class="inline-block bg-white text-amber-700 px-8 py-3 rounded-lg font-semibold hover:bg-amber-50 transition transform hover:-translate-y-1">
                Shop Now <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="mb-16">
    <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Shop by Category</h2>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <?php foreach ($categories as $category): ?>
            <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo $category['slug']; ?>" 
               class="group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="h-32 overflow-hidden">
                    <div class="w-full h-full bg-gradient-to-br from-amber-100 to-amber-300 flex items-center justify-center">
                        <i class="fas fa-seedling text-4xl text-amber-700"></i>
                    </div>
                </div>
                <div class="p-4 text-center">
                    <h3 class="font-semibold text-gray-800 group-hover:text-amber-600"><?php echo $category['name']; ?></h3>
                    <p class="text-sm text-gray-500 mt-1">Explore Collection</p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Featured Products -->
<section class="mb-16">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Featured Products</h2>
        <a href="<?php echo SITE_URL; ?>/shop.php" class="text-amber-600 hover:text-amber-700 font-semibold">
            View All <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    
    <?php if ($featured_products): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ($featured_products as $product): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <!-- Product Image -->
                    <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo $product['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            
                            <!-- Discount Badge -->
                            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                <?php $discount = calculate_discount_percentage($product['price'], $product['compare_price']); ?>
                                <span class="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                    <?php echo $discount; ?>% OFF
                                </span>
                            <?php endif; ?>
                            
                            <!-- Out of Stock -->
                            <?php if ($product['stock_quantity'] <= 0): ?>
                                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                    <span class="text-white font-bold">Out of Stock</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    
                    <!-- Product Info -->
                    <div class="p-4">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                            <h3 class="font-semibold text-gray-800 group-hover:text-amber-600 mb-2 truncate">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                        </a>
                        
                        <!-- Price -->
                        <div class="flex items-center mb-3">
                            <span class="text-xl font-bold text-gray-900">
                                <?php echo format_price($product['price']); ?>
                            </span>
                            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                <span class="text-sm text-gray-500 line-through ml-2">
                                    <?php echo format_price($product['compare_price']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Add to Cart Button -->
                        <form action="<?php echo SITE_URL; ?>/cart.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <button type="submit" 
                                        class="w-full bg-amber-600 text-white py-2 rounded-lg hover:bg-amber-700 transition flex items-center justify-center">
                                    <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                                </button>
                            <?php else: ?>
                                <button type="button" 
                                        class="w-full bg-gray-300 text-gray-500 py-2 rounded-lg cursor-not-allowed">
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12 bg-gray-100 rounded-xl">
            <i class="fas fa-box-open text-5xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">No featured products available at the moment.</p>
        </div>
    <?php endif; ?>
</section>

<!-- Features Section -->
<section class="mb-16">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center">
            <div class="bg-amber-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shipping-fast text-2xl text-amber-600"></i>
            </div>
            <h3 class="font-semibold text-xl mb-2">Free Shipping</h3>
            <p class="text-gray-600">On orders above ₹<?php echo get_setting('free_shipping_amount'); ?></p>
        </div>
        
        <div class="text-center">
            <div class="bg-amber-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-undo-alt text-2xl text-amber-600"></i>
            </div>
            <h3 class="font-semibold text-xl mb-2">Easy Returns</h3>
            <p class="text-gray-600">7-day return policy for all products</p>
        </div>
        
        <div class="text-center">
            <div class="bg-amber-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-2xl text-amber-600"></i>
            </div>
            <h3 class="font-semibold text-xl mb-2">Quality Guaranteed</h3>
            <p class="text-gray-600">Premium quality products with freshness guarantee</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>