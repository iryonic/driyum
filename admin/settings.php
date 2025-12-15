<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Settings';
$breadcrumbs = [
    ['title' => 'Admin', 'url' => ADMIN_URL . '/dashboard.php'],
    ['title' => 'Settings']
];

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_post_request()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $settings = [
            'store_name' => sanitize_input($_POST['store_name'] ?? ''),
            'store_email' => sanitize_input($_POST['store_email'] ?? ''),
            'store_phone' => sanitize_input($_POST['store_phone'] ?? ''),
            'store_address' => sanitize_input($_POST['store_address'] ?? ''),
            'currency' => sanitize_input($_POST['currency'] ?? '₹'),
            'tax_rate' => floatval($_POST['tax_rate'] ?? 18),
            'shipping_amount' => floatval($_POST['shipping_amount'] ?? 50),
            'free_shipping_amount' => floatval($_POST['free_shipping_amount'] ?? 500),
            'order_email_template' => sanitize_input($_POST['order_email_template'] ?? ''),
            'contact_email' => sanitize_input($_POST['contact_email'] ?? ''),
            'support_email' => sanitize_input($_POST['support_email'] ?? ''),
            'facebook_url' => sanitize_input($_POST['facebook_url'] ?? ''),
            'twitter_url' => sanitize_input($_POST['twitter_url'] ?? ''),
            'instagram_url' => sanitize_input($_POST['instagram_url'] ?? ''),
            'meta_title' => sanitize_input($_POST['meta_title'] ?? ''),
            'meta_description' => sanitize_input($_POST['meta_description'] ?? ''),
            'meta_keywords' => sanitize_input($_POST['meta_keywords'] ?? ''),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
            'guest_checkout' => isset($_POST['guest_checkout']) ? '1' : '0'
        ];
        
        foreach ($settings as $key => $value) {
            db_query(
                "INSERT INTO settings (setting_key, setting_value, setting_group) 
                 VALUES (?, ?, 'general') 
                 ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                [$key, $value, $value],
                'sss'
            );
        }
        
        // Handle logo upload
        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['store_logo'], 'logo');
            if ($upload_result['success']) {
                db_query(
                    "INSERT INTO settings (setting_key, setting_value, setting_group) 
                     VALUES ('store_logo', ?, 'general') 
                     ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                    [$upload_result['path'], $upload_result['path']],
                    'ss'
                );
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['store_favicon']) && $_FILES['store_favicon']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['store_favicon'], 'logo');
            if ($upload_result['success']) {
                db_query(
                    "INSERT INTO settings (setting_key, setting_value, setting_group) 
                     VALUES ('store_favicon', ?, 'general') 
                     ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                    [$upload_result['path'], $upload_result['path']],
                    'ss'
                );
            }
        }
        
        $_SESSION['success'] = 'Settings updated successfully.';
        header('Location: ' . ADMIN_URL . '/settings.php');
        exit;
    }
}

