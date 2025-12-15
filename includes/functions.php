<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';

// Sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Generate slug
function generate_slug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Format price
function format_price($price) {
    return '₹' . number_format($price, 2);
}

// Human readable time ago
if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        if (empty($datetime)) {
            return '';
        }

        try {
            $time = new DateTime($datetime);
        } catch (Exception $e) {
            return '';
        }

        $now = new DateTime();
        $diff = $now->getTimestamp() - $time->getTimestamp();

        if ($diff < 5) {
            return 'just now';
        }

        $units = [
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];

        foreach ($units as $secs => $name) {
            if ($diff >= $secs) {
                $value = floor($diff / $secs);
                return $value . ' ' . $name . ($value > 1 ? 's' : '') . ' ago';
            }
        }

        return 'just now';
    }
}

// Calculate discount percentage
function calculate_discount_percentage($price, $compare_price) {
    if ($compare_price > $price) {
        return round((($compare_price - $price) / $compare_price) * 100);
    }
    return 0;
}

// User role badge (PHP helper for admin templates)
if (!function_exists('get_user_role_badge')) {
    function get_user_role_badge($role) {
        $badges = [
            'admin' => 'bg-purple-100 text-purple-800',
            'user' => 'bg-blue-100 text-blue-800'
        ];

        $icons = [
            'admin' => '<i class="fas fa-crown mr-1"></i> Admin',
            'user' => '<i class="fas fa-user mr-1"></i> Customer'
        ];

        $class = $badges[$role] ?? 'bg-gray-100 text-gray-800';
        $label = $icons[$role] ?? htmlspecialchars(ucfirst($role));

        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . $class . '">' . $label . '</span>';
    }
}

// User status badge (PHP helper for admin templates)
if (!function_exists('get_user_status_badge')) {
    function get_user_status_badge($status) {
        $badges = [
            'active' => 'bg-green-100 text-green-800',
            'inactive' => 'bg-yellow-100 text-yellow-800',
            'suspended' => 'bg-red-100 text-red-800'
        ];

        $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
        return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}

// Get cart count
function get_cart_count() {
    $count = 0;
    
    if (isset($_SESSION['user_id'])) {
        $result = db_fetch_single(
            "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?",
            [$_SESSION['user_id']],
            'i'
        );
        $count = $result['total'] ?? 0;
    } elseif (isset($_SESSION['cart_session_id'])) {
        $result = db_fetch_single(
            "SELECT SUM(quantity) as total FROM cart WHERE session_id = ?",
            [$_SESSION['cart_session_id']],
            's'
        );
        $count = $result['total'] ?? 0;
    }
    
    return $count;
}

// Get categories for navigation
function get_categories($parent_id = null) {
    $sql = "SELECT id, name, slug FROM categories WHERE status = 'active'";
    
    if ($parent_id === null) {
        $sql .= " AND parent_id IS NULL";
    } else {
        $sql .= " AND parent_id = ?";
        return db_fetch_all($sql, [$parent_id], 'i');
    }
    
    return db_fetch_all($sql);
}

// Get products with filters
function get_products($category_id = null, $limit = 12, $offset = 0) {
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active'";
    
    $params = [];
    $types = '';
    
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    return db_fetch_all($sql, $params, $types);
}

// Get featured products
function get_featured_products($limit = 8) {
    return db_fetch_all(
        "SELECT * FROM products WHERE is_featured = 1 AND status = 'active' ORDER BY created_at DESC LIMIT ?",
        [$limit],
        'i'
    );
}

// Get single product
function get_product($product_id) {
    return db_fetch_single(
        "SELECT p.*, c.name as category_name, c.slug as category_slug 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.id = ?",
        [$product_id],
        'i'
    );
}

// Get product by slug
function get_product_by_slug($slug) {
    return db_fetch_single(
        "SELECT p.*, c.name as category_name, c.slug as category_slug 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.slug = ?",
        [$slug],
        's'
    );
}

// Add to cart
function add_to_cart($product_id, $quantity = 1) {
    $product = get_product($product_id);
    
    if (!$product || $product['stock_quantity'] < $quantity) {
        return false;
    }
    
    $cart_id = null;
    
    if (isset($_SESSION['user_id'])) {
        // User is logged in
        $existing = db_fetch_single(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$_SESSION['user_id'], $product_id],
            'ii'
        );
        
        if ($existing) {
            $new_quantity = $existing['quantity'] + $quantity;
            db_query(
                "UPDATE cart SET quantity = ?, added_at = NOW() WHERE id = ?",
                [$new_quantity, $existing['id']],
                'ii'
            );
            $cart_id = $existing['id'];
        } else {
            db_query(
                "INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
                [$_SESSION['user_id'], $product_id, $quantity, $product['price']],
                'iiid'
            );
            $cart_id = db_last_insert_id();
        }
    } else {
        // Guest user
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = session_id();
        }
        
        $existing = db_fetch_single(
            "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND user_id IS NULL",
            [$_SESSION['cart_session_id'], $product_id],
            'si'
        );
        
        if ($existing) {
            $new_quantity = $existing['quantity'] + $quantity;
            db_query(
                "UPDATE cart SET quantity = ?, added_at = NOW() WHERE id = ?",
                [$new_quantity, $existing['id']],
                'ii'
            );
            $cart_id = $existing['id'];
        } else {
            db_query(
                "INSERT INTO cart (session_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
                [$_SESSION['cart_session_id'], $product_id, $quantity, $product['price']],
                'siid'
            );
            $cart_id = db_last_insert_id();
        }
    }
    
    return $cart_id;
}



