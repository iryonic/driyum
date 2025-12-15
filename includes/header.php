<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Premium snacks and dry fruits online store'; ?>">
    <meta name="keywords" content="snacks, dry fruits, nuts, chocolates, online shopping">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Premium snacks and dry fruits online store'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
            <button onclick="this.parentElement.remove()" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
            <button onclick="this.parentElement.remove()" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Top Bar -->
    <div class="bg-amber-800 text-white py-2 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <!-- Desktop contact info -->
            <div class="text-sm hidden sm:block">
                <i class="fas fa-phone mr-2"></i> <?php echo get_setting('store_phone'); ?>
                <span class="mx-2">|</span>
                <i class="fas fa-envelope mr-2"></i> <?php echo get_setting('store_email'); ?>
            </div>
            <!-- Mobile compact icons -->
            <div class="text-sm sm:hidden flex items-center space-x-4">
                <a href="tel:<?php echo get_setting('store_phone'); ?>" class="hover:text-amber-200">
                    <i class="fas fa-phone"></i>
                </a>
                <a href="mailto:<?php echo get_setting('store_email'); ?>" class="hover:text-amber-200">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>

            <div class="text-sm">
                <a href="<?php echo SITE_URL; ?>/contact.php" class="hover:text-amber-200 mr-4">
                    <i class="fas fa-headset mr-1"></i> Support
                </a>
                <a href="<?php echo SITE_URL; ?>/faq.php" class="hover:text-amber-200">
                    <i class="fas fa-question-circle mr-1"></i> FAQ
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <!-- Mobile Hamburger (small screens) -->
                <button class="md:hidden mr-3 text-gray-700 focus:outline-none" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                    <i class="fas fa-bars text-2xl"></i>
                </button>

                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo SITE_URL; ?>" class="flex items-center">
                        <img src="<?php echo SITE_URL; ?>/assets/images/driyumlogo.jpg" alt="<?php echo SITE_NAME; ?>" class="h-10 mr-3">
                        <span class="text-2xl font-bold text-amber-700"><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                
                <!-- Search Bar -->
                <div class="hidden md:block w-1/3">
                    <form action="<?php echo SITE_URL; ?>/shop.php" method="GET" class="relative">
                        <input type="text" name="search" placeholder="Search for snacks, dry fruits..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-amber-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- User Actions -->
                <div class="flex items-center space-x-6">
                    <!-- Cart -->
                    <a href="<?php echo SITE_URL; ?>/cart.php" class="relative text-gray-700 hover:text-amber-600">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php $cart_count = get_cart_count(); ?>
                        <?php if ($cart_count > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-amber-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- User Account -->
                    <?php if (is_logged_in()): ?>
                        <div class="relative group">
                            <button class="flex items-center text-gray-700 hover:text-amber-600 focus:outline-none">
                                <i class="fas fa-user-circle text-xl mr-2"></i>
                                <span class="hidden md:inline"><?php echo $_SESSION['user_name']; ?></span>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-10 hidden group-hover:block hover:block">
                                <?php if (is_user()): ?>
                                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="block px-4 py-2 text-gray-800 hover:bg-amber-50 hover:text-amber-700">
                                        <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/dashboard/orders.php" class="block px-4 py-2 text-gray-800 hover:bg-amber-50 hover:text-amber-700">
                                        <i class="fas fa-shopping-bag mr-2"></i> Orders
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/dashboard/wishlist.php" class="block px-4 py-2 text-gray-800 hover:bg-amber-50 hover:text-amber-700">
                                        <i class="fas fa-heart mr-2"></i> Wishlist
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/dashboard/profile.php" class="block px-4 py-2 text-gray-800 hover:bg-amber-50 hover:text-amber-700">
                                        <i class="fas fa-user-edit mr-2"></i> Profile
                                    </a>
                                    <div class="border-t my-1"></div>
                                <?php endif; ?>
                                
                                <?php if (is_admin()): ?>
                                    <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-amber-50 hover:text-amber-700">
                                        <i class="fas fa-cog mr-2"></i> Admin Panel
                                    </a>
                                    <div class="border-t my-1"></div>
                                <?php endif; ?>
                                
                                <a href="<?php echo SITE_URL; ?>/logout.php" class="block px-4 py-2 text-gray-800 hover:bg-amber-50 hover:text-amber-700">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-4">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="text-gray-700 hover:text-amber-600">
                                <i class="fas fa-sign-in-alt mr-1"></i> Login
                            </a>
                            <a href="<?php echo SITE_URL; ?>/register.php" class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition">
                                <i class="fas fa-user-plus mr-1"></i> Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mobile Search -->
            <div class="md:hidden mt-4">
                <form action="<?php echo SITE_URL; ?>/shop.php" method="GET" class="relative">
                    <input type="text" name="search" placeholder="Search for snacks, dry fruits..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-amber-600">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="bg-amber-700 text-white">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center">
                    <!-- Main Menu -->
                    <div class="flex items-center space-x-6 py-3">
                        <a href="<?php echo SITE_URL; ?>" class="hover:bg-amber-800 px-3 py-2 rounded transition <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-amber-800' : ''; ?>">
                            <i class="fas fa-home mr-2"></i> Home
                        </a>
                        <a href="<?php echo SITE_URL; ?>/shop.php" class="hover:bg-amber-800 px-3 py-2 rounded transition <?php echo basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'bg-amber-800' : ''; ?>">
                            <i class="fas fa-store mr-2"></i> Shop
                        </a>
                        
                        <!-- Categories Dropdown -->
                        <?php $categories = get_categories(); ?>
                        <?php if ($categories): ?>
                            <div class="relative group">
                                <button class="hover:bg-amber-800 px-3 py-2 rounded transition flex items-center">
                                    <i class="fas fa-list mr-2"></i> Categories
                                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                </button>
                                <div class="absolute left-0 mt-1 w-48 bg-white text-gray-800 rounded-lg shadow-lg py-2 z-10 hidden group-hover:block hover:block">
                                    <?php foreach ($categories as $category): ?>
                                        <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo $category['slug']; ?>" 
                                           class="block px-4 py-2 hover:bg-amber-50 hover:text-amber-700">
                                            <?php echo $category['name']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php echo SITE_URL; ?>/about.php" class="hover:bg-amber-800 px-3 py-2 rounded transition <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'bg-amber-800' : ''; ?>">
                            <i class="fas fa-info-circle mr-2"></i> About
                        </a>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="hover:bg-amber-800 px-3 py-2 rounded transition <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'bg-amber-800' : ''; ?>">
                            <i class="fas fa-address-book mr-2"></i> Contact
                        </a>
                    </div>
                    
                    <!-- Promo Text -->
                    <div class="hidden md:block">
                        <span class="text-amber-200 text-sm">
                            <i class="fas fa-truck mr-1"></i> Free Shipping on orders above ₹<?php echo get_setting('free_shipping_amount'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu (hidden by default) -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-4 py-4">
                <!-- Mobile Search -->
                <form action="<?php echo SITE_URL; ?>/shop.php" method="GET" class="relative mb-4">
                    <input type="text" name="search" placeholder="Search for snacks, dry fruits..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-amber-600">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <div class="space-y-2">
                    <a href="<?php echo SITE_URL; ?>" class="block px-3 py-2 rounded hover:bg-gray-50">Home</a>
                    <a href="<?php echo SITE_URL; ?>/shop.php" class="block px-3 py-2 rounded hover:bg-gray-50">Shop</a>
                    <?php $mobile_cats = get_categories(); ?>
                    <?php if ($mobile_cats): ?>
                        <div class="pt-2 border-t">
                            <div class="text-sm font-medium text-gray-600 px-3 py-2">Categories</div>
                            <?php foreach ($mobile_cats as $mc): ?>
                                <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo $mc['slug']; ?>" class="block px-3 py-2 hover:bg-gray-50"><?php echo $mc['name']; ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/about.php" class="block px-3 py-2 rounded hover:bg-gray-50">About</a>
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="block px-3 py-2 rounded hover:bg-gray-50">Contact</a>

                    <div class="pt-2 border-t">
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo SITE_URL; ?>/dashboard/" class="block px-3 py-2 hover:bg-gray-50">Dashboard</a>
                            <a href="<?php echo SITE_URL; ?>/dashboard/orders.php" class="block px-3 py-2 hover:bg-gray-50">Orders</a>
                            <a href="<?php echo SITE_URL; ?>/dashboard/wishlist.php" class="block px-3 py-2 hover:bg-gray-50">Wishlist</a>
                            <a href="<?php echo SITE_URL; ?>/logout.php" class="block px-3 py-2 hover:bg-gray-50">Logout</a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/login.php" class="block px-3 py-2 hover:bg-gray-50">Login</a>
                            <a href="<?php echo SITE_URL; ?>/register.php" class="block px-3 py-2 hover:bg-gray-50">Register</a>
                        <?php endif; ?>
                    </div>

                    <div class="pt-2 border-t">
                        <a href="<?php echo SITE_URL; ?>/cart.php" class="flex items-center px-3 py-2 hover:bg-gray-50">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Cart
                            <?php $cart_count_mobile = get_cart_count(); if ($cart_count_mobile > 0): ?>
                                <span class="ml-auto bg-amber-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $cart_count_mobile; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="pt-2 border-t">
                        <span class="text-xs text-amber-600">Free Shipping on orders above ₹<?php echo get_setting('free_shipping_amount'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Breadcrumbs -->
    <?php if (isset($breadcrumbs)): ?>
        <div class="bg-gray-100 py-3">
            <div class="container mx-auto px-4">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <a href="<?php echo SITE_URL; ?>" class="text-gray-500 hover:text-amber-600">
                                <i class="fas fa-home"></i>
                            </a>
                        </li>
                        <?php foreach ($breadcrumbs as $crumb): ?>
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <?php if (isset($crumb['url'])): ?>
                                    <a href="<?php echo $crumb['url']; ?>" class="text-gray-500 hover:text-amber-600">
                                        <?php echo $crumb['title']; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-amber-600 font-medium"><?php echo $crumb['title']; ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">