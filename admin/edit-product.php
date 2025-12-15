<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Edit Product';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Products', 'url' => ADMIN_URL . '/products.php'],
    ['title' => 'Edit Product']
];

$product_id = intval($_GET['id'] ?? 0);
if (!$product_id) {
    header('Location: ' . ADMIN_URL . '/products.php');
    exit;
}

// Get product data
$product = get_product($product_id);
if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . ADMIN_URL . '/products.php');
    exit;
}

// Get categories for dropdown
$categories = db_fetch_all("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $name = sanitize_input($_POST['name'] ?? '');
    $slug = generate_slug($_POST['slug'] ?? $name);
    $description = sanitize_input($_POST['description'] ?? '');
    $short_description = sanitize_input($_POST['short_description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $sku = sanitize_input($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $compare_price = floatval($_POST['compare_price'] ?? 0);
    $cost = floatval($_POST['cost'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $low_stock_threshold = intval($_POST['low_stock_threshold'] ?? 10);
    $weight = floatval($_POST['weight'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = sanitize_input($_POST['status'] ?? 'active');
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Product name is required.';
    }
    
    if (empty($price) || $price <= 0) {
        $errors[] = 'Valid price is required.';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }
    
    // Check if SKU already exists (excluding current product)
    if (!empty($sku)) {
        $existing = db_fetch_single(
            "SELECT id FROM products WHERE sku = ? AND id != ?",
            [$sku, $product_id],
            'si'
        );
        if ($existing) {
            $errors[] = 'SKU already exists. Please use a different SKU.';
        }
    }
    
    // Check if slug already exists (excluding current product)
    $existing_slug = db_fetch_single(
        "SELECT id FROM products WHERE slug = ? AND id != ?",
        [$slug, $product_id],
        'si'
    );
    if ($existing_slug) {
        $slug .= '-' . time(); // Append timestamp to make it unique
    }
    
    if (empty($errors)) {
        // Handle main image upload/update
        $image_main = $product['image_main'];
        if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] === UPLOAD_ERR_OK) {
            // Delete old image if exists
            if ($image_main) {
                delete_image_file($image_main);
            }
            
            $upload_result = upload_image($_FILES['image_main'], 'product');
            if ($upload_result['success']) {
                $image_main = $upload_result['path'];
            } else {
                $errors[] = $upload_result['message'];
            }
        } elseif (isset($_POST['remove_image_main'])) {
            // Remove main image
            if ($image_main) {
                delete_image_file($image_main);
                $image_main = '';
            }
        }
        
        // Handle additional images
        $current_images = $product['images'] ? json_decode($product['images'], true) : [];
        $additional_images = $current_images;
        
        // Handle removal of existing images
        if (isset($_POST['remove_images']) && is_array($_POST['remove_images'])) {
            foreach ($_POST['remove_images'] as $image_to_remove) {
                if (($key = array_search($image_to_remove, $additional_images)) !== false) {
                    delete_image_file($image_to_remove);
                    unset($additional_images[$key]);
                }
            }
            $additional_images = array_values($additional_images); // Reindex array
        }
        
        // Handle new additional images upload
        if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
            for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['additional_images']['name'][$i],
                        'type' => $_FILES['additional_images']['type'][$i],
                        'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                        'error' => $_FILES['additional_images']['error'][$i],
                        'size' => $_FILES['additional_images']['size'][$i]
                    ];
                    
                    $upload_result = upload_image($file, 'product');
                    if ($upload_result['success']) {
                        $additional_images[] = $upload_result['path'];
                    }
                }
            }
        }
        
        // Handle nutritional info
        $nutritional_info = [];
        if (isset($_POST['nutrition_key']) && is_array($_POST['nutrition_key'])) {
            for ($i = 0; $i < count($_POST['nutrition_key']); $i++) {
                $key = sanitize_input($_POST['nutrition_key'][$i] ?? '');
                $value = sanitize_input($_POST['nutrition_value'][$i] ?? '');
                if (!empty($key) && !empty($value)) {
                    $nutritional_info[$key] = $value;
                }
            }
        }
        
        if (empty($errors)) {
            // Update product
            $sql = "UPDATE products SET 
                    name = ?, slug = ?, description = ?, short_description = ?, category_id = ?,
                    sku = ?, price = ?, compare_price = ?, cost = ?, stock_quantity = ?, 
                    low_stock_threshold = ?, weight = ?, image_main = ?, images = ?, 
                    nutritional_info = ?, is_featured = ?, status = ?, updated_at = NOW()
                    WHERE id = ?";
            
            $images_json = !empty($additional_images) ? json_encode($additional_images) : null;
            $nutritional_json = !empty($nutritional_info) ? json_encode($nutritional_info) : null;
            
            $result = db_query($sql, [
                $name, $slug, $description, $short_description, $category_id,
                $sku, $price, $compare_price, $cost, $stock_quantity, $low_stock_threshold, $weight,
                $image_main, $images_json, $nutritional_json, $is_featured, $status, $product_id
            ], 'ssssisdiddiissssisi');
            
            if ($result) {
                // Log admin activity
                log_admin_activity($_SESSION['user_id'], 'edit_product', "Updated product: {$name} (ID: {$product_id})");
                
                $_SESSION['success'] = 'Product updated successfully.';
                header('Location: ' . ADMIN_URL . '/products.php');
                exit;
            } else {
                $errors[] = 'Failed to update product. Please try again.';
            }
        }
    }
}

