<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Contact Us';
$breadcrumbs = [
    ['title' => 'Contact Us']
];

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required.';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required.';
    }
    
    if (empty($errors)) {
        // Save contact message to database
        db_query(
            "INSERT INTO contact_messages (name, email, phone, subject, message) 
             VALUES (?, ?, ?, ?, ?)",
            [$name, $email, $phone, $subject, $message],
            'sssss'
        );
        
        // Send email notification (in production)
        $to = get_setting('contact_email') ?? get_setting('store_email') ?? 'info@snackstore.com';
        $email_subject = "New Contact Message: $subject";
        $email_body = "
        New contact form submission:
        
        Name: $name
        Email: $email
        Phone: $phone
        Subject: $subject
        
        Message:
        $message
        
        ---
        This message was sent from the contact form on " . SITE_NAME . "
        ";
        
        // For development, log the email
        error_log("Contact form submission: $email_subject\n$email_body");
        
        $_SESSION['success'] = 'Thank you for your message! We will get back to you soon.';
        header('Location: ' . SITE_URL . '/contact.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Get in Touch</h1>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            Have questions or need assistance? We're here to help! Reach out to us and we'll respond as soon as possible.
        </p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contact Information -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Contact Information</h2>
                
                <div class="space-y-6">
                    <!-- Address -->
                    <div class="flex items-start">
                        <div class="bg-amber-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-map-marker-alt text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Our Location</h3>
                            <p class="text-gray-600"><?php echo get_setting('store_address') ?? '123 Snack Street, Food City'; ?></p>
                        </div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="flex items-center">
                        <div class="bg-amber-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-phone text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Phone Number</h3>
                            <p class="text-gray-600"><?php echo get_setting('store_phone') ?? '+91 9876543210'; ?></p>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="flex items-center">
                        <div class="bg-amber-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-envelope text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Email Address</h3>
                            <p class="text-gray-600"><?php echo get_setting('store_email') ?? 'info@snackstore.com'; ?></p>
                        </div>
                    </div>
                    
                    <!-- Hours -->
                    <div class="flex items-start">
                        <div class="bg-amber-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-clock text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Business Hours</h3>
                            <div class="text-gray-600">
                                <p>Monday - Friday: 9:00 AM - 8:00 PM</p>
                                <p>Saturday - Sunday: 10:00 AM - 6:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h3 class="font-semibold text-gray-800 mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="<?php echo get_setting('facebook_url') ?? '#'; ?>" 
                           class="bg-blue-100 text-blue-600 p-3 rounded-lg hover:bg-blue-200 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo get_setting('twitter_url') ?? '#'; ?>" 
                           class="bg-blue-100 text-blue-400 p-3 rounded-lg hover:bg-blue-200 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="<?php echo get_setting('instagram_url') ?? '#'; ?>" 
                           class="bg-pink-100 text-pink-600 p-3 rounded-lg hover:bg-pink-200 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Send us a Message</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo SITE_URL; ?>/contact.php">
                    <?php echo csrf_field(); ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Your Name *</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>
                        
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>
                        
                        <!-- Subject -->
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                            <select id="subject" name="subject" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                <option value="">Select a subject</option>
                                <option value="Order Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Order Inquiry') ? 'selected' : ''; ?>>Order Inquiry</option>
                                <option value="Product Question" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Product Question') ? 'selected' : ''; ?>>Product Question</option>
                                <option value="Shipping & Delivery" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Shipping & Delivery') ? 'selected' : ''; ?>>Shipping & Delivery</option>
                                <option value="Returns & Refunds" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Returns & Refunds') ? 'selected' : ''; ?>>Returns & Refunds</option>
                                <option value="Account Support" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Account Support') ? 'selected' : ''; ?>>Account Support</option>
                                <option value="Wholesale Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Wholesale Inquiry') ? 'selected' : ''; ?>>Wholesale Inquiry</option>
                                <option value="Other" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Message -->
                    <div class="mt-6">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Your Message *</label>
                        <textarea id="message" name="message" rows="6" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                  placeholder="Please provide details about your inquiry..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="mt-8">
                        <button type="submit" 
                                class="w-full bg-amber-600 text-white py-3 rounded-lg hover:bg-amber-700 transition font-medium">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- FAQ Section -->
            <div class="mt-8 bg-gray-50 rounded-xl p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Frequently Asked Questions</h3>
                
                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-800 mb-1">How long does shipping take?</h4>
                        <p class="text-gray-600 text-sm">Most orders are delivered within 3-7 business days across India.</p>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-800 mb-1">What is your return policy?</h4>
                        <p class="text-gray-600 text-sm">We offer a 7-day return policy for unopened products in original packaging.</p>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-800 mb-1">Do you offer wholesale pricing?</h4>
                        <p class="text-gray-600 text-sm">Yes, we offer special pricing for bulk orders. Please contact us for details.</p>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo SITE_URL; ?>/faq.php" 
                           class="text-amber-600 hover:text-amber-700 font-medium">
                            View All FAQ <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>