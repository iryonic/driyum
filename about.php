<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'About Us';
$breadcrumbs = [
    ['title' => 'About Us']
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-amber-700 to-amber-900 rounded-2xl overflow-hidden mb-12">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1579113800032-c38bd7635818?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80" 
                 alt="About Us Banner" class="w-full h-full object-cover opacity-30">
        </div>
        <div class="relative px-8 py-20 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">About Snack Store</h1>
            <p class="text-xl text-amber-100 max-w-3xl mx-auto">
                Your trusted destination for premium quality snacks and dry fruits since 2010.
                We bring nature's best to your doorstep with love and care.
            </p>
        </div>
    </div>
    
    <!-- Our Story -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Our Story</h2>
            <div class="space-y-4 text-gray-600">
                <p>
                    Founded in 2010, Snack Store began with a simple mission: to provide 
                    high-quality, healthy snacks that everyone can enjoy. What started as 
                    a small family business has grown into one of India's most trusted 
                    online snack stores.
                </p>
                <p>
                    We carefully source our products from the best farms and producers across 
                    the country. Each product is selected for its quality, taste, and 
                    nutritional value.
                </p>
                <p>
                    Our commitment to quality, customer satisfaction, and ethical sourcing 
                    has helped us build lasting relationships with thousands of happy 
                    customers across India.
                </p>
            </div>
        </div>
        <div class="order-first lg:order-last">
            <img src="https://images.unsplash.com/photo-1567306226416-28f0efdc88ce?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                 alt="Our Story" class="w-full h-96 object-cover rounded-2xl shadow-lg">
        </div>
    </div>
    
    <!-- Our Values -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-800 mb-12 text-center">Our Values</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-leaf text-2xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Quality & Freshness</h3>
                <p class="text-gray-600">
                    We guarantee the freshness and quality of every product. 
                    Our snacks are packaged with care to preserve their natural goodness.
                </p>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-heart text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Customer First</h3>
                <p class="text-gray-600">
                    Your satisfaction is our priority. We go above and beyond to ensure 
                    you have the best shopping experience with us.
                </p>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-recycle text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Sustainable Practices</h3>
                <p class="text-gray-600">
                    We're committed to sustainability. Our packaging is eco-friendly, 
                    and we work with farmers who practice sustainable agriculture.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Our Team -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-800 mb-12 text-center">Meet Our Team</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="w-32 h-32 mx-auto mb-4">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" 
                         alt="Founder" class="w-full h-full object-cover rounded-full">
                </div>
                <h3 class="font-semibold text-gray-800">Rajesh Kumar</h3>
                <p class="text-sm text-gray-600">Founder & CEO</p>
            </div>
            
            <div class="text-center">
                <div class="w-32 h-32 mx-auto mb-4">
                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" 
                         alt="Head of Operations" class="w-full h-full object-cover rounded-full">
                </div>
                <h3 class="font-semibold text-gray-800">Priya Sharma</h3>
                <p class="text-sm text-gray-600">Head of Operations</p>
            </div>
            
            <div class="text-center">
                <div class="w-32 h-32 mx-auto mb-4">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" 
                         alt="Quality Manager" class="w-full h-full object-cover rounded-full">
                </div>
                <h3 class="font-semibold text-gray-800">Amit Patel</h3>
                <p class="text-sm text-gray-600">Quality Control Manager</p>
            </div>
            
            <div class="text-center">
                <div class="w-32 h-32 mx-auto mb-4">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" 
                         alt="Customer Support" class="w-full h-full object-cover rounded-full">
                </div>
                <h3 class="font-semibold text-gray-800">Neha Gupta</h3>
                <p class="text-sm text-gray-600">Customer Support Head</p>
            </div>
        </div>
    </div>
    
    <!-- Why Choose Us -->
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Why Choose Snack Store?</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="flex items-start">
                <div class="bg-amber-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-shipping-fast text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">Fast Delivery</h3>
                    <p class="text-gray-600 text-sm">Delivery across India in 3-7 days</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="bg-amber-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-shield-alt text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">100% Quality</h3>
                    <p class="text-gray-600 text-sm">Quality assurance on all products</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="bg-amber-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-undo-alt text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">Easy Returns</h3>
                    <p class="text-gray-600 text-sm">7-day return policy</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="bg-amber-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-headset text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-2">24/7 Support</h3>
                    <p class="text-gray-600 text-sm">Always here to help you</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="text-center mt-16">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Ready to Experience Quality Snacks?</h2>
        <a href="<?php echo SITE_URL; ?>/shop.php" 
           class="inline-block bg-amber-600 text-white px-8 py-3 rounded-lg hover:bg-amber-700 transition text-lg font-medium">
            Start Shopping Now
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>