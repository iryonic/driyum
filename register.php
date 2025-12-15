<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/dashboard/');
    exit;
}

$page_title = 'Register';
$breadcrumbs = [['title' => 'Register']];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        $result = register_user($name, $email, $password);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Registration successful! Welcome to ' . SITE_NAME . '.';
            header('Location: ' . SITE_URL . '/dashboard/');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Create Account</h1>
            <p class="text-gray-600">Join us for exclusive deals and faster checkout</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo SITE_URL; ?>/register.php">
            <?php echo csrf_field(); ?>
            
            <!-- Name -->
            <div class="mb-6">
                <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                       placeholder="John Doe">
            </div>
            
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
                       placeholder="At least 6 characters">
                <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters long</p>
            </div>
            
            <!-- Confirm Password -->
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                       placeholder="Confirm your password">
            </div>
            
            <!-- Terms Agreement -->
            <div class="mb-6">
                <div class="flex items-start">
                    <input type="checkbox" id="terms" name="terms" required 
                           class="h-4 w-4 mt-1 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                    <label for="terms" class="ml-2 text-gray-700 text-sm">
                        I agree to the 
                        <a href="<?php echo SITE_URL; ?>/terms.php" class="text-amber-600 hover:text-amber-700">Terms of Service</a> 
                        and 
                        <a href="<?php echo SITE_URL; ?>/privacy.php" class="text-amber-600 hover:text-amber-700">Privacy Policy</a>
                    </label>
                </div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full bg-amber-600 text-white py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                Create Account
            </button>
        </form>
        
        <!-- Login Link -->
        <div class="mt-8 text-center">
            <p class="text-gray-600">
                Already have an account? 
                <a href="<?php echo SITE_URL; ?>/login.php" class="text-amber-600 hover:text-amber-700 font-medium">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>