// Get cart items
function get_cart_items() {
    $items = [];
    
    if (isset($_SESSION['user_id'])) {
        $items = db_fetch_all(
            "SELECT c.*, p.name, p.slug, p.image_main, p.stock_quantity 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ? AND p.status = 'active'",
            [$_SESSION['user_id']],
            'i'
        );
    } elseif (isset($_SESSION['cart_session_id'])) {
        $items = db_fetch_all(
            "SELECT c.*, p.name, p.slug, p.image_main, p.stock_quantity 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.session_id = ? AND c.user_id IS NULL AND p.status = 'active'",
            [$_SESSION['cart_session_id']],
            's'
        );
    }
    
    return $items;
}

// Calculate cart total
function calculate_cart_total() {
    $items = get_cart_items();
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

// Remove from cart
function remove_from_cart($cart_item_id) {
    if (isset($_SESSION['user_id'])) {
        return db_query(
            "DELETE FROM cart WHERE id = ? AND user_id = ?",
            [$cart_item_id, $_SESSION['user_id']],
            'ii'
        );
    } else {
        return db_query(
            "DELETE FROM cart WHERE id = ? AND session_id = ? AND user_id IS NULL",
            [$cart_item_id, $_SESSION['cart_session_id']],
            'is'
        );
    }
}

// Update cart quantity
function update_cart_quantity($cart_item_id, $quantity) {
    if ($quantity <= 0) {
        return remove_from_cart($cart_item_id);
    }
    
    if (isset($_SESSION['user_id'])) {
        return db_query(
            "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?",
            [$quantity, $cart_item_id, $_SESSION['user_id']],
            'iii'
        );
    } else {
        return db_query(
            "UPDATE cart SET quantity = ? WHERE id = ? AND session_id = ? AND user_id IS NULL",
            [$quantity, $cart_item_id, $_SESSION['cart_session_id']],
            'iis'
        );
    }
}

// Generate order number
function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
}

