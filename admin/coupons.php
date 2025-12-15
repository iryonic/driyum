<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Manage Coupons';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Coupons']
];

// Handle coupon actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    $coupon_id = intval($_POST['coupon_id'] ?? 0);
    
    switch ($action) {
        case 'add':
            $code = strtoupper(sanitize_input($_POST['code'] ?? ''));
            $description = sanitize_input($_POST['description'] ?? '');
            $discount_type = sanitize_input($_POST['discount_type'] ?? 'percentage');
            $discount_value = floatval($_POST['discount_value'] ?? 0);
            $max_discount_amount = floatval($_POST['max_discount_amount'] ?? 0);
            $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
            $valid_from = sanitize_input($_POST['valid_from'] ?? '');
            $valid_to = sanitize_input($_POST['valid_to'] ?? '');
            $usage_limit = intval($_POST['usage_limit'] ?? 0);
            $user_limit = intval($_POST['user_limit'] ?? 0);
            $status = sanitize_input($_POST['status'] ?? 'active');
            
            // Check if code already exists
            $existing = db_fetch_single("SELECT id FROM coupons WHERE code = ?", [$code], 's');
            if ($existing) {
                $_SESSION['error'] = 'Coupon code already exists.';
            } else {
                db_query(
                    "INSERT INTO coupons (code, description, discount_type, discount_value, 
                     max_discount_amount, min_order_amount, valid_from, valid_to, usage_limit, 
                     user_limit, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$code, $description, $discount_type, $discount_value, $max_discount_amount,
                     $min_order_amount, $valid_from ?: null, $valid_to ?: null, 
                     $usage_limit ?: null, $user_limit ?: null, $status],
                    'sssddddssiis'
                );
                
                $_SESSION['success'] = 'Coupon added successfully.';
            }
            break;
            
        case 'update':
            $code = strtoupper(sanitize_input($_POST['code'] ?? ''));
            $description = sanitize_input($_POST['description'] ?? '');
            $discount_type = sanitize_input($_POST['discount_type'] ?? 'percentage');
            $discount_value = floatval($_POST['discount_value'] ?? 0);
            $max_discount_amount = floatval($_POST['max_discount_amount'] ?? 0);
            $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
            $valid_from = sanitize_input($_POST['valid_from'] ?? '');
            $valid_to = sanitize_input($_POST['valid_to'] ?? '');
            $usage_limit = intval($_POST['usage_limit'] ?? 0);
            $user_limit = intval($_POST['user_limit'] ?? 0);
            $status = sanitize_input($_POST['status'] ?? 'active');
            
            // Check if code already exists (excluding current coupon)
            $existing = db_fetch_single(
                "SELECT id FROM coupons WHERE code = ? AND id != ?",
                [$code, $coupon_id],
                'si'
            );
            if ($existing) {
                $_SESSION['error'] = 'Coupon code already exists.';
            } else {
                db_query(
                    "UPDATE coupons SET 
                     code = ?, description = ?, discount_type = ?, discount_value = ?, 
                     max_discount_amount = ?, min_order_amount = ?, valid_from = ?, valid_to = ?, 
                     usage_limit = ?, user_limit = ?, status = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$code, $description, $discount_type, $discount_value, $max_discount_amount,
                     $min_order_amount, $valid_from ?: null, $valid_to ?: null, 
                     $usage_limit ?: null, $user_limit ?: null, $status, $coupon_id],
                    'sssddddssiisi'
                );
                
                $_SESSION['success'] = 'Coupon updated successfully.';
            }
            break;
            
        case 'delete':
            db_query("DELETE FROM coupons WHERE id = ?", [$coupon_id], 'i');
            $_SESSION['success'] = 'Coupon deleted successfully.';
            break;
            
        case 'toggle_status':
            $current = db_fetch_single("SELECT status FROM coupons WHERE id = ?", [$coupon_id], 'i');
            if ($current) {
                $new_status = $current['status'] === 'active' ? 'inactive' : 'active';
                db_query("UPDATE coupons SET status = ? WHERE id = ?", [$new_status, $coupon_id], 'si');
                $_SESSION['success'] = 'Coupon status updated.';
            }
            break;
    }
    
    header('Location: ' . ADMIN_URL . '/coupons.php');
    exit;
}

