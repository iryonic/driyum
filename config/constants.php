<?php
// Session configuration
session_start();
define('SESSION_TIMEOUT', 3600); // 1 hour


// local
// Site constants
// define('SITE_NAME', 'Driyum');
// define('SITE_URL', 'http://localhost/driyum');
// define('ADMIN_URL', SITE_URL . '/admin');

  
// production
 
define('SITE_NAME', 'Driyum');
define('SITE_URL', 'http://localhost/driyum');
define('ADMIN_URL', SITE_URL . '/admin');

// File upload paths
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/driyum/uploads/');
define('PRODUCT_IMG_PATH', UPLOAD_PATH . 'products/');
define('BANNER_IMG_PATH', UPLOAD_PATH . 'banners/');

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB

// Security
define('CSRF_TOKEN_LIFE', 3600); // 1 hour

// Initialize session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Regenerate session ID periodically
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 1800) {
    // Regenerate session id periodically if possible
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['CREATED'] = time();
}
?>