// Parse existing data for form
$current_images = $product['images'] ? json_decode($product['images'], true) : [];
$nutritional_info = $product['nutritional_info'] ? json_decode($product['nutritional_info'], true) : [];

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Edit Product</h1>
        <p class="text-gray-600">Update the product details below</p>
    </div>
    
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Product Form -->
    <form method="POST" action="<?php echo ADMIN_URL; ?>/edit-product.php?id=<?php echo $product_id; ?>" enctype="multipart/form-data" class="space-y-8">
        <?php echo csrf_field(); ?>
        
        <!-- Basic Information -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Basic Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product Name -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo htmlspecialchars($product['name']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                           oninput="document.getElementById('slug').value = generateSlug(this.value)">
                </div>
                
                <!-- Slug -->
                <div class="md:col-span-2">
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">URL Slug *</label>
                    <input type="text" id="slug" name="slug" required
                           value="<?php echo htmlspecialchars($product['slug']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">URL-friendly version of the name</p>
                </div>
                
                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <select id="category_id" name="category_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- SKU -->
                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">SKU (Stock Keeping Unit)</label>
                    <input type="text" id="sku" name="sku"
                           value="<?php echo htmlspecialchars($product['sku']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select id="status" name="status" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="out_of_stock" <?php echo $product['status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                
                <!-- Featured -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Featured Product</label>
                    <div class="flex items-center">
                        <input type="checkbox" id="is_featured" name="is_featured" 
                               class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                               <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                        <label for="is_featured" class="ml-2 text-gray-700">
                            Show this product as featured
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pricing -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Pricing</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price (₹) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required
                           value="<?php echo htmlspecialchars($product['price']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <!-- Compare Price -->
                <div>
                    <label for="compare_price" class="block text-sm font-medium text-gray-700 mb-2">Compare at Price (₹)</label>
                    <input type="number" id="compare_price" name="compare_price" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($product['compare_price'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <!-- Cost -->
                <div>
                    <label for="cost" class="block text-sm font-medium text-gray-700 mb-2">Cost (₹)</label>
                    <input type="number" id="cost" name="cost" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($product['cost'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
            </div>
        </div>
        
        <!-- Inventory -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Inventory</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stock Quantity -->
                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" required
                           value="<?php echo htmlspecialchars($product['stock_quantity']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <!-- Low Stock Threshold -->
                <div>
                    <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-2">Low Stock Threshold</label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="1"
                           value="<?php echo htmlspecialchars($product['low_stock_threshold']); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <!-- Weight -->
                <div>
                    <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">Weight (grams)</label>
                    <input type="number" id="weight" name="weight" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($product['weight'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
            </div>
        </div>
        
        <!-- Descriptions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Descriptions</h2>
            
            <div class="space-y-6">
                <!-- Short Description -->
                <div>
                    <label for="short_description" class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Full Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Full Description *</label>
                    <textarea id="description" name="description" rows="6" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Images -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Images</h2>
            
            <div class="space-y-6">
                <!-- Main Image -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Main Image *</label>
                    
                    <?php if ($product['image_main']): ?>
                        <div class="flex items-start space-x-4 mb-4">
                            <div class="w-32 h-32">
                                <img src="<?php echo $product['image_main']; ?>" 
                                     alt="Main Product Image"
                                     class="w-full h-full object-cover rounded-lg">
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-600 mb-2">Current main image</p>
                                <div class="flex items-center space-x-2">
                                    <label class="cursor-pointer bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition">
                                        <i class="fas fa-upload mr-2"></i> Change Image
                                        <input type="file" name="image_main" accept="image/*" class="hidden">
                                    </label>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="remove_image_main" name="remove_image_main"
                                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                        <label for="remove_image_main" class="ml-2 text-sm text-red-600">
                                            Remove Image
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50">
                            <div class="text-center">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-xs text-gray-500">No main image</p>
                            </div>
                            <input type="file" name="image_main" accept="image/*" required
                                   class="opacity-0 absolute w-32 h-32 cursor-pointer">
                        </div>
                    <?php endif; ?>
                    <p class="text-sm text-gray-500 mt-2">Recommended size: 800x800px. Max 5MB.</p>
                </div>
                
                <!-- Additional Images -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Images</label>
                    
                    <!-- Current Images -->
                    <?php if (!empty($current_images)): ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <?php foreach ($current_images as $index => $image): ?>
                                <div class="relative group">
                                    <img src="<?php echo $image; ?>" 
                                         alt="Additional Image <?php echo $index + 1; ?>"
                                         class="w-full h-32 object-cover rounded-lg">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                        <div class="flex items-center space-x-2">
                                            <input type="checkbox" name="remove_images[]" value="<?php echo $image; ?>"
                                                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                            <span class="text-white text-sm">Remove</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- New Images Upload -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="additionalImagesContainer">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="w-full h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50 relative">
                                <div class="text-center">
                                    <i class="fas fa-plus text-2xl text-gray-400"></i>
                                    <p class="text-xs text-gray-500 mt-1">Add Image</p>
                                </div>
                                <input type="file" name="additional_images[]" accept="image/*"
                                       class="opacity-0 absolute w-full h-full cursor-pointer">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Add up to 4 additional images. Total images cannot exceed 5.</p>
                </div>
            </div>
        </div>
        
        <!-- Nutritional Information -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Nutritional Information</h2>
            
            <div id="nutritionFields">
                <?php if (!empty($nutritional_info)): ?>
                    <?php foreach ($nutritional_info as $key => $value): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <input type="text" name="nutrition_key[]" 
                                       value="<?php echo htmlspecialchars($key); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <input type="text" name="nutrition_value[]" 
                                       value="<?php echo htmlspecialchars($value); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div class="flex space-x-2">
                                <button type="button" onclick="addNutritionField()" 
                                        class="flex-1 bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button type="button" onclick="removeNutritionField(this)" 
                                        class="flex-1 bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <input type="text" name="nutrition_key[]" 
                                   placeholder="e.g., Calories"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <input type="text" name="nutrition_value[]" 
                                   placeholder="e.g., 100 kcal"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <button type="button" onclick="addNutritionField()" 
                                    class="w-full bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200">
                                <i class="fas fa-plus mr-2"></i> Add More
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <p class="text-sm text-gray-500">Add nutritional facts for your product (optional).</p>
        </div>
        
        <!-- Form Actions -->
        <div class="flex justify-end space-x-4">
            <a href="<?php echo ADMIN_URL; ?>/products.php" 
               class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" 
                    class="px-8 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition font-medium">
                Update Product
            </button>
        </div>
    </form>
</div>

<script>
// Generate slug from product name
function generateSlug(text) {
    return text.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Add more nutritional fields
function addNutritionField() {
    const container = document.getElementById('nutritionFields');
    const newRow = document.createElement('div');
    newRow.className = 'grid grid-cols-1 md:grid-cols-3 gap-4 mb-4';
    newRow.innerHTML = `
        <div>
            <input type="text" name="nutrition_key[]" 
                   placeholder="e.g., Protein"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>
        <div>
            <input type="text" name="nutrition_value[]" 
                   placeholder="e.g., 10g"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>
        <div class="flex space-x-2">
            <button type="button" onclick="addNutritionField()" 
                    class="flex-1 bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200">
                <i class="fas fa-plus"></i>
            </button>
            <button type="button" onclick="removeNutritionField(this)" 
                    class="flex-1 bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
}

// Remove nutritional field
function removeNutritionField(button) {
    button.closest('.grid').remove();
}

// Validate form before submission
document.querySelector('form').addEventListener('submit', function(e) {
    const price = document.getElementById('price').value;
    if (parseFloat(price) <= 0) {
        e.preventDefault();
        alert('Price must be greater than 0.');
        return false;
    }
    
    const stock = document.getElementById('stock_quantity').value;
    if (parseInt(stock) < 0) {
        e.preventDefault();
        alert('Stock quantity cannot be negative.');
        return false;
    }
    
    return true;
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>