// Get coupons with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters
$where = "1=1";
$params = [];
$types = '';

// Filter by status
if (isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive', 'expired'])) {
    if ($_GET['status'] === 'expired') {
        $where .= " AND valid_to < CURDATE()";
    } else {
        $where .= " AND status = ?";
        $params[] = $_GET['status'];
        $types .= 's';
    }
}

// Filter by search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND code LIKE ?";
    $search_term = "%{$_GET['search']}%";
    $params[] = $search_term;
    $types .= 's';
}

// Get coupons
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM coupon_usage cu WHERE cu.coupon_id = c.id) as used_count
        FROM coupons c 
        WHERE $where 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$coupons = db_fetch_all($sql, $params, $types);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM coupons c WHERE $where";
$count_params = array_slice($params, 0, -2);
$count_types = substr($types, 0, -2);
$count_result = db_fetch_single($count_sql, $count_params, $count_types);
$total_coupons = $count_result['total'] ?? 0;

// Get coupon statistics
$stats = db_fetch_single(
    "SELECT 
        COUNT(*) as total_coupons,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_coupons,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_coupons,
        SUM(used_count) as total_usage,
        SUM(CASE WHEN valid_to < CURDATE() THEN 1 ELSE 0 END) as expired_coupons
     FROM coupons"
);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Coupons</h1>
            <p class="text-gray-600">Total Coupons: <?php echo $total_coupons; ?> | 
               Total Usage: <?php echo $stats['total_usage'] ?? 0; ?> times</p>
        </div>
        <button type="button" onclick="openAddCouponModal()" 
                class="mt-4 md:mt-0 bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition font-medium">
            <i class="fas fa-plus mr-2"></i> Add New Coupon
        </button>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_coupons'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Total Coupons</div>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo $stats['active_coupons'] ?? 0; ?></div>
            <div class="text-sm text-green-700">Active</div>
        </div>
        <div class="bg-yellow-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['inactive_coupons'] ?? 0; ?></div>
            <div class="text-sm text-yellow-700">Inactive</div>
        </div>
        <div class="bg-red-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-red-600"><?php echo $stats['expired_coupons'] ?? 0; ?></div>
            <div class="text-sm text-red-700">Expired</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Code</label>
                <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>" 
                       placeholder="Coupon code"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">All Status</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="expired" <?php echo (isset($_GET['status']) && $_GET['status'] == 'expired') ? 'selected' : ''; ?>>Expired</option>
                </select>
            </div>
            
            <!-- Buttons -->
            <div class="flex items-end space-x-2">
                <button type="submit" 
                        class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="<?php echo ADMIN_URL; ?>/coupons.php" 
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Coupons Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <?php if ($coupons): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Coupon Code
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Discount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usage
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Validity
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
                        <?php foreach ($coupons as $coupon): ?>
                            <?php
                            $is_expired = $coupon['valid_to'] && strtotime($coupon['valid_to']) < time();
                            ?>
                            <tr class="hover:bg-gray-50 transition <?php echo $is_expired ? 'bg-red-50' : ''; ?>">
                                <td class="px-6 py-4">
                                    <div class="text-lg font-bold text-gray-900 font-mono">
                                        <?php echo $coupon['code']; ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Created: <?php echo date('M d, Y', strtotime($coupon['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($coupon['description']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                            <?php echo $coupon['discount_value']; ?>% off
                                        <?php else: ?>
                                            <?php echo format_price($coupon['discount_value']); ?> off
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($coupon['min_order_amount'] > 0): ?>
                                        <div class="text-xs text-gray-500">
                                            Min order: <?php echo format_price($coupon['min_order_amount']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $coupon['used_count']; ?> / <?php echo $coupon['usage_limit'] ?: '∞'; ?>
                                    </div>
                                    <?php if ($coupon['user_limit']): ?>
                                        <div class="text-xs text-gray-500">
                                            <?php echo $coupon['user_limit']; ?> per user
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php if ($coupon['valid_from']): ?>
                                            From: <?php echo date('M d, Y', strtotime($coupon['valid_from'])); ?>
                                        <?php else: ?>
                                            From: Always
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-900">
                                        <?php if ($coupon['valid_to']): ?>
                                            To: <?php echo date('M d, Y', strtotime($coupon['valid_to'])); ?>
                                        <?php else: ?>
                                            To: Forever
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo get_coupon_status_badge($coupon['status'], $is_expired); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button type="button" onclick="openEditCouponModal(<?php echo $coupon['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-700" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Toggle coupon status?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" 
                                                    class="text-amber-600 hover:text-amber-700" 
                                                    title="Toggle Status">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        
                                        <button type="button" onclick="viewCouponUsage(<?php echo $coupon['id']; ?>)" 
                                                class="text-green-600 hover:text-green-700" title="View Usage">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_coupons > $limit): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo min($offset + 1, $total_coupons); ?> to <?php echo min($offset + $limit, $total_coupons); ?> of <?php echo $total_coupons; ?> coupons
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <?php
                                $prev_url = ADMIN_URL . '/coupons.php?page=' . ($page - 1);
                                if (isset($_GET['search'])) $prev_url .= '&search=' . urlencode($_GET['search']);
                                if (isset($_GET['status'])) $prev_url .= '&status=' . $_GET['status'];
                                ?>
                                <a href="<?php echo $prev_url; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < ceil($total_coupons / $limit)): ?>
                                <?php
                                $next_url = ADMIN_URL . '/coupons.php?page=' . ($page + 1);
                                if (isset($_GET['search'])) $next_url .= '&search=' . urlencode($_GET['search']);
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
                <i class="fas fa-tag text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-700 mb-2">No coupons found</h3>
                <p class="text-gray-500 mb-6">Create your first coupon to boost sales.</p>
                <button type="button" onclick="openAddCouponModal()" 
                        class="bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                    <i class="fas fa-plus mr-2"></i> Add New Coupon
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Coupon Modal -->
<div id="couponModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
        <!-- Modal Header -->
        <div class="flex justify-between items-center mb-6">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Add New Coupon</h3>
            <button type="button" onclick="closeCouponModal()" 
                    class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <form id="couponForm" method="POST" class="space-y-6">
            <?php echo csrf_field(); ?>
            <input type="hidden" id="action" name="action" value="add">
            <input type="hidden" id="coupon_id" name="coupon_id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Coupon Code -->
                <div class="md:col-span-2">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Coupon Code *</label>
                    <input type="text" id="code" name="code" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 uppercase"
                           placeholder="SUMMER2024"
                           maxlength="20">
                    <p class="text-sm text-gray-500 mt-1">Uppercase letters and numbers only</p>
                </div>
                
                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="description" name="description" rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
                
                <!-- Discount Type -->
                <div>
                    <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-2">Discount Type *</label>
                    <select id="discount_type" name="discount_type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount (₹)</option>
                    </select>
                </div>
                
                <!-- Discount Value -->
                <div>
                    <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-2">Discount Value *</label>
                    <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Max Discount Amount -->
                <div>
                    <label for="max_discount_amount" class="block text-sm font-medium text-gray-700 mb-2">Max Discount (₹)</label>
                    <input type="number" id="max_discount_amount" name="max_discount_amount" step="0.01" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <p class="text-sm text-gray-500 mt-1">For percentage discounts only</p>
                </div>
                
                <!-- Min Order Amount -->
                <div>
                    <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-2">Min Order (₹)</label>
                    <input type="number" id="min_order_amount" name="min_order_amount" step="0.01" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Valid From -->
                <div>
                    <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-2">Valid From</label>
                    <input type="date" id="valid_from" name="valid_from"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Valid To -->
                <div>
                    <label for="valid_to" class="block text-sm font-medium text-gray-700 mb-2">Valid To</label>
                    <input type="date" id="valid_to" name="valid_to"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Usage Limit -->
                <div>
                    <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                    <input type="number" id="usage_limit" name="usage_limit" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <p class="text-sm text-gray-500 mt-1">Leave empty for unlimited</p>
                </div>
                
                <!-- User Limit -->
                <div>
                    <label for="user_limit" class="block text-sm font-medium text-gray-700 mb-2">Per User Limit</label>
                    <input type="number" id="user_limit" name="user_limit" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <p class="text-sm text-gray-500 mt-1">Times a single user can use</p>
                </div>
                
                <!-- Status -->
                <div class="md:col-span-2">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeCouponModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                    Save Coupon
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Coupon Usage Modal -->
<div id="couponUsageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Coupon Usage Details</h3>
            <button type="button" onclick="closeCouponUsageModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="couponUsageContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
// Modal functions
function openAddCouponModal() {
    document.getElementById('couponModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Add New Coupon';
    document.getElementById('action').value = 'add';
    document.getElementById('couponForm').reset();
    document.getElementById('status').value = 'active';
}

function openEditCouponModal(couponId) {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php?action=get_coupon&coupon_id=' + couponId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('couponModal').classList.remove('hidden');
                document.getElementById('modalTitle').textContent = 'Edit Coupon';
                document.getElementById('action').value = 'update';
                document.getElementById('coupon_id').value = couponId;
                
                // Fill form with coupon data
                const coupon = data.coupon;
                document.getElementById('code').value = coupon.code;
                document.getElementById('description').value = coupon.description || '';
                document.getElementById('discount_type').value = coupon.discount_type;
                document.getElementById('discount_value').value = coupon.discount_value;
                document.getElementById('max_discount_amount').value = coupon.max_discount_amount || '';
                document.getElementById('min_order_amount').value = coupon.min_order_amount || '';
                document.getElementById('valid_from').value = coupon.valid_from || '';
                document.getElementById('valid_to').value = coupon.valid_to || '';
                document.getElementById('usage_limit').value = coupon.usage_limit || '';
                document.getElementById('user_limit').value = coupon.user_limit || '';
                document.getElementById('status').value = coupon.status;
            } else {
                alert('Failed to load coupon data.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        });
}

function closeCouponModal() {
    document.getElementById('couponModal').classList.add('hidden');
}

function viewCouponUsage(couponId) {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php?action=get_coupon_usage&coupon_id=' + couponId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('couponUsageModal').classList.remove('hidden');
                document.getElementById('couponUsageContent').innerHTML = data.html;
            } else {
                alert('Failed to load coupon usage data.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        });
}

function closeCouponUsageModal() {
    document.getElementById('couponUsageModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('couponModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCouponModal();
    }
});

document.getElementById('couponUsageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCouponUsageModal();
    }
});

// Validate coupon form
document.getElementById('couponForm').addEventListener('submit', function(e) {
    const discountType = document.getElementById('discount_type').value;
    const discountValue = parseFloat(document.getElementById('discount_value').value);
    
    if (discountType === 'percentage' && (discountValue <= 0 || discountValue > 100)) {
        e.preventDefault();
        alert('Percentage discount must be between 0 and 100.');
        return false;
    }
    
    if (discountType === 'fixed' && discountValue <= 0) {
        e.preventDefault();
        alert('Fixed discount must be greater than 0.');
        return false;
    }
    
    const validFrom = document.getElementById('valid_from').value;
    const validTo = document.getElementById('valid_to').value;
    
    if (validFrom && validTo && validFrom > validTo) {
        e.preventDefault();
        alert('Valid From date must be before Valid To date.');
        return false;
    }
    
    return true;
});
</script>

<?php
// Helper function for coupon status badge
function get_coupon_status_badge($status, $is_expired = false) {
    if ($is_expired) {
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">' .
               '<i class="fas fa-calendar-times mr-1"></i> Expired</span>';
    }
    
    switch ($status) {
        case 'active':
            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">' .
                   '<i class="fas fa-check-circle mr-1"></i> Active</span>';
        case 'inactive':
            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' .
                   '<i class="fas fa-times-circle mr-1"></i> Inactive</span>';
        default:
            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' .
                   '<i class="fas fa-question-circle mr-1"></i> Unknown</span>';
    }
}

require_once __DIR__ . '/../includes/admin-footer.php';
?>