// Create order
function create_order($user_id, $address_id, $cart_items, $coupon_code = null) {
    $connection = get_db_connection();
    
    // Start transaction
    mysqli_begin_transaction($connection);
    
    try {
        // Calculate totals
        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Get tax rate from settings
        $tax_rate = db_fetch_single("SELECT setting_value FROM settings WHERE setting_key = 'tax_rate'")['setting_value'] ?? 18;
        $tax_amount = ($subtotal * $tax_rate) / 100;
        
        // Get shipping amount
        $free_shipping_amount = db_fetch_single("SELECT setting_value FROM settings WHERE setting_key = 'free_shipping_amount'")['setting_value'] ?? 500;
        $shipping_amount = db_fetch_single("SELECT setting_value FROM settings WHERE setting_key = 'shipping_amount'")['setting_value'] ?? 50;
        
        if ($subtotal >= $free_shipping_amount) {
            $shipping_amount = 0;
        }
        
        // Apply coupon if valid
        $discount_amount = 0;
        if ($coupon_code) {
            $coupon = validate_coupon($coupon_code, $subtotal);
            if ($coupon['valid']) {
                if ($coupon['type'] === 'percentage') {
                    $discount = ($subtotal * $coupon['value']) / 100;
                    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                        $discount = $coupon['max_discount'];
                    }
                } else {
                    $discount = $coupon['value'];
                }
                $discount_amount = $discount;
            }
        }
        
        $total_amount = $subtotal + $tax_amount + $shipping_amount - $discount_amount;
        
        // Create order
        $order_number = generate_order_number();
        $order_sql = "INSERT INTO orders (order_number, user_id, address_id, subtotal, tax_amount, 
                     shipping_amount, discount_amount, total_amount) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_order = db_query($order_sql, [
            $order_number, $user_id, $address_id, $subtotal, $tax_amount,
            $shipping_amount, $discount_amount, $total_amount
        ], 'siiddddd');
        
        if ($insert_order === false) {
            throw new Exception('Failed to create order: ' . mysqli_error($connection));
        }
        
        $order_id = db_last_insert_id();
        if (empty($order_id)) {
            throw new Exception('Failed to obtain new order ID after insert.');
        }
        
        // Add order items and update stock
        foreach ($cart_items as $item) {
            // Add order item
            $insert_item = db_query(
                "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price'], 
                 $item['price'] * $item['quantity']],
                'iisiid'
            );
            if ($insert_item === false) {
                throw new Exception('Failed to insert order item for product ID ' . $item['product_id'] . ': ' . mysqli_error($connection));
            }
            
            // Update product stock
            $update_stock = db_query(
                "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
                [$item['quantity'], $item['product_id']],
                'ii'
            );
            if ($update_stock === false) {
                throw new Exception('Failed to update stock for product ID ' . $item['product_id'] . ': ' . mysqli_error($connection));
            }
        }
        
        // Clear cart (handle both logged-in users and guest sessions)
        if (!empty($user_id)) {
            $delete_result = db_query("DELETE FROM cart WHERE user_id = ?", [$user_id], 'i');
            if ($delete_result === false) {
                throw new Exception('Failed to clear user cart: ' . mysqli_error($connection));
            }
        } else {
            if (isset($_SESSION['cart_session_id'])) {
                $delete_result = db_query("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL", [$_SESSION['cart_session_id']], 's');
                if ($delete_result === false) {
                    throw new Exception('Failed to clear guest cart: ' . mysqli_error($connection));
                }
            }
        }
        
        // Record coupon usage (if applicable) - guard against missing coupon row
        if ($coupon_code && $discount_amount > 0) {
            $coupon_row = db_fetch_single(
                "SELECT id FROM coupons WHERE code = ?",
                [$coupon_code],
                's'
            );
            
            if ($coupon_row && !empty($coupon_row['id'])) {
                $coupon_id = $coupon_row['id'];
                $insert_coupon_usage = db_query(
                    "INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) 
                     VALUES (?, ?, ?, ?)",
                    [$coupon_id, $user_id, $order_id, $discount_amount],
                    'iiid'
                );
                if ($insert_coupon_usage === false) {
                    throw new Exception('Failed to record coupon usage: ' . mysqli_error($connection));
                }
                
                $update_coupon = db_query(
                    "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?",
                    [$coupon_id],
                    'i'
                );
                if ($update_coupon === false) {
                    throw new Exception('Failed to update coupon usage count: ' . mysqli_error($connection));
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($connection);
        
        return [
            'success' => true,
            'order_id' => $order_id,
            'order_number' => $order_number
        ];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($connection);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Validate coupon
function validate_coupon($code, $subtotal) {
    $coupon = db_fetch_single(
        "SELECT * FROM coupons 
         WHERE code = ? 
         AND status = 'active' 
         AND valid_from <= CURDATE() 
         AND valid_to >= CURDATE() 
         AND (usage_limit IS NULL OR used_count < usage_limit)",
        [$code],
        's'
    );
    
    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid or expired coupon.'];
    }
    
    if ($subtotal < $coupon['min_order_amount']) {
        return [
            'valid' => false, 
            'message' => 'Minimum order amount: ' . format_price($coupon['min_order_amount'])
        ];
    }
    
    return [
        'valid' => true,
        'type' => $coupon['discount_type'],
        'value' => $coupon['discount_value'],
        'max_discount' => $coupon['max_discount_amount']
    ];
}

// Get user orders
function get_user_orders($user_id, $limit = 10, $offset = 0) {
    return db_fetch_all(
        "SELECT o.*, 
         (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
         FROM orders o 
         WHERE o.user_id = ? 
         ORDER BY o.created_at DESC 
         LIMIT ? OFFSET ?",
        [$user_id, $limit, $offset],
        'iii'
    );
}

// Get order details
function get_order_details($order_id, $user_id = null) {
    $sql = "SELECT o.*, a.*, 
            (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_items
            FROM orders o 
            JOIN user_addresses a ON o.address_id = a.id 
            WHERE o.id = ?";
    
    $params = [$order_id];
    $types = 'i';
    
    if ($user_id) {
        $sql .= " AND o.user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    return db_fetch_single($sql, $params, $types);
}

// Get order items
function get_order_items($order_id) {
    return db_fetch_all(
        "SELECT oi.*, p.slug, p.image_main 
         FROM order_items oi 
         LEFT JOIN products p ON oi.product_id = p.id 
         WHERE oi.order_id = ?",
        [$order_id],
        'i'
    );
}

// Add to wishlist
function add_to_wishlist($user_id, $product_id) {
    $existing = db_fetch_single(
        "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$user_id, $product_id],
        'ii'
    );
    
    if ($existing) {
        return false; // Already in wishlist
    }
    
    return db_query(
        "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)",
        [$user_id, $product_id],
        'ii'
    );
}

// Remove from wishlist
function remove_from_wishlist($user_id, $product_id) {
    return db_query(
        "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$user_id, $product_id],
        'ii'
    );
}

// Get user wishlist
function get_wishlist($user_id) {
    return db_fetch_all(
        "SELECT w.*, p.name, p.slug, p.price, p.compare_price, p.image_main, p.stock_quantity 
         FROM wishlist w 
         JOIN products p ON w.product_id = p.id 
         WHERE w.user_id = ? AND p.status = 'active'",
        [$user_id],
        'i'
    );
}

// Check if product is in wishlist
function is_in_wishlist($user_id, $product_id) {
    $result = db_fetch_single(
        "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$user_id, $product_id],
        'ii'
    );
    return $result !== null;
}

// Get user addresses
function get_user_addresses($user_id) {
    return db_fetch_all(
        "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC",
        [$user_id],
        'i'
    );
}

// Add user address
function add_user_address($user_id, $address_data) {
    // If setting as default, unset other defaults
    if ($address_data['is_default']) {
        db_query(
            "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?",
            [$user_id],
            'i'
        );
    }
    
    return db_query(
        "INSERT INTO user_addresses (user_id, full_name, phone, address_line1, address_line2, 
         city, state, country, postal_code, is_default) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $user_id, $address_data['full_name'], $address_data['phone'], 
            $address_data['address_line1'], $address_data['address_line2'] ?? '',
            $address_data['city'], $address_data['state'], 
            $address_data['country'] ?? 'India', $address_data['postal_code'],
            $address_data['is_default'] ? 1 : 0
        ],
        'issssssssi'
    );
}

// Add guest address (user_id = NULL)
function add_guest_address($address_data) {
    return db_query(
        "INSERT INTO user_addresses (user_id, full_name, phone, address_line1, address_line2, 
         city, state, country, postal_code, is_default) 
         VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
        [
            $address_data['full_name'], $address_data['phone'], 
            $address_data['address_line1'], $address_data['address_line2'] ?? '',
            $address_data['city'], $address_data['state'], 
            $address_data['country'] ?? 'India', $address_data['postal_code']
        ],
        'ssssssss'
    );
}

// Update user address
function update_user_address($address_id, $user_id, $address_data) {
    // If setting as default, unset other defaults
    if ($address_data['is_default']) {
        db_query(
            "UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?",
            [$user_id, $address_id],
            'ii'
        );
    }
    
    return db_query(
        "UPDATE user_addresses SET 
         full_name = ?, phone = ?, address_line1 = ?, address_line2 = ?, 
         city = ?, state = ?, country = ?, postal_code = ?, is_default = ?,
         updated_at = NOW()
         WHERE id = ? AND user_id = ?",
        [
            $address_data['full_name'], $address_data['phone'], 
            $address_data['address_line1'], $address_data['address_line2'] ?? '',
            $address_data['city'], $address_data['state'], 
            $address_data['country'] ?? 'India', $address_data['postal_code'],
            $address_data['is_default'] ? 1 : 0,
            $address_id, $user_id
        ],
        'ssssssssiii'
    );
}

// Delete user address
function delete_user_address($address_id, $user_id) {
    return db_query(
        "DELETE FROM user_addresses WHERE id = ? AND user_id = ?",
        [$address_id, $user_id],
        'ii'
    );
}

// Get site setting
function get_setting($key) {
    $result = db_fetch_single(
        "SELECT setting_value FROM settings WHERE setting_key = ?",
        [$key],
        's'
    );
    return $result['setting_value'] ?? null;
}

// Upload image
function upload_image($file, $type = 'product') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > MAX_IMAGE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Max 5MB allowed.'];
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp'];
    }
    
    // Create upload directory if not exists
    $upload_dir = $type === 'product' ? PRODUCT_IMG_PATH : BANNER_IMG_PATH;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Return web-root relative path for database (e.g., '/driyum/uploads/products/..')
        $web_root = rtrim(parse_url(SITE_URL, PHP_URL_PATH) ?: '', '/');
        $relative_path = $web_root . '/uploads/' . ($type === 'product' ? 'products/' : 'banners/') . $filename;
        return ['success' => true, 'path' => $relative_path];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

// Delete image file
function delete_image_file($path) {
    // If a full URL was stored, extract the path component
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        $path = parse_url($path, PHP_URL_PATH);
    }

    // Build full filesystem path and delete
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (file_exists($full_path) && is_file($full_path)) {
        return unlink($full_path);
    }
    return false;
}

