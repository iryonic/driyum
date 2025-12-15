<?php
// Generate CSRF token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    if ($_SESSION['csrf_token'] !== $token) {
        return false;
    }
    
    // Check token expiration (1 hour)
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFE) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

// Get CSRF token field for forms
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Validate POST request with CSRF
function validate_post_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid or expired security token. Please try again.';
        return false;
    }
    
    return true;
}
?>