// Get all settings
$settings_result = db_fetch_all("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($settings_result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Store Settings</h1>
        <p class="text-gray-600">Configure your store settings and preferences</p>
    </div>
    
    <!-- Settings Form -->
    <form method="POST" action="<?php echo ADMIN_URL; ?>/settings.php" enctype="multipart/form-data" class="space-y-8">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_settings">
        
        <!-- General Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">General Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Store Name -->
                <div>
                    <label for="store_name" class="block text-sm font-medium text-gray-700 mb-2">Store Name *</label>
                    <input type="text" id="store_name" name="store_name" required
                           value="<?php echo htmlspecialchars($settings['store_name'] ?? 'Snack Store'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Store Email -->
                <div>
                    <label for="store_email" class="block text-sm font-medium text-gray-700 mb-2">Store Email *</label>
                    <input type="email" id="store_email" name="store_email" required
                           value="<?php echo htmlspecialchars($settings['store_email'] ?? 'info@snackstore.com'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Store Phone -->
                <div>
                    <label for="store_phone" class="block text-sm font-medium text-gray-700 mb-2">Store Phone</label>
                    <input type="text" id="store_phone" name="store_phone"
                           value="<?php echo htmlspecialchars($settings['store_phone'] ?? '+91 9876543210'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Currency -->
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
                    <input type="text" id="currency" name="currency"
                           value="<?php echo htmlspecialchars($settings['currency'] ?? '₹'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            
            <!-- Store Address -->
            <div class="mt-6">
                <label for="store_address" class="block text-sm font-medium text-gray-700 mb-2">Store Address</label>
                <textarea id="store_address" name="store_address" rows="2"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"><?php echo htmlspecialchars($settings['store_address'] ?? '123 Snack Street, Food City'); ?></textarea>
            </div>
        </div>
        
        <!-- Store Logo & Favicon -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Store Branding</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Store Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Store Logo</label>
                    
                    <?php if (!empty($settings['store_logo'])): ?>
                        <div class="mb-4">
                            <img src="<?php echo $settings['store_logo']; ?>" 
                                 alt="Store Logo" 
                                 class="h-20 object-contain">
                        </div>
                    <?php endif; ?>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                        <input type="file" id="store_logo" name="store_logo" accept="image/*" 
                               class="hidden">
                        <label for="store_logo" 
                               class="cursor-pointer bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">
                            <?php echo empty($settings['store_logo']) ? 'Upload Logo' : 'Change Logo'; ?>
                        </label>
                        <p class="text-xs text-gray-500 mt-2">Recommended: 200x60px PNG</p>
                    </div>
                </div>
                
                <!-- Store Favicon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Store Favicon</label>
                    
                    <?php if (!empty($settings['store_favicon'])): ?>
                        <div class="mb-4">
                            <img src="<?php echo $settings['store_favicon']; ?>" 
                                 alt="Store Favicon" 
                                 class="h-16 w-16 object-contain">
                        </div>
                    <?php endif; ?>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                        <input type="file" id="store_favicon" name="store_favicon" accept="image/*" 
                               class="hidden">
                        <label for="store_favicon" 
                               class="cursor-pointer bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">
                            <?php echo empty($settings['store_favicon']) ? 'Upload Favicon' : 'Change Favicon'; ?>
                        </label>
                        <p class="text-xs text-gray-500 mt-2">Recommended: 32x32px ICO/PNG</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pricing & Shipping -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Pricing & Shipping</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Tax Rate -->
                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">Tax Rate (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '18'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Shipping Amount -->
                <div>
                    <label for="shipping_amount" class="block text-sm font-medium text-gray-700 mb-2">Shipping Amount (₹)</label>
                    <input type="number" id="shipping_amount" name="shipping_amount" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($settings['shipping_amount'] ?? '50'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Free Shipping Amount -->
                <div>
                    <label for="free_shipping_amount" class="block text-sm font-medium text-gray-700 mb-2">Free Shipping Above (₹)</label>
                    <input type="number" id="free_shipping_amount" name="free_shipping_amount" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($settings['free_shipping_amount'] ?? '500'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Email Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contact Email -->
                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email"
                           value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'contact@snackstore.com'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Support Email -->
                <div>
                    <label for="support_email" class="block text-sm font-medium text-gray-700 mb-2">Support Email</label>
                    <input type="email" id="support_email" name="support_email"
                           value="<?php echo htmlspecialchars($settings['support_email'] ?? 'support@snackstore.com'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            
            <!-- Order Email Template -->
            <div class="mt-6">
                <label for="order_email_template" class="block text-sm font-medium text-gray-700 mb-2">Order Confirmation Email Template</label>
                <textarea id="order_email_template" name="order_email_template" rows="6"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"><?php echo htmlspecialchars($settings['order_email_template'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-500 mt-2">Use placeholders: {customer_name}, {order_number}, {order_date}, {order_total}</p>
            </div>
        </div>
        
        <!-- Social Media -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Social Media</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Facebook -->
                <div>
                    <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-2">Facebook URL</label>
                    <input type="url" id="facebook_url" name="facebook_url"
                           value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Twitter -->
                <div>
                    <label for="twitter_url" class="block text-sm font-medium text-gray-700 mb-2">Twitter URL</label>
                    <input type="url" id="twitter_url" name="twitter_url"
                           value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Instagram -->
                <div>
                    <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-2">Instagram URL</label>
                    <input type="url" id="instagram_url" name="instagram_url"
                           value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
        </div>
        
        <!-- SEO Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">SEO Settings</h2>
            
            <div class="space-y-6">
                <!-- Meta Title -->
                <div>
                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title"
                           value="<?php echo htmlspecialchars($settings['meta_title'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Meta Description -->
                <div>
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"><?php echo htmlspecialchars($settings['meta_description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Meta Keywords -->
                <div>
                    <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                    <textarea id="meta_keywords" name="meta_keywords" rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"><?php echo htmlspecialchars($settings['meta_keywords'] ?? ''); ?></textarea>
                    <p class="text-sm text-gray-500 mt-2">Separate keywords with commas</p>
                </div>
            </div>
        </div>
        
        <!-- Advanced Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Advanced Settings</h2>
            
            <div class="space-y-4">
                <!-- Maintenance Mode -->
                <div class="flex items-center">
                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                           <?php echo (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>>
                    <label for="maintenance_mode" class="ml-2 text-gray-700">
                        Enable Maintenance Mode
                    </label>
                </div>
                
                <!-- Registration Enabled -->
                <div class="flex items-center">
                    <input type="checkbox" id="registration_enabled" name="registration_enabled" 
                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                           <?php echo (!isset($settings['registration_enabled']) || $settings['registration_enabled'] == '1') ? 'checked' : ''; ?>>
                    <label for="registration_enabled" class="ml-2 text-gray-700">
                        Allow New User Registrations
                    </label>
                </div>
                
                <!-- Guest Checkout -->
                <div class="flex items-center">
                    <input type="checkbox" id="guest_checkout" name="guest_checkout" 
                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                           <?php echo (!isset($settings['guest_checkout']) || $settings['guest_checkout'] == '1') ? 'checked' : ''; ?>>
                    <label for="guest_checkout" class="ml-2 text-gray-700">
                        Allow Guest Checkout
                    </label>
                </div>
            </div>
            
            <!-- Cache Clear Button -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <button type="button" onclick="clearCache()" 
                        class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition">
                    <i class="fas fa-broom mr-2"></i> Clear Cache
                </button>
                <p class="text-sm text-gray-500 mt-2">Clear all cached data and refresh settings.</p>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="flex justify-end space-x-4">
            <button type="reset" 
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Reset Changes
            </button>
            <button type="submit" 
                    class="px-8 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition font-medium">
                Save Settings
            </button>
        </div>
    </form>
</div>

<script>
function clearCache() {
    if (confirm('Clear all cache? This will reset temporary data.')) {
        fetch('<?php echo ADMIN_URL; ?>/includes/ajax.php?action=clear_cache')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cache cleared successfully.');
                    location.reload();
                } else {
                    alert('Failed to clear cache.');
                }
            });
    }
}

// Preview image before upload
document.getElementById('store_logo').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('img');
            preview.src = e.target.result;
            preview.className = 'h-20 object-contain mb-4';
            
            const container = document.getElementById('store_logo').parentNode;
            const existingPreview = container.querySelector('img');
            if (existingPreview) {
                existingPreview.src = e.target.result;
            } else {
                container.insertBefore(preview, container.firstChild);
            }
        };
        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('store_favicon').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('img');
            preview.src = e.target.result;
            preview.className = 'h-16 w-16 object-contain mb-4';
            
            const container = document.getElementById('store_favicon').parentNode;
            const existingPreview = container.querySelector('img');
            if (existingPreview) {
                existingPreview.src = e.target.result;
            } else {
                container.insertBefore(preview, container.firstChild);
            }
        };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>