// Normalize image path for public use
function get_image_src($path) {
    // Return placeholder for empty
    if (empty($path)) {
        return SITE_URL . '/assets/images/placeholder.jpg';
    }

    // If it's already a full URL, return as-is
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }

    // If a legacy path like '/uploads/...', prepend site path (e.g., '/driyum')
    $site_path = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
    if (strpos($path, '/uploads/') === 0 && $site_path) {
        return $site_path . $path;
    }

    // If already an absolute path (starts with '/'), return it
    if ($path[0] === '/') {
        return $path;
    }

    // Otherwise return as-is (relative path)
    return $path;
}

// Get pagination links
function get_pagination($total_items, $items_per_page, $current_page, $url) {
    $total_pages = ceil($total_items / $items_per_page);
    
    $pagination = [
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'previous_page' => $current_page > 1 ? $current_page - 1 : null,
        'next_page' => $current_page < $total_pages ? $current_page + 1 : null,
        'pages' => []
    ];
    
    // Calculate page range
    $range = 2;
    $start = max(1, $current_page - $range);
    $end = min($total_pages, $current_page + $range);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination['pages'][] = [
            'number' => $i,
            'url' => $url . '?page=' . $i,
            'is_current' => $i == $current_page
        ];
    }
    
    return $pagination;
}

