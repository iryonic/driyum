<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Frequently Asked Questions';
$breadcrumbs = [
    ['title' => 'FAQ']
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Frequently Asked Questions</h1>
        <p class="text-gray-600 text-lg">
            Find answers to common questions about shopping with us
        </p>
    </div>
    
    <!-- Search FAQ -->
    <div class="mb-12">
        <div class="relative max-w-2xl mx-auto">
            <input type="text" id="faqSearch" 
                   placeholder="Search for questions..." 
                   class="w-full px-6 py-4 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-amber-600">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
    
    <!-- FAQ Categories -->
    <div class="mb-8">
        <div class="flex flex-wrap justify-center gap-2">
            <button type="button" onclick="filterFAQ('all')" 
                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                All Questions
            </button>
            <button type="button" onclick="filterFAQ('ordering')" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Ordering
            </button>
            <button type="button" onclick="filterFAQ('shipping')" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Shipping & Delivery
            </button>
            <button type="button" onclick="filterFAQ('returns')" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Returns & Refunds
            </button>
            <button type="button" onclick="filterFAQ('account')" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Account & Payment
            </button>
            <button type="button" onclick="filterFAQ('products')" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Products & Quality
            </button>
        </div>
    </div>
    
    <!-- FAQ List -->
    <div class="space-y-4" id="faqList">
        <!-- Ordering Questions -->
        <div class="faq-item" data-category="ordering">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">How do I place an order?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        To place an order:<br>
                        1. Browse our products and add items to your cart<br>
                        2. Click the cart icon and review your items<br>
                        3. Proceed to checkout<br>
                        4. Enter your shipping information<br>
                        5. Choose payment method and complete your order<br>
                        You'll receive an order confirmation email with all details.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="ordering">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">Can I modify or cancel my order?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        You can modify or cancel your order within 1 hour of placing it. 
                        After that, the order enters processing and cannot be changed. 
                        To request modification or cancellation, contact our customer 
                        support immediately with your order number.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Shipping Questions -->
        <div class="faq-item" data-category="shipping">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">How long does shipping take?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        Standard shipping takes 3-7 business days across India. 
                        Express shipping (2-3 days) is available at an additional cost. 
                        Delivery times may vary based on location and season. 
                        You'll receive tracking information once your order is shipped.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="shipping">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">What are your shipping charges?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        Standard shipping: ₹50 per order<br>
                        Free shipping on orders above ₹500<br>
                        Express shipping: ₹100 (2-3 business days)<br>
                        Shipping charges are calculated at checkout based on your location.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Returns Questions -->
        <div class="faq-item" data-category="returns">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">What is your return policy?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        We offer a 7-day return policy from the date of delivery. 
                        Products must be unopened, unused, and in original packaging. 
                        Perishable items cannot be returned unless damaged or defective. 
                        To initiate a return, contact our customer support with your order number.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="returns">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">How do I get a refund?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        Once we receive and inspect your returned item, 
                        we will process your refund within 5-7 business days. 
                        Refunds will be issued to the original payment method. 
                        You will receive a confirmation email once the refund is processed.
                    </p>
                </div>
            </div>
        </div>

        <!-- Account Questions -->
        <div class="faq-item" data-category="account">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">How do I create an account?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        To create an account, click on the "Sign Up" link at the top right corner of our website. 
                        Fill in your details including name, email, and password. 
                        After submitting the form, you'll receive a confirmation email. 
                        Click the link in the email to verify your account and start shopping!
                    </p>
                </div>
            </div>


        </div>
        <div class="faq-item" data-category="account">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">What payment methods do you accept?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        We accept the following payment methods:<br>
                        - Credit/Debit Cards (Visa, MasterCard, American Express)<br>
                        - Net Banking<br>
                        - UPI (Google Pay, PhonePe, Paytm)<br>
                        - Cash on Delivery (COD) for select locations<br>
                        All payments are processed securely to ensure your information is safe.
                    </p>
                </div>
            </div>


        </div>
        <!-- Products Questions -->
        <div class="faq-item" data-category="products">
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">Are your products safe and of high quality?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        Yes, we prioritize quality and safety. 
                        All our products undergo strict quality checks and comply with food safety standards. 
                        We source our snacks from reputable manufacturers to ensure freshness and taste. 
                        If you have any concerns about a product, please contact our customer support.
                    </p>
                </div>
            </div>

        </div>
        <div class="faq-item" data-category="products">     
            <div class="bg-white rounded-xl shadow-md border border-gray-200">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 rounded-xl"
                        onclick="toggleFAQ(this)">
                    <span class="font-medium text-gray-800">Do you offer product customization?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div class="px-6 py-4 border-t border-gray-200 hidden">
                    <p class="text-gray-600">
                        Currently, we do not offer product customization. 
                        However, we have a wide variety of snacks to choose from. 
                        If you have specific preferences or dietary needs, 
                        feel free to reach out to our customer support for recommendations.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    // Toggle FAQ answer visibility
    function toggleFAQ(button) {
        const answer = button.nextElementSibling;
        const icon = button.querySelector('i');
        if (answer.classList.contains('hidden')) {
            answer.classList.remove('hidden');
            icon.classList.add('transform', 'rotate-180');
        } else {
            answer.classList.add('hidden');
            icon.classList.remove('transform', 'rotate-180');
        }
    }

    // Filter FAQ by category
    function filterFAQ(category) {
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            if (category === 'all' || item.getAttribute('data-category') === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Search FAQ
    document.getElementById('faqSearch').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('button span').textContent.toLowerCase();
            if (question.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>