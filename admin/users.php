<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Manage Users';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Users']
];

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    switch ($action) {
        case 'update_status':
            $status = sanitize_input($_POST['status'] ?? '');
            if (in_array($status, ['active', 'inactive', 'suspended'])) {
                db_query(
                    "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?",
                    [$status, $user_id],
                    'si'
                );
                $_SESSION['success'] = 'User status updated.';
            }
            break;
            
        case 'update_role':
            $role = sanitize_input($_POST['role'] ?? '');
            if (in_array($role, ['user', 'admin'])) {
                // Prevent removing last admin
                if ($role === 'user') {
                    $admin_count = db_fetch_single(
                        "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'"
                    )['count'] ?? 0;
                    
                    if ($admin_count <= 1) {
                        $_SESSION['error'] = 'Cannot remove last active admin.';
                        break;
                    }
                }
                
                db_query(
                    "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?",
                    [$role, $user_id],
                    'si'
                );
                $_SESSION['success'] = 'User role updated.';
            }
            break;
            
        case 'delete':
            // Check if user has orders
            $order_count = db_fetch_single(
                "SELECT COUNT(*) as count FROM orders WHERE user_id = ?",
                [$user_id],
                'i'
            )['count'] ?? 0;
            
            if ($order_count > 0) {
                $_SESSION['error'] = 'Cannot delete user with existing orders.';
            } else {
                // Delete user data
                db_query("DELETE FROM cart WHERE user_id = ?", [$user_id], 'i');
                db_query("DELETE FROM wishlist WHERE user_id = ?", [$user_id], 'i');
                db_query("DELETE FROM user_addresses WHERE user_id = ?", [$user_id], 'i');
                db_query("DELETE FROM users WHERE id = ?", [$user_id], 'i');
                
                $_SESSION['success'] = 'User deleted successfully.';
            }
            break;
    }
    
    header('Location: ' . ADMIN_URL . '/users.php');
    exit;
}

// Get users with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query with filters
$where = "1=1";
$params = [];
$types = '';

// Filter by status
if (isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive', 'suspended'])) {
    $where .= " AND u.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Filter by role
if (isset($_GET['role']) && in_array($_GET['role'], ['user', 'admin'])) {
    $where .= " AND u.role = ?";
    $params[] = $_GET['role'];
    $types .= 's';
}

// Filter by search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_term = "%{$_GET['search']}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

// Filter by date range
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $where .= " AND DATE(u.created_at) >= ?";
    $params[] = $_GET['date_from'];
    $types .= 's';
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $where .= " AND DATE(u.created_at) <= ?";
    $params[] = $_GET['date_to'];
    $types .= 's';
}

// Get users
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count,
        (SELECT COUNT(*) FROM user_addresses ua WHERE ua.user_id = u.id) as address_count
        FROM users u 
        WHERE $where 
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$users = db_fetch_all($sql, $params, $types);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users u WHERE $where";
$count_params = array_slice($params, 0, -2);
$count_types = substr($types, 0, -2);
$count_result = db_fetch_single($count_sql, $count_params, $count_types);
$total_users = $count_result['total'] ?? 0;

