<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . ADMIN_URL . '/dashboard.php');
    } else {
        header('Location: ' . SITE_URL . '/dashboard/');
    }
    exit;
}

$page_title = 'Login';
$breadcrumbs = [['title' => 'Login']];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = login_user($email, $password);
        
        if ($result['success']) {
            // Redirect based on role
            $redirect = $_SESSION['redirect_to'] ?? '';
            
            if ($result['role'] === 'admin') {
                header('Location: ' . ADMIN_URL . '/dashboard.php');
            } elseif (!empty($redirect)) {
                header('Location: ' . $redirect);
                unset($_SESSION['redirect_to']);
            } else {
                header('Location: ' . SITE_URL . '/dashboard/');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h1>
            <p class="text-gray-600">Sign in to your account to continue</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo SITE_URL; ?>/login.php">
            <?php echo csrf_field(); ?>
            
            <!-- Email -->
            <div class="mb-6">
                <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                       placeholder="you@example.com">
            </div>
            
            <!-- Password -->
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" id="password" name="password" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                       placeholder="Enter your password">
            </div>
            
            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" 
                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 text-gray-700">Remember me</label>
                </div>
                <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="text-amber-600 hover:text-amber-700">
                    Forgot password?
                </a>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-amber-600 text-white py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                Sign In
            </button>
        </form>
        
        <!-- Divider -->
        <div class="mt-8 mb-6 relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500">Or continue with</span>
            </div>
        </div>
        
        <!-- Admin Login Link -->
        <div class="text-center">
            <a href="<?php echo ADMIN_URL; ?>/login.php" class="text-gray-600 hover:text-amber-600">
                <i class="fas fa-user-shield mr-2"></i> Admin Login
            </a>
        </div>
        
        <!-- Register Link -->
        <div class="mt-8 text-center">
            <p class="text-gray-600">
                Don't have an account? 
                <a href="<?php echo SITE_URL; ?>/register.php" class="text-amber-600 hover:text-amber-700 font-medium">
                    Sign up now
                </a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>