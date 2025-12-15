<?php
require_once __DIR__ . '/../includes/auth.php';
require_user();

$page_title = 'My Addresses';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => SITE_URL . '/dashboard/'],
    ['title' => 'My Addresses']
];

// Handle address actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $address_id = intval($_POST['address_id'] ?? 0);
    
    switch ($action) {
        case 'add':
            $address_data = [
                'full_name' => sanitize_input($_POST['full_name']),
                'phone' => sanitize_input($_POST['phone']),
                'address_line1' => sanitize_input($_POST['address_line1']),
                'address_line2' => sanitize_input($_POST['address_line2'] ?? ''),
                'city' => sanitize_input($_POST['city']),
                'state' => sanitize_input($_POST['state']),
                'country' => sanitize_input($_POST['country'] ?? 'India'),
                'postal_code' => sanitize_input($_POST['postal_code']),
                'is_default' => isset($_POST['is_default'])
            ];
            
            if (add_user_address($_SESSION['user_id'], $address_data)) {
                $_SESSION['success'] = 'Address added successfully.';
            } else {
                $_SESSION['error'] = 'Failed to add address.';
            }
            break;
            
        case 'update':
            $address_data = [
                'full_name' => sanitize_input($_POST['full_name']),
                'phone' => sanitize_input($_POST['phone']),
                'address_line1' => sanitize_input($_POST['address_line1']),
                'address_line2' => sanitize_input($_POST['address_line2'] ?? ''),
                'city' => sanitize_input($_POST['city']),
                'state' => sanitize_input($_POST['state']),
                'country' => sanitize_input($_POST['country'] ?? 'India'),
                'postal_code' => sanitize_input($_POST['postal_code']),
                'is_default' => isset($_POST['is_default'])
            ];
            
            if (update_user_address($address_id, $_SESSION['user_id'], $address_data)) {
                $_SESSION['success'] = 'Address updated successfully.';
            } else {
                $_SESSION['error'] = 'Failed to update address.';
            }
            break;
            
        case 'delete':
            if (delete_user_address($address_id, $_SESSION['user_id'])) {
                $_SESSION['success'] = 'Address deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete address.';
            }
            break;
            
        case 'set_default':
            // Unset all defaults
            db_query(
                "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?",
                [$_SESSION['user_id']],
                'i'
            );
            
            // Set new default
            db_query(
                "UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?",
                [$address_id, $_SESSION['user_id']],
                'ii'
            );
            
            $_SESSION['success'] = 'Default address updated.';
            break;
    }
    
    header('Location: ' . SITE_URL . '/dashboard/addresses.php');
    exit;
}

