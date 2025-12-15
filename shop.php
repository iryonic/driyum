<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Shop';
$page_description = 'Browse our collection of premium snacks and dry fruits';
$breadcrumbs = [
    ['title' => 'Shop']
];

// Get query parameters
$category_slug = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$where = "p.status = 'active'";
$params = [];
$types = '';

// Filter by category
if ($category_slug) {
    $category = db_fetch_single("SELECT id, name FROM categories WHERE slug = ?", [$category_slug], 's');
    if ($category) {
        $where .= " AND p.category_id = ?";
        $params[] = $category['id'];
        $types .= 'i';
        $page_title = $category['name'];
        $breadcrumbs = [
            ['title' => 'Shop', 'url' => SITE_URL . '/shop.php'],
            ['title' => $category['name']]
        ];
    }
}

// Filter by search
if ($search) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= str_repeat('s', 3);
    $page_title = "Search: $search";
    $breadcrumbs = [
    ['title' => 'Shop', 'url' => SITE_URL . '/shop.php'],
    ['title' => "Search: $search"]
];
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where";
$count_result = db_fetch_single($count_sql, $params, $types);
$total_products = $count_result['total'] ?? 0;

// Get products
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$products = db_fetch_all($sql, $params, $types);

// Get categories for sidebar
$categories = get_categories();

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col lg:flex-row gap-8">
    <!-- Sidebar Filters -->
    <div class="lg:w-1/4">
        <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
            <!-- Categories -->
            <div class="mb-8">
                <h3 class="font-bold text-lg mb-4 text-gray-800">Categories</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo SITE_URL; ?>/shop.php" 
                           class="block py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-700 <?php echo !$category_slug ? 'bg-amber-50 text-amber-700 font-medium' : 'text-gray-600'; ?>">
                            All Products
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo $cat['slug']; ?>" 
                               class="block py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-700 <?php echo $category_slug == $cat['slug'] ? 'bg-amber-50 text-amber-700 font-medium' : 'text-gray-600'; ?>">
                                <?php echo $cat['name']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Search -->
            <div class="mb-8">
                <h3 class="font-bold text-lg mb-4 text-gray-800">Search</h3>
                <form method="GET" action="<?php echo SITE_URL; ?>/shop.php">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search products..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-amber-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Price Range -->
            <div>
                <h3 class="font-bold text-lg mb-4 text-gray-800">Price Range</h3>
                <form method="GET" action="<?php echo SITE_URL; ?>/shop.php">
                    <?php if ($category_slug): ?>
                        <input type="hidden" name="category" value="<?php echo $category_slug; ?>">
                    <?php endif; ?>
                    <?php if ($search): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Min: ₹<span id="minPrice">0</span></span>
                            <span class="text-gray-600">Max: ₹<span id="maxPrice">5000</span></span>
                        </div>
                        <input type="range" id="priceRange" min="0" max="5000" value="5000" 
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        <button type="button" onclick="filterByPrice()" 
                                class="w-full bg-amber-600 text-white py-2 rounded-lg hover:bg-amber-700 transition">
                            Filter by Price
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="lg:w-3/4">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo $page_title; ?></h1>
                <p class="text-gray-600"><?php echo $total_products; ?> products found</p>
            </div>
            
            <!-- Sort Options -->
            <div class="mt-4 md:mt-0">
                <select id="sortProducts" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="newest">Newest First</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="name">Name A-Z</option>
                </select>
            </div>
        </div>
        
        <!-- Products Grid -->
        <?php if ($products): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($products as $product): ?>
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
                            </div>
                        </a>
                        
                        <!-- Product Info -->
                        <div class="p-4">
                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                                <h3 class="font-semibold text-gray-800 group-hover:text-amber-600 mb-2 truncate">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                            </a>
                            
                            <!-- Category -->
                            <div class="mb-2">
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                    <?php echo $product['category_name']; ?>
                                </span>
                            </div>
                            
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
                            
                            <!-- Stock Status -->
                            <div class="mb-3">
                                <?php if ($product['stock_quantity'] > 10): ?>
                                    <span class="text-green-600 text-sm">
                                        <i class="fas fa-check-circle mr-1"></i> In Stock
                                    </span>
                                <?php elseif ($product['stock_quantity'] > 0): ?>
                                    <span class="text-amber-600 text-sm">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Only <?php echo $product['stock_quantity']; ?> left
                                    </span>
                                <?php else: ?>
                                    <span class="text-red-600 text-sm">
                                        <i class="fas fa-times-circle mr-1"></i> Out of Stock
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
            
            <!-- Pagination -->
            <?php if ($total_products > $limit): ?>
                <div class="flex justify-center mt-8">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo SITE_URL; ?>/shop.php?page=<?php echo $page - 1; ?><?php echo $category_slug ? '&category=' . $category_slug : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= ceil($total_products / $limit); $i++): ?>
                            <?php if ($i == 1 || $i == ceil($total_products / $limit) || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="<?php echo SITE_URL; ?>/shop.php?page=<?php echo $i; ?><?php echo $category_slug ? '&category=' . $category_slug : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg <?php echo $i == $page ? 'bg-amber-600 text-white border-amber-600' : 'hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="px-3 py-2">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < ceil($total_products / $limit)): ?>
                            <a href="<?php echo SITE_URL; ?>/shop.php?page=<?php echo $page + 1; ?><?php echo $category_slug ? '&category=' . $category_slug : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-16 bg-gray-100 rounded-xl">
                <i class="fas fa-search text-5xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No products found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your search or filter criteria</p>
                <a href="<?php echo SITE_URL; ?>/shop.php" class="bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                    Browse All Products
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Price range filter
const priceRange = document.getElementById('priceRange');
const maxPrice = document.getElementById('maxPrice');

priceRange.addEventListener('input', function() {
    maxPrice.textContent = this.value;
});

function filterByPrice() {
    const maxPriceValue = priceRange.value;
    let url = '<?php echo SITE_URL; ?>/shop.php?max_price=' + maxPriceValue;
    
    <?php if ($category_slug): ?>
        url += '&category=<?php echo $category_slug; ?>';
    <?php endif; ?>
    
    <?php if ($search): ?>
        url += '&search=<?php echo urlencode($search); ?>';
    <?php endif; ?>
    
    window.location.href = url;
}

// Sort products
document.getElementById('sortProducts').addEventListener('change', function() {
    let url = '<?php echo SITE_URL; ?>/shop.php?sort=' + this.value;
    
    <?php if ($category_slug): ?>
        url += '&category=<?php echo $category_slug; ?>';
    <?php endif; ?>
    
    <?php if ($search): ?>
        url += '&search=<?php echo urlencode($search); ?>';
    <?php endif; ?>
    
    <?php if ($page > 1): ?>
        url += '&page=<?php echo $page; ?>';
    <?php endif; ?>
    
    window.location.href = url;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>