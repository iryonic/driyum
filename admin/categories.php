<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Manage Categories';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Categories']
];

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    $category_id = intval($_POST['category_id'] ?? 0);
    
    switch ($action) {
        case 'add':
            $name = sanitize_input($_POST['name'] ?? '');
            $slug = generate_slug($_POST['slug'] ?? $name);
            $description = sanitize_input($_POST['description'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $status = sanitize_input($_POST['status'] ?? 'active');
            
            // Check if slug exists
            $existing = db_fetch_single("SELECT id FROM categories WHERE slug = ?", [$slug], 's');
            if ($existing) {
                $_SESSION['error'] = 'Category slug already exists.';
            } else {
                // Handle image upload
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_image($_FILES['image'], 'category');
                    if ($upload_result['success']) {
                        $image = $upload_result['path'];
                    }
                }
                
                db_query(
                    "INSERT INTO categories (name, slug, description, parent_id, image, status) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$name, $slug, $description, $parent_id ?: null, $image, $status],
                    'sssiss'
                );
                
                $_SESSION['success'] = 'Category added successfully.';
            }
            break;
            
        case 'update':
            $name = sanitize_input($_POST['name'] ?? '');
            $slug = generate_slug($_POST['slug'] ?? $name);
            $description = sanitize_input($_POST['description'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $status = sanitize_input($_POST['status'] ?? 'active');
            
            // Check if slug exists (excluding current category)
            $existing = db_fetch_single(
                "SELECT id FROM categories WHERE slug = ? AND id != ?",
                [$slug, $category_id],
                'si'
            );
            
            if ($existing) {
                $_SESSION['error'] = 'Category slug already exists.';
            } else {
                // Get current image
                $current = db_fetch_single("SELECT image FROM categories WHERE id = ?", [$category_id], 'i');
                $image = $current['image'] ?? '';
                
                // Handle image upload/removal
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    // Delete old image if exists
                    if ($image) {
                        delete_image_file($image);
                    }
                    
                    $upload_result = upload_image($_FILES['image'], 'category');
                    if ($upload_result['success']) {
                        $image = $upload_result['path'];
                    }
                } elseif (isset($_POST['remove_image'])) {
                    // Remove image
                    if ($image) {
                        delete_image_file($image);
                        $image = '';
                    }
                }
                
                db_query(
                    "UPDATE categories SET 
                     name = ?, slug = ?, description = ?, parent_id = ?, image = ?, status = ?
                     WHERE id = ?",
                    [$name, $slug, $description, $parent_id ?: null, $image, $status, $category_id],
                    'sssissi'
                );
                
                $_SESSION['success'] = 'Category updated successfully.';
            }
            break;
            
        case 'delete':
            // Check if category has products
            $product_count = db_fetch_single(
                "SELECT COUNT(*) as count FROM products WHERE category_id = ?",
                [$category_id],
                'i'
            )['count'] ?? 0;
            
            // Check if category has subcategories
            $subcategory_count = db_fetch_single(
                "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?",
                [$category_id],
                'i'
            )['count'] ?? 0;
            
            if ($product_count > 0 || $subcategory_count > 0) {
                $_SESSION['error'] = 'Cannot delete category with products or subcategories.';
            } else {
                // Delete category image if exists
                $category = db_fetch_single("SELECT image FROM categories WHERE id = ?", [$category_id], 'i');
                if ($category && $category['image']) {
                    delete_image_file($category['image']);
                }
                
                db_query("DELETE FROM categories WHERE id = ?", [$category_id], 'i');
                $_SESSION['success'] = 'Category deleted successfully.';
            }
            break;
            
        case 'toggle_status':
            $current = db_fetch_single("SELECT status FROM categories WHERE id = ?", [$category_id], 'i');
            if ($current) {
                $new_status = $current['status'] === 'active' ? 'inactive' : 'active';
                db_query("UPDATE categories SET status = ? WHERE id = ?", [$new_status, $category_id], 'si');
                $_SESSION['success'] = 'Category status updated.';
            }
            break;
    }
    
    header('Location: ' . ADMIN_URL . '/categories.php');
    exit;
}

// Get all categories with hierarchy
function get_categories_hierarchy($parent_id = null) {
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count,
            (SELECT COUNT(*) FROM categories sc WHERE sc.parent_id = c.id) as subcategory_count
            FROM categories c";
    
    if ($parent_id === null) {
        $sql .= " WHERE c.parent_id IS NULL";
    } else {
        $sql .= " WHERE c.parent_id = ?";
        return db_fetch_all($sql, [$parent_id], 'i');
    }
    
    $sql .= " ORDER BY c.name";
    return db_fetch_all($sql);
}

// Get all categories for parent dropdown
$all_categories = db_fetch_all("SELECT id, name FROM categories WHERE id != ?", [$category_id ?? 0], 'i');

$categories = get_categories_hierarchy();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Categories</h1>
            <p class="text-gray-600">Organize your products into categories and subcategories</p>
        </div>
        <button type="button" onclick="openAddCategoryModal()" 
                class="mt-4 md:mt-0 bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition font-medium">
            <i class="fas fa-plus mr-2"></i> Add New Category
        </button>
    </div>
    
    <!-- Categories List -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <?php if ($categories): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Products
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subcategories
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
                        <?php foreach ($categories as $category): ?>
                            <?php
                            $subcategories = get_categories_hierarchy($category['id']);
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php if ($category['image']): ?>
                                            <div class="h-10 w-10 flex-shrink-0 mr-3">
                                                <img src="<?php echo $category['image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                     class="h-10 w-10 object-cover rounded">
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                /<?php echo $category['slug']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $category['product_count']; ?> products</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $category['subcategory_count']; ?> subcategories</div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo get_category_status_badge($category['status']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button type="button" onclick="openEditCategoryModal(<?php echo $category['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-700" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button type="button" onclick="openAddSubcategoryModal(<?php echo $category['id']; ?>)" 
                                                class="text-green-600 hover:text-green-700" title="Add Subcategory">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                        
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Toggle category status?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" 
                                                    class="text-amber-600 hover:text-amber-700" 
                                                    title="Toggle Status">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this category? This will also delete all subcategories and products in this category.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Subcategories -->
                            <?php if ($subcategories): ?>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <tr class="bg-gray-50 hover:bg-gray-100 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center ml-8">
                                                <div class="text-gray-400 mr-3">
                                                    <i class="fas fa-level-down-alt fa-rotate-90"></i>
                                                </div>
                                                <?php if ($subcategory['image']): ?>
                                                    <div class="h-8 w-8 flex-shrink-0 mr-3">
                                                        <img src="<?php echo $subcategory['image']; ?>" 
                                                             alt="<?php echo htmlspecialchars($subcategory['name']); ?>"
                                                             class="h-8 w-8 object-cover rounded">
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($subcategory['name']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        /<?php echo $subcategory['slug']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900"><?php echo $subcategory['product_count']; ?> products</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500">-</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php echo get_category_status_badge($subcategory['status']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <button type="button" onclick="openEditCategoryModal(<?php echo $subcategory['id']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-700" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <form method="POST" class="inline" 
                                                      onsubmit="return confirm('Toggle category status?');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="category_id" value="<?php echo $subcategory['id']; ?>">
                                                    <button type="submit" 
                                                            class="text-amber-600 hover:text-amber-700" 
                                                            title="Toggle Status">
                                                        <i class="fas fa-power-off"></i>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" class="inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this subcategory?');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category_id" value="<?php echo $subcategory['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-700 mb-2">No categories yet</h3>
                <p class="text-gray-500 mb-6">Create your first category to organize products.</p>
                <button type="button" onclick="openAddCategoryModal()" 
                        class="bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                    <i class="fas fa-plus mr-2"></i> Add New Category
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <!-- Modal Header -->
        <div class="flex justify-between items-center mb-6">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Add New Category</h3>
            <button type="button" onclick="closeCategoryModal()" 
                    class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <form id="categoryForm" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" id="action" name="action" value="add">
            <input type="hidden" id="category_id" name="category_id" value="">
            <input type="hidden" id="parent_id" name="parent_id" value="0">
            
            <div class="space-y-4">
                <!-- Category Name -->
                <div>
                    <label for="modal_name" class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                    <input type="text" id="modal_name" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"
                           oninput="document.getElementById('modal_slug').value = generateSlug(this.value)">
                </div>
                
                <!-- Slug -->
                <div>
                    <label for="modal_slug" class="block text-sm font-medium text-gray-700 mb-1">URL Slug *</label>
                    <input type="text" id="modal_slug" name="slug" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Parent Category -->
                <div>
                    <label for="modal_parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
                    <select id="modal_parent_id" name="parent_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="0">None (Main Category)</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Description -->
                <div>
                    <label for="modal_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="modal_description" name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                
                <!-- Image -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category Image</label>
                    <div id="currentImage" class="mb-2 hidden">
                        <img src="" alt="Current Image" class="h-20 w-20 object-cover rounded">
                        <div class="mt-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="remove_image" class="text-red-600">
                                <span class="ml-2 text-sm text-red-600">Remove image</span>
                            </label>
                        </div>
                    </div>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600 mb-2">Click to upload category image</p>
                        <input type="file" id="modal_image" name="image" accept="image/*" 
                               class="hidden">
                        <label for="modal_image" 
                               class="cursor-pointer bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">
                            Choose Image
                        </label>
                    </div>
                </div>
                
                <!-- Status -->
                <div>
                    <label for="modal_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="modal_status" name="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeCategoryModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Generate slug
function generateSlug(text) {
    return text.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Modal functions
function openAddCategoryModal() {
    document.getElementById('categoryModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Add New Category';
    document.getElementById('action').value = 'add';
    document.getElementById('parent_id').value = '0';
    document.getElementById('categoryForm').reset();
    document.getElementById('currentImage').classList.add('hidden');
    document.getElementById('modal_status').value = 'active';
}

function openAddSubcategoryModal(parentId) {
    document.getElementById('categoryModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Add Subcategory';
    document.getElementById('action').value = 'add';
    document.getElementById('parent_id').value = parentId;
    document.getElementById('categoryForm').reset();
    document.getElementById('currentImage').classList.add('hidden');
    document.getElementById('modal_status').value = 'active';
    
    // Set parent category in dropdown
    document.getElementById('modal_parent_id').value = parentId;
}

function openEditCategoryModal(categoryId) {
    // Fetch category data via AJAX
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php?action=get_category&category_id=' + categoryId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('categoryModal').classList.remove('hidden');
                document.getElementById('modalTitle').textContent = 'Edit Category';
                document.getElementById('action').value = 'update';
                document.getElementById('category_id').value = categoryId;
                
                // Fill form with category data
                document.getElementById('modal_name').value = data.category.name;
                document.getElementById('modal_slug').value = data.category.slug;
                document.getElementById('modal_parent_id').value = data.category.parent_id || '0';
                document.getElementById('modal_description').value = data.category.description || '';
                document.getElementById('modal_status').value = data.category.status;
                
                // Handle image
                if (data.category.image) {
                    const currentImage = document.getElementById('currentImage');
                    currentImage.classList.remove('hidden');
                    currentImage.querySelector('img').src = data.category.image;
                } else {
                    document.getElementById('currentImage').classList.add('hidden');
                }
            } else {
                alert('Failed to load category data.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        });
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCategoryModal();
    }
});

// Image preview
document.getElementById('modal_image').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const currentImage = document.getElementById('currentImage');
            currentImage.classList.remove('hidden');
            currentImage.querySelector('img').src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php
// Helper function for category status badge
function get_category_status_badge($status) {
    if ($status === 'active') {
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-1"></i> Active
                </span>';
    } else {
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    <i class="fas fa-times-circle mr-1"></i> Inactive
                </span>';
    }
}

require_once __DIR__ . '/../includes/admin-footer.php';
?>