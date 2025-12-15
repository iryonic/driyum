<?php
require_once __DIR__ . '/../includes/auth.php';
require_user();

$page_title = 'My Profile';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => SITE_URL . '/dashboard/'],
    ['title' => 'My Profile']
];

// Get user info
$user = get_user();
if (!$user) {
    logout_user();
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize_input($_POST['name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    
    $data = [];
    if (!empty($name)) $data['name'] = $name;
    if (!empty($phone)) $data['phone'] = $phone;
    
    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_image($_FILES['avatar'], 'avatar');
        if ($upload_result['success']) {
            $data['avatar'] = $upload_result['path'];
        }
    }
    
    if (!empty($data)) {
        if (update_profile($_SESSION['user_id'], $data)) {
            $_SESSION['user_name'] = $name ?: $_SESSION['user_name'];
            $_SESSION['success'] = 'Profile updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update profile.';
        }
    }
    
    header('Location: ' . SITE_URL . '/dashboard/profile.php');
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Please fill in all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = 'New password must be at least 6 characters.';
    } else {
        $result = change_password($_SESSION['user_id'], $current_password, $new_password);
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    header('Location: ' . SITE_URL . '/dashboard/profile.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Profile</h1>
        <p class="text-gray-600">Manage your account information and security</p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Profile Info -->
        <div class="lg:col-span-2">
            <!-- Profile Update Form -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Personal Information</h2>
                
                <form method="POST" action="<?php echo SITE_URL; ?>/dashboard/profile.php" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <div class="space-y-6">
                        <!-- Avatar -->
                        <div class="flex items-center space-x-6">
                            <div class="relative">
                                <img src="<?php echo $user['avatar'] ?: SITE_URL . '/assets/images/avatar-default.png'; ?>" 
                                     alt="Profile Picture"
                                     class="w-24 h-24 rounded-full object-cover border-4 border-white shadow">
                                <div class="absolute bottom-0 right-0 bg-amber-600 text-white p-2 rounded-full cursor-pointer hover:bg-amber-700">
                                    <label for="avatar" class="cursor-pointer">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden">
                                    </label>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Upload a profile picture (JPG, PNG, GIF max 5MB)</p>
                            </div>
                        </div>
                        
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                   required>
                        </div>
                        
                        <!-- Email (readonly) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                                   readonly>
                            <p class="text-sm text-gray-500 mt-1">Email cannot be changed</p>
                        </div>
                        
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>
                        
                        <!-- Account Created -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Member Since</label>
                            <p class="text-gray-600"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button type="submit" name="update_profile" 
                                    class="bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                                Update Profile
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Change Password</h2>
                
                <form method="POST" action="<?php echo SITE_URL; ?>/dashboard/profile.php">
                    <?php echo csrf_field(); ?>
                    
                    <div class="space-y-6">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                   required>
                        </div>
                        
                        <!-- New Password -->
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                   required>
                            <p class="text-sm text-gray-500 mt-1">Must be at least 6 characters long</p>
                        </div>
                        
                        <!-- Confirm New Password -->
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                   required>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button type="submit" name="change_password" 
                                    class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition font-medium">
                                Change Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Right Column - Account Info -->
        <div class="lg:col-span-1">
            <!-- Account Status -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Account Status</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Account Status</span>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Account Type</span>
                        <span class="font-medium"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Email Verified</span>
                        <span class="px-3 py-1 <?php echo $user['email_verified_at'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> text-sm font-medium rounded-full">
                            <?php echo $user['email_verified_at'] ? 'Verified' : 'Pending'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Verify Email -->
                <?php if (!$user['email_verified_at']): ?>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600 mb-3">Your email is not verified.</p>
                        <button type="button" 
                                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            Verify Email Address
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Security Tips -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                <h3 class="font-semibold text-amber-800 mb-3">Security Tips</h3>
                <ul class="space-y-2 text-sm text-amber-700">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2"></i>
                        <span>Use a strong, unique password</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2"></i>
                        <span>Never share your password</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2"></i>
                        <span>Log out from public computers</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-2"></i>
                        <span>Report suspicious activity</span>
                    </li>
                </ul>
            </div>
            
            <!-- Danger Zone -->
            <div class="bg-white rounded-xl shadow-md p-6 mt-6 border border-red-200">
                <h3 class="font-semibold text-red-700 mb-3">Danger Zone</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Once you delete your account, there is no going back. Please be certain.
                </p>
                <button type="button" onclick="confirmDeleteAccount()" 
                        class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition text-sm">
                    Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently deleted.')) {
        // In a real implementation, this would be an AJAX call
        fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete_account'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '<?php echo SITE_URL; ?>';
            } else {
                alert(data.message);
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>