// Get user addresses
$addresses = get_user_addresses($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">My Addresses</h1>
            <p class="text-gray-600">Manage your shipping addresses for faster checkout</p>
        </div>
        <button type="button" onclick="openAddAddressModal()" 
                class="mt-4 md:mt-0 bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition font-medium">
            <i class="fas fa-plus mr-2"></i> Add New Address
        </button>
    </div>
    
    <!-- Addresses Grid -->
    <?php if ($addresses): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($addresses as $address): ?>
                <div class="bg-white rounded-xl shadow-md p-6 border <?php echo $address['is_default'] ? 'border-amber-500' : 'border-gray-200'; ?>">
                    <!-- Address Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($address['full_name']); ?></h3>
                            <?php if ($address['is_default']): ?>
                                <span class="inline-block mt-1 px-2 py-1 text-xs bg-amber-100 text-amber-800 rounded">
                                    Default Address
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" onclick="openEditAddressModal(<?php echo $address['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (!$address['is_default']): ?>
                                <form method="POST" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="set_default">
                                    <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                    <button type="submit" class="text-amber-600 hover:text-amber-700" title="Set as Default">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="inline" 
                                  onsubmit="return confirm('Are you sure you want to delete this address?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Address Details -->
                    <div class="text-gray-600 text-sm space-y-1">
                        <p><?php echo htmlspecialchars($address['address_line1']); ?></p>
                        <?php if ($address['address_line2']): ?>
                            <p><?php echo htmlspecialchars($address['address_line2']); ?></p>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?></p>
                        <p><?php echo htmlspecialchars($address['postal_code']); ?></p>
                        <p><?php echo htmlspecialchars($address['country']); ?></p>
                        <p class="mt-2"><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($address['phone']); ?></p>
                    </div>
                    
                    <!-- Last Updated -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            Updated: <?php echo date('M d, Y', strtotime($address['updated_at'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-map-marker-alt text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700 mb-2">No addresses yet</h3>
            <p class="text-gray-500 mb-6">Add your first address for faster checkout.</p>
            <button type="button" onclick="openAddAddressModal()" 
                    class="bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition">
                <i class="fas fa-plus mr-2"></i> Add New Address
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Address Limit Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-center">
            <i class="fas fa-info-circle text-blue-500 mr-3"></i>
            <div>
                <p class="text-sm text-blue-800">
                    You can add up to 5 addresses. Having multiple addresses helps with faster checkout.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Address Modal -->
<div id="addressModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <!-- Modal Header -->
        <div class="flex justify-between items-center mb-6">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Add New Address</h3>
            <button type="button" onclick="closeAddressModal()" 
                    class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <form id="addressForm" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" id="action" name="action" value="add">
            <input type="hidden" id="address_id" name="address_id" value="">
            
            <div class="space-y-4">
                <!-- Full Name -->
                <div>
                    <label for="modal_full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="modal_full_name" name="full_name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Phone -->
                <div>
                    <label for="modal_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input type="tel" id="modal_phone" name="phone" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Address Line 1 -->
                <div>
                    <label for="modal_address_line1" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                    <input type="text" id="modal_address_line1" name="address_line1" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- Address Line 2 -->
                <div>
                    <label for="modal_address_line2" class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                    <input type="text" id="modal_address_line2" name="address_line2"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <!-- City, State, Postal Code -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="modal_city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                        <input type="text" id="modal_city" name="city" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label for="modal_state" class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                        <input type="text" id="modal_state" name="state" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label for="modal_postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                        <input type="text" id="modal_postal_code" name="postal_code" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                
                <!-- Country -->
                <div>
                    <label for="modal_country" class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                    <select id="modal_country" name="country" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="India" selected>India</option>
                        <option value="USA">United States</option>
                        <option value="UK">United Kingdom</option>
                        <option value="Canada">Canada</option>
                        <option value="Australia">Australia</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <!-- Set as Default -->
                <div class="flex items-center">
                    <input type="checkbox" id="modal_is_default" name="is_default"
                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                    <label for="modal_is_default" class="ml-2 text-sm text-gray-700">
                        Set as default shipping address
                    </label>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeAddressModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                    Save Address
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openAddAddressModal() {
    document.getElementById('addressModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Add New Address';
    document.getElementById('action').value = 'add';
    document.getElementById('addressForm').reset();
    document.getElementById('modal_country').value = 'India';
}

function openEditAddressModal(addressId) {
    // Fetch address data via AJAX
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php?action=get_address&address_id=' + addressId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('addressModal').classList.remove('hidden');
                document.getElementById('modalTitle').textContent = 'Edit Address';
                document.getElementById('action').value = 'update';
                document.getElementById('address_id').value = addressId;
                
                // Fill form with address data
                document.getElementById('modal_full_name').value = data.address.full_name;
                document.getElementById('modal_phone').value = data.address.phone;
                document.getElementById('modal_address_line1').value = data.address.address_line1;
                document.getElementById('modal_address_line2').value = data.address.address_line2 || '';
                document.getElementById('modal_city').value = data.address.city;
                document.getElementById('modal_state').value = data.address.state;
                document.getElementById('modal_postal_code').value = data.address.postal_code;
                document.getElementById('modal_country').value = data.address.country;
                document.getElementById('modal_is_default').checked = data.address.is_default == 1;
            } else {
                alert('Failed to load address data.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        });
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('addressModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddressModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>