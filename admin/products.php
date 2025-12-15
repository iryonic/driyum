<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Manage Products';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Products']
];

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    
    switch ($action) {
        case 'delete':
            // Get product image for deletion
            $product = db_fetch_single("SELECT image_main, images FROM products WHERE id = ?", [$product_id], 'i');
            if ($product) {
                // Delete main image
                if ($product['image_main']) {
                    delete_image_file($product['image_main']);
                }
                
                // Delete additional images
                if ($product['images']) {
                    $images = json_decode($product['images'], true);
                    foreach ($images as $image) {
                        delete_image_file($image);
                    }
                }
                
                // Delete product
                db_query("DELETE FROM products WHERE id = ?", [$product_id], 'i');
                $_SESSION['success'] = 'Product deleted successfully.';
            }
            break;
            
        case 'toggle_status':
            $current = db_fetch_single("SELECT status FROM products WHERE id = ?", [$product_id], 'i');
            if ($current) {
                $new_status = $current['status'] === 'active' ? 'inactive' : 'active';
                db_query("UPDATE products SET status = ? WHERE id = ?", [$new_status, $product_id], 'si');
                $_SESSION['success'] = 'Product status updated.';
            }
            break;
            
        case 'toggle_featured':
            $current = db_fetch_single("SELECT is_featured FROM products WHERE id = ?", [$product_id], 'i');
            if ($current) {
                $new_featured = $current['is_featured'] ? 0 : 1;
                db_query("UPDATE products SET is_featured = ? WHERE id = ?", [$new_featured, $product_id], 'ii');
                $_SESSION['success'] = 'Featured status updated.';
            }
            break;
    }
    
    header('Location: ' . ADMIN_URL . '/products.php');
    exit;
}

// Get products with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = "1=1";
$params = [];
$types = '';

// Filter by status
if (isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive', 'out_of_stock'])) {
    $where .= " AND p.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Filter by category
if (isset($_GET['category']) && intval($_GET['category']) > 0) {
    $where .= " AND p.category_id = ?";
    $params[] = intval($_GET['category']);
    $types .= 'i';
}

// Filter by search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $search_term = "%{$_GET['search']}%";
    $params = array_merge($params, [$search_term, $search_term]);
    $types .= 'ss';
}

// Get products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$products = db_fetch_all($sql, $params, $types);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where";
$count_params = array_slice($params, 0, -2);
$count_types = substr($types, 0, -2);
$count_result = db_fetch_single($count_sql, $count_params, $count_types);
$total_products = $count_result['total'] ?? 0;

// Get categories for filter
$categories = db_fetch_all("SELECT id, name FROM categories ORDER BY name");

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Products</h1>
            <p class="text-gray-600">Total Products: <?php echo $total_products; ?></p>
        </div>
        <a href="<?php echo ADMIN_URL; ?>/add-product.php" 
           class="mt-4 md:mt-0 bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition font-medium">
            <i class="fas fa-plus mr-2"></i> Add New Product
        </a>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>" 
                       placeholder="Name or SKU" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">All Status</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="out_of_stock" <?php echo (isset($_GET['status']) && $_GET['status'] == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            
            <!-- Buttons -->
            <div class="flex items-end space-x-2">
                <button type="submit" 
                        class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="<?php echo ADMIN_URL; ?>/products.php" 
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Products Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <?php if ($products): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                SKU
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-12 w-12 flex-shrink-0">
                                            <img src="<?php echo $product['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="h-12 w-12 object-cover rounded-lg">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>" 
                                                   class="hover:text-amber-600" target="_blank">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </a>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php if ($product['is_featured']): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                        <i class="fas fa-star mr-1"></i> Featured
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-mono"><?php echo $product['sku']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $product['category_name'] ?: 'Uncategorized'; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo format_price($product['price']); ?></div>
                                    <?php if ($product['compare_price']): ?>
                                        <div class="text-xs text-gray-500 line-through"><?php echo format_price($product['compare_price']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm <?php echo $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'text-amber-600 font-medium' : 'text-gray-900'; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo get_product_status_badge($product['status']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?php echo ADMIN_URL; ?>/edit-product.php?id=<?php echo $product['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-700" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Toggle featured status?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_featured">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" 
                                                    class="text-purple-600 hover:text-purple-700" 
                                                    title="<?php echo $product['is_featured'] ? 'Remove from featured' : 'Mark as featured'; ?>">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Toggle product status?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" 
                                                    class="text-amber-600 hover:text-amber-700" 
                                                    title="Toggle Status">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_products > $limit): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo min($offset + 1, $total_products); ?> to <?php echo min($offset + $limit, $total_products); ?> of <?php echo $total_products; ?> products
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <?php
                                $prev_url = ADMIN_URL . '/products.php?page=' . ($page - 1);
                                if (isset($_GET['search'])) $prev_url .= '&search=' . urlencode($_GET['search']);
                                if (isset($_GET['category'])) $prev_url .= '&category=' . $_GET['category'];
                                if (isset($_GET['status'])) $prev_url .= '&status=' . $_GET['status'];
                                ?>
                                <a href="<?php echo $prev_url; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < ceil($total_products / $limit)): ?>
                                <?php
                                $next_url = ADMIN_URL . '/products.php?page=' . ($page + 1);
                                if (isset($_GET['search'])) $next_url .= '&search=' . urlencode($_GET['search']);
                                if (isset($_GET['category'])) $next_url .= '&category=' . $_GET['category'];
                                if (isset($_GET['status'])) $next_url .= '&status=' . $_GET['status'];
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
                <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-700 mb-2">No products found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your filters or add new products.</p>
                <a href="<?php echo ADMIN_URL; ?>/add-product.php" 
                   class="inline-block bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                    Add New Product
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function for product status badge
function get_product_status_badge($status) {
    $badges = [
        'active' => [
            'class' => 'bg-green-100 text-green-800',
            'icon' => 'fas fa-check-circle',
            'text' => 'Active'
        ],
        'inactive' => [
            'class' => 'bg-gray-100 text-gray-800',
            'icon' => 'fas fa-times-circle',
            'text' => 'Inactive'
        ],
        'out_of_stock' => [
            'class' => 'bg-red-100 text-red-800',
            'icon' => 'fas fa-exclamation-triangle',
            'text' => 'Out of Stock'
        ]
    ];
    
    $badge = $badges[$status] ?? $badges['inactive'];
    
    return sprintf(
        '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium %s">
            <i class="%s mr-1"></i> %s
        </span>',
        $badge['class'],
        $badge['icon'],
        $badge['text']
    );
}

require_once __DIR__ . '/../includes/admin-footer.php';
?>