// Send email (simulated - in production use PHPMailer or similar)
function send_email($to, $subject, $body) {
    // This is a simulation. In production, implement actual email sending.
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . get_setting('store_email') . "\r\n";
    
    // For development, log instead of sending
    error_log("Email to: $to\nSubject: $subject\nBody: $body\n");
    
    // Uncomment to actually send in production
    // return mail($to, $subject, $body, $headers);
    return true;
}

// Send order confirmation email
function send_order_confirmation($order_id, $fallback_email = null) {
    $order = get_order_details($order_id);
    $items = get_order_items($order_id);
    
    if (!$order) {
        return false;
    }
    
    $user = null;
    if (!empty($order['user_id'])) {
        $user = db_fetch_single(
            "SELECT email, name FROM users WHERE id = ?",
            [$order['user_id']],
            'i'
        );
    }

    // Fallback to provided email or order address name
    if (!$user) {
        $user = [
            'email' => $fallback_email,
            'name' => $order['full_name'] ?? 'Customer'
        ];
    }
    
    // If we don't have an email, skip sending
    if (empty($user['email'])) {
        return false;
    }
    
    $subject = "Order Confirmation - " . $order['order_number'];
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f8f9fa; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-details { width: 100%; border-collapse: collapse; }
            .order-details th, .order-details td { padding: 10px; border: 1px solid #ddd; }
            .order-details th { background: #f8f9fa; }
            .total { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Order Confirmation</h2>
                <p>Thank you for your order!</p>
            </div>
            <div class="content">
                <p>Hello ' . $user['name'] . ',</p>
                <p>Your order has been confirmed. Here are your order details:</p>
                
                <h3>Order #' . $order['order_number'] . '</h3>
                <p>Order Date: ' . date('F j, Y', strtotime($order['created_at'])) . '</p>
                
                <h4>Shipping Address</h4>
                <p>' . $order['full_name'] . '<br>
                   ' . $order['address_line1'] . '<br>
                   ' . ($order['address_line2'] ? $order['address_line2'] . '<br>' : '') . '
                   ' . $order['city'] . ', ' . $order['state'] . ' ' . $order['postal_code'] . '<br>
                   ' . $order['country'] . '</p>
                
                <h4>Order Items</h4>
                <table class="order-details">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($items as $item) {
        $body .= '<tr>
                    <td>' . $item['product_name'] . '</td>
                    <td>' . $item['quantity'] . '</td>
                    <td>₹' . number_format($item['price'], 2) . '</td>
                    <td>₹' . number_format($item['total'], 2) . '</td>
                  </tr>';
    }
    
    $body .= '</tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" align="right">Subtotal:</td>
                        <td>₹' . number_format($order['subtotal'], 2) . '</td>
                    </tr>
                    <tr>
                        <td colspan="3" align="right">Tax:</td>
                        <td>₹' . number_format($order['tax_amount'], 2) . '</td>
                    </tr>
                    <tr>
                        <td colspan="3" align="right">Shipping:</td>
                        <td>₹' . number_format($order['shipping_amount'], 2) . '</td>
                    </tr>
                    <tr>
                        <td colspan="3" align="right">Discount:</td>
                        <td>-₹' . number_format($order['discount_amount'], 2) . '</td>
                    </tr>
                    <tr class="total">
                        <td colspan="3" align="right">Total:</td>
                        <td>₹' . number_format($order['total_amount'], 2) . '</td>
                    </tr>
                </tfoot>
            </table>
            
            <h4>Payment Method</h4>
            <p>' . ucfirst($order['payment_method']) . '</p>
            
            <h4>Order Status</h4>
            <p>' . ucfirst($order['order_status']) . '</p>
            
            <p>You can track your order from your dashboard.</p>
            <p>Thank you for shopping with us!</p>
            <p><strong>' . get_setting('store_name') . '</strong></p>
        </div>
    </div>
    </body>
    </html>';
    
    return send_email($user['email'], $subject, $body);
}

// Log admin activity
function log_admin_activity($admin_id, $action, $description = '') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    return db_query(
        "INSERT INTO admin_logs (admin_id, action, description, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?)",
        [$admin_id, $action, $description, $ip_address, $user_agent],
        'issss'
    );
}


?>

