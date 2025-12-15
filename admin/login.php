<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

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

require_once __DIR__ . '/../includes/admin-header.php';

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
        <form method="POST" action="">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 mb-2">Password</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div class="mb-6 flex items-center">
                <input type="checkbox" id="remember" name="remember" class="mr-2">
                <label for="remember" class="text-gray-700">Remember Me</label>
            </div>
            <button type="submit" class="w-full bg-amber-600 text-white py-2 rounded-lg hover:bg-amber-700 transition duration-300">Login</button>
        </form>
    </div>
</div>
<?php
require_once __DIR__ . '/../includes/admin-footer.php';
?>
