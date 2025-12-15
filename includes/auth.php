<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

// Check if user is logged in
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }
}

// Check if user is admin
if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

// Check if user is regular user
if (!function_exists('is_user')) {
    function is_user() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
    }
}

// Redirect if not logged in
if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
    }
}

// Redirect if not admin
if (!function_exists('require_admin')) {
    function require_admin() {
        if (!is_admin()) {
            $_SESSION['error'] = 'Access denied. Admin privileges required.';
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
    }
}

// Redirect if not user
if (!function_exists('require_user')) {
    function require_user() {
        if (!is_user()) {
            $_SESSION['error'] = 'Please login to access this page.';
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
    }
}

// Login user
if (!function_exists('login_user')) {
    function login_user($email, $password) {
    $user = db_fetch_single(
        "SELECT id, name, email, password, role, status FROM users WHERE email = ?",
        [$email],
        's'
    );
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is inactive or suspended.'];
        }
        
        // Regenerate session ID for security (only if headers not sent yet)
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Update last login (optional)
        db_query(
            "UPDATE users SET updated_at = NOW() WHERE id = ?",
            [$user['id']],
            'i'
        );
        
        return ['success' => true, 'role' => $user['role']];
    }
    
    return ['success' => false, 'message' => 'Invalid email or password.'];
    }
}

// Register new user
if (!function_exists('register_user')) {
    function register_user($name, $email, $password) {
    // Check if email exists
    $existing = db_fetch_single(
        "SELECT id FROM users WHERE email = ?",
        [$email],
        's'
    );
    
    if ($existing) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $result = db_query(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')",
        [$name, $email, $hashed_password],
        'sss'
    );
    
    if ($result) {
        $user_id = db_last_insert_id();
        
        // Auto login after registration
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['role'] = 'user';
        $_SESSION['logged_in'] = true;
        
        return ['success' => true, 'user_id' => $user_id];
    }
    
    return ['success' => false, 'message' => 'Registration failed.'];
    }
}

// Logout user
if (!function_exists('logout_user')) {
    function logout_user() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    header('Location: ' . SITE_URL);
    exit();
    }
}

// Get current user info
// NOTE: Avoid using the name `get_current_user` (PHP builtin exists). Use `get_user` instead.
if (!function_exists('get_user')) {
    function get_user() {
        if (!is_logged_in()) {
            return null;
        }

        return db_fetch_single(
            "SELECT id, name, email, role, status, phone, avatar, created_at FROM users WHERE id = ?",
            [$_SESSION['user_id']],
            'i'
        );
    }
}

// Update user profile
if (!function_exists('update_profile')) {
    function update_profile($user_id, $data) {
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['name']) && !empty($data['name'])) {
        $updates[] = "name = ?";
        $params[] = $data['name'];
        $types .= 's';
    }
    
    if (isset($data['phone']) && !empty($data['phone'])) {
        $updates[] = "phone = ?";
        $params[] = $data['phone'];
        $types .= 's';
    }
    
    if (isset($data['avatar']) && !empty($data['avatar'])) {
        $updates[] = "avatar = ?";
        $params[] = $data['avatar'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $params[] = $user_id;
    $types .= 'i';
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
    
    return db_query($sql, $params, $types);
    }
}

// Change password
if (!function_exists('change_password')) {
    function change_password($user_id, $current_password, $new_password) {
    $user = db_fetch_single(
        "SELECT password FROM users WHERE id = ?",
        [$user_id],
        'i'
    );
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $result = db_query(
        "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
        [$hashed_password, $user_id],
        'si'
    );
    
    if ($result) {
        return ['success' => true, 'message' => 'Password updated successfully.'];
    }
    
    return ['success' => false, 'message' => 'Failed to update password.'];
    }
}
?>