// Get user statistics
$stats = db_fetch_single(
    "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users
     FROM users"
);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Users</h1>
        <p class="text-gray-600">Total Users: <?php echo $total_users; ?> | 
           Admins: <?php echo $stats['admin_count'] ?? 0; ?> | 
           Customers: <?php echo $stats['user_count'] ?? 0; ?></p>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_users'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Total Users</div>
        </div>
        <div class="bg-blue-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-blue-600"><?php echo $stats['admin_count'] ?? 0; ?></div>
            <div class="text-sm text-blue-700">Admins</div>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo $stats['user_count'] ?? 0; ?></div>
            <div class="text-sm text-green-700">Customers</div>
        </div>
        <div class="bg-yellow-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['active_users'] ?? 0; ?></div>
            <div class="text-sm text-yellow-700">Active</div>
        </div>
        <div class="bg-red-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-red-600"><?php echo $stats['suspended_users'] ?? 0; ?></div>
            <div class="text-sm text-red-700">Suspended</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>" 
                       placeholder="Name, Email or Phone"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Role -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">All Roles</option>
                    <option value="user" <?php echo (isset($_GET['role']) && $_GET['role'] == 'user') ? 'selected' : ''; ?>>Customer</option>
                    <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">All Status</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo (isset($_GET['status']) && $_GET['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            
            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Buttons -->
            <div class="md:col-span-6 flex justify-end space-x-2">
                <button type="submit" 
                        class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="<?php echo ADMIN_URL; ?>/users.php" 
                   class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                    Reset Filters
                </a>
                <button type="button" onclick="exportUsers()" 
                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-download mr-2"></i> Export
                </button>
            </div>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <?php if ($users): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Role
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Orders
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Joined
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php if ($user['avatar']): ?>
                                            <div class="h-10 w-10 flex-shrink-0 mr-3">
                                                <img src="<?php echo $user['avatar']; ?>" 
                                                     alt="<?php echo htmlspecialchars($user['name']); ?>"
                                                     class="h-10 w-10 rounded-full object-cover">
                                            </div>
                                        <?php else: ?>
                                            <div class="h-10 w-10 flex-shrink-0 mr-3 bg-gray-200 rounded-full flex items-center justify-center">
                                                <span class="text-gray-500 font-medium">
                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ID: <?php echo $user['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $user['email']; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $user['phone'] ?: 'No phone'; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo get_user_role_badge($user['role']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $user['order_count']; ?> orders</div>
                                    <div class="text-xs text-gray-500"><?php echo $user['address_count']; ?> addresses</div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo get_user_status_badge($user['status']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($user['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <!-- View Profile -->
                                        <button type="button" onclick="viewUserProfile(<?php echo $user['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-700" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <!-- Edit Status -->
                                        <div class="relative group">
                                            <button type="button" class="text-amber-600 hover:text-amber-700" title="Change Status">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-10 hidden group-hover:block">
                                                <form method="POST" class="px-2 py-1">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="status" value="active" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700">
                                                        <i class="fas fa-check-circle mr-2"></i> Set Active
                                                    </button>
                                                    <button type="submit" name="status" value="inactive" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-yellow-50 hover:text-yellow-700">
                                                        <i class="fas fa-minus-circle mr-2"></i> Set Inactive
                                                    </button>
                                                    <button type="submit" name="status" value="suspended" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700">
                                                        <i class="fas fa-ban mr-2"></i> Suspend
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Change Role -->
                                        <div class="relative group">
                                            <button type="button" class="text-purple-600 hover:text-purple-700" title="Change Role">
                                                <i class="fas fa-user-tag"></i>
                                            </button>
                                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-10 hidden group-hover:block">
                                                <form method="POST" class="px-2 py-1">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="role" value="user" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                                        <i class="fas fa-user mr-2"></i> Make Customer
                                                    </button>
                                                    <button type="submit" name="role" value="admin" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                                        <i class="fas fa-crown mr-2"></i> Make Admin
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete -->
                                        <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                            <form method="POST" class="inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_users > $limit): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo min($offset + 1, $total_users); ?> to <?php echo min($offset + $limit, $total_users); ?> of <?php echo $total_users; ?> users
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <?php
                                $prev_url = ADMIN_URL . '/users.php?page=' . ($page - 1);
                                if (isset($_GET['search'])) $prev_url .= '&search=' . urlencode($_GET['search']);
                                if (isset($_GET['role'])) $prev_url .= '&role=' . $_GET['role'];
                                if (isset($_GET['status'])) $prev_url .= '&status=' . $_GET['status'];
                                if (isset($_GET['date_from'])) $prev_url .= '&date_from=' . $_GET['date_from'];
                                if (isset($_GET['date_to'])) $prev_url .= '&date_to=' . $_GET['date_to'];
                                ?>
                                <a href="<?php echo $prev_url; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < ceil($total_users / $limit)): ?>
                                <?php
                                $next_url = ADMIN_URL . '/users.php?page=' . ($page + 1);
                                if (isset($_GET['search'])) $next_url .= '&search=' . urlencode($_GET['search']);
                                if (isset($_GET['role'])) $next_url .= '&role=' . $_GET['role'];
                                if (isset($_GET['status'])) $next_url .= '&status=' . $_GET['status'];
                                if (isset($_GET['date_from'])) $next_url .= '&date_from=' . $_GET['date_from'];
                                if (isset($_GET['date_to'])) $next_url .= '&date_to=' . $_GET['date_to'];
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
                <i class="fas fa-users text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-700 mb-2">No users found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your filters or check back later.</p>
                <a href="<?php echo ADMIN_URL; ?>/users.php" 
                   class="inline-block bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                    Reset Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- User Profile Modal -->
<div id="userProfileModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800">User Profile</h3>
            <button type="button" onclick="closeUserProfile()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="userProfileContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
function viewUserProfile(userId) {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php?action=get_user_profile&user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('userProfileModal').classList.remove('hidden');
                document.getElementById('userProfileContent').innerHTML = data.html;
            } else {
                alert('Failed to load user profile.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        });
}

function closeUserProfile() {
    document.getElementById('userProfileModal').classList.add('hidden');
}

function exportUsers() {
    let url = '<?php echo ADMIN_URL; ?>/includes/export.php?type=users';
    
    <?php if (isset($_GET['search'])): ?>
        url += '&search=<?php echo urlencode($_GET['search']); ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['role'])): ?>
        url += '&role=<?php echo $_GET['role']; ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['status'])): ?>
        url += '&status=<?php echo $_GET['status']; ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['date_from'])): ?>
        url += '&date_from=<?php echo $_GET['date_from']; ?>';
    <?php endif; ?>
    
    <?php if (isset($_GET['date_to'])): ?>
        url += '&date_to=<?php echo $_GET['date_to']; ?>';
    <?php endif; ?>
    
    window.open(url, '_blank');
}

// Close modal when clicking outside
document.getElementById('userProfileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserProfile();
    }
});

// Helper functions
function get_user_role_badge(role) {
    if (role === 'admin') {
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">' +
               '<i class="fas fa-crown mr-1"></i> Admin</span>';
    } else {
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">' +
               '<i class="fas fa-user mr-1"></i> Customer</span>';
    }
}

function get_user_status_badge(status) {
    switch (status) {
        case 'active':
            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">' +
                   '<i class="fas fa-check-circle mr-1"></i> Active</span>';
        case 'inactive':
            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">' +
                   '<i class="fas fa-minus-circle mr-1"></i> Inactive</span>';
        case 'suspended':
            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">' +
                   '<i class="fas fa-ban mr-1"></i> Suspended</span>';
        default:
            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' +
                   '<i class="fas fa-question-circle mr-1"></i> Unknown</span>';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>