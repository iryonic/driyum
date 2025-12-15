    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-xl font-bold mb-4 text-amber-400"><?php echo SITE_NAME; ?></h3>
                    <p class="text-gray-300 mb-4">
                        Premium quality snacks and dry fruits delivered to your doorstep. 
                        Fresh, healthy, and delicious.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-amber-400">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-amber-400">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-amber-400">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-amber-400">
                            <i class="fab fa-pinterest"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-4 text-amber-400">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-gray-300 hover:text-amber-400">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/shop.php" class="text-gray-300 hover:text-amber-400">Shop</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php" class="text-gray-300 hover:text-amber-400">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="text-gray-300 hover:text-amber-400">Contact</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/faq.php" class="text-gray-300 hover:text-amber-400">FAQ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/terms.php" class="text-gray-300 hover:text-amber-400">Terms & Conditions</a></li>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div>
                    <h4 class="text-lg font-semibold mb-4 text-amber-400">Categories</h4>
                    <ul class="space-y-2">
                        <?php $footer_categories = get_categories(); ?>
                        <?php foreach ($footer_categories as $category): ?>
                            <li>
                                <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo $category['slug']; ?>" 
                                   class="text-gray-300 hover:text-amber-400">
                                    <?php echo $category['name']; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-semibold mb-4 text-amber-400">Contact Us</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-amber-400"></i>
                            <span class="text-gray-300"><?php echo get_setting('store_address'); ?></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3 text-amber-400"></i>
                            <span class="text-gray-300"><?php echo get_setting('store_phone'); ?></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-amber-400"></i>
                            <span class="text-gray-300"><?php echo get_setting('store_email'); ?></span>
                        </li>
                    </ul>
                    
                    <!-- Newsletter -->
                    <div class="mt-6">
                        <h5 class="font-semibold mb-2">Subscribe to Newsletter</h5>
                        <form action="#" method="POST" class="flex">
                            <input type="email" placeholder="Your email" 
                                   class="px-3 py-2 rounded-l-lg text-gray-800 w-full focus:outline-none">
                            <button type="submit" class="bg-amber-600 px-4 py-2 rounded-r-lg hover:bg-amber-700">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="mt-12 pt-8 border-t border-gray-800">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <p class="text-gray-400">We accept:</p>
                        <div class="flex space-x-4 mt-2">
                            <i class="fab fa-cc-visa text-2xl text-gray-300"></i>
                            <i class="fab fa-cc-mastercard text-2xl text-gray-300"></i>
                            <i class="fab fa-cc-amex text-2xl text-gray-300"></i>
                            <i class="fab fa-cc-paypal text-2xl text-gray-300"></i>
                            <i class="fas fa-university text-2xl text-gray-300"></i>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <p class="text-gray-400">© <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                        <p class="text-gray-500 text-sm mt-1">Designed with ❤️ for snack lovers</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) == 'cart.php'): ?>
        <script src="<?php echo SITE_URL; ?>/assets/js/cart.js"></script>
    <?php endif; ?>
    
    <!-- Back to Top Button -->
    <button id="backToTop" class="fixed bottom-8 right-8 bg-amber-600 text-white p-3 rounded-full shadow-lg hover:bg-amber-700 transition hidden">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <script>
        // Back to Top
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.classList.remove('hidden');
            } else {
                backToTop.classList.add('hidden');
            }
        });
        
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown menus
            document.querySelectorAll('.group').forEach(group => {
                group.addEventListener('mouseleave', function() {
                    const dropdown = this.querySelector('.hidden');
                    if (dropdown) {
                        dropdown.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>
</html>