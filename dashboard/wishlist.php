<?php
require_once __DIR__ . '/../includes/auth.php';
require_user();

$page_title = 'My Wishlist';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => SITE_URL . '/dashboard/'],
    ['title' => 'My Wishlist']
];

// Handle wishlist actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    
    switch ($action) {
        case 'remove':
            if (remove_from_wishlist($_SESSION['user_id'], $product_id)) {
                $_SESSION['success'] = 'Item removed from wishlist.';
            }
            break;
            
        case 'move_to_cart':
            $product = get_product($product_id);
            if ($product && $product['stock_quantity'] > 0) {
                add_to_cart($product_id, 1);
                remove_from_wishlist($_SESSION['user_id'], $product_id);
                $_SESSION['success'] = 'Item moved to cart.';
            } else {
                $_SESSION['error'] = 'Product is out of stock.';
            }
            break;
            
        case 'clear':
            db_query("DELETE FROM wishlist WHERE user_id = ?", [$_SESSION['user_id']], 'i');
            $_SESSION['success'] = 'Wishlist cleared.';
            break;
    }
    
    header('Location: ' . SITE_URL . '/dashboard/wishlist.php');
    exit;
}

// Get wishlist items
$wishlist_items = get_wishlist($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">My Wishlist</h1>
            <p class="text-gray-600">
                <?php echo count($wishlist_items); ?> item(s) saved
                <?php if (count($wishlist_items) > 0): ?>
                    • <span class="text-amber-600">Save items you love for later</span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (!empty($wishlist_items)): ?>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <button type="button" onclick="clearWishlist()" 
                        class="bg-red-100 text-red-600 px-4 py-2 rounded-lg hover:bg-red-200 transition">
                    <i class="fas fa-trash-alt mr-2"></i> Clear All
                </button>
                <a href="<?php echo SITE_URL; ?>/shop.php" 
                   class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700 transition">
                    <i class="fas fa-shopping-bag mr-2"></i> Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($wishlist_items): ?>
        <!-- Wishlist Items -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="overflow-x-auto table-responsive">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($wishlist_items as $item): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-16 w-16 flex-shrink-0">
                                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>">
                                                <img src="<?php echo $item['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="h-16 w-16 object-cover rounded-lg">
                                            </a>
                                        </div>
                                        <div class="ml-4">
                                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>" 
                                               class="font-medium text-gray-800 hover:text-amber-600">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                            <?php if ($item['compare_price'] && $item['compare_price'] > $item['price']): ?>
                                                <div class="text-sm text-gray-500 line-through mt-1">
                                                    <?php echo format_price($item['compare_price']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-lg font-bold text-gray-900">
                                        <?php echo format_price($item['price']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($item['stock_quantity'] > 10): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> In Stock
                                        </span>
                                    <?php elseif ($item['stock_quantity'] > 0): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-exclamation-circle mr-1"></i> Low Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i> Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <?php if ($item['stock_quantity'] > 0): ?>
                                            <form method="POST" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="move_to_cart">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <button type="submit" 
                                                        class="text-amber-600 hover:text-amber-700 font-medium">
                                                    <i class="fas fa-shopping-cart mr-1"></i> Add to Cart
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-700">
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
        </div>
        
        <!-- Wishlist Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Price Summary -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Price Summary</h3>
                <?php
                $total_price = 0;
                $total_items = 0;
                $in_stock_items = 0;
                
                foreach ($wishlist_items as $item) {
                    $total_price += $item['price'];
                    $total_items++;
                    if ($item['stock_quantity'] > 0) {
                        $in_stock_items++;
                    }
                }
                ?>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Items</span>
                        <span class="font-medium"><?php echo $total_items; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">In Stock Items</span>
                        <span class="font-medium text-green-600"><?php echo $in_stock_items; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Value</span>
                        <span class="font-bold text-lg text-amber-600"><?php echo format_price($total_price); ?></span>
                    </div>
                </div>
                
                <?php if ($in_stock_items > 0): ?>
                    <form method="POST" class="mt-6">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="move_all_to_cart">
                        <button type="button" onclick="moveAllToCart()" 
                                class="w-full bg-amber-600 text-white py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                            <i class="fas fa-shopping-cart mr-2"></i> Add All to Cart
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Recommendations -->
            <div class="md:col-span-2 bg-white rounded-xl shadow-md p-6">
                <h3 class="font-semibold text-gray-800 mb-4">You Might Also Like</h3>
                
                <?php
                // Get random products (excluding wishlist items)
                $product_ids = array_column($wishlist_items, 'product_id');
                $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                
                $recommendations = db_fetch_all(
                    "SELECT * FROM products 
                     WHERE status = 'active' 
                     AND id NOT IN ($placeholders)
                     ORDER BY RAND() LIMIT 3",
                    $product_ids,
                    str_repeat('i', count($product_ids))
                );
                ?>
                
                <?php if ($recommendations): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <?php foreach ($recommendations as $product): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-amber-500 transition">
                                <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                                    <img src="<?php echo $product['image_main'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-full h-32 object-cover rounded mb-3">
                                </a>
                                <h4 class="font-medium text-gray-800 truncate">
                                    <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>" 
                                       class="hover:text-amber-600">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h4>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-bold text-amber-600"><?php echo format_price($product['price']); ?></span>
                                    <form method="POST" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="add_to_wishlist">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-500">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No recommendations available.</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Empty Wishlist -->
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-heart text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-medium text-gray-700 mb-2">Your wishlist is empty</h3>
            <p class="text-gray-500 mb-6">Save items you love for later. They'll show up here.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="<?php echo SITE_URL; ?>/shop.php" 
                   class="bg-amber-600 text-white px-8 py-3 rounded-lg hover:bg-amber-700 transition">
                    <i class="fas fa-shopping-bag mr-2"></i> Start Shopping
                </a>
                <a href="<?php echo SITE_URL; ?>/shop.php?filter=featured" 
                   class="bg-white text-amber-600 border border-amber-600 px-8 py-3 rounded-lg hover:bg-amber-50 transition">
                    <i class="fas fa-star mr-2"></i> View Featured Products
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function clearWishlist() {
    if (confirm('Are you sure you want to clear your entire wishlist?')) {
        fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_wishlist'
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

function moveAllToCart() {
    if (confirm('Move all in-stock items to cart?')) {
        fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=move_all_to_cart'
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>