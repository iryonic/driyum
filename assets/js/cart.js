/**
 * Snack Store - Cart JavaScript
 * Handles shopping cart functionality
 */

// Cart state
let cartState = {
    items: [],
    subtotal: 0,
    tax: 0,
    shipping: 0,
    discount: 0,
    total: 0,
    couponCode: null,
    couponApplied: false
};

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart
    initCart();
    
    // Load cart items if on cart page
    if (document.getElementById('cartPage')) {
        loadCartItems();
    }
    
    // Initialize event listeners
    initCartEventListeners();
    
    // Initialize quantity controls
    initQuantityControls();
    
    // Initialize coupon functionality
    initCouponFunctionality();
    
    // Initialize proceed to checkout
    initCheckoutProceed();
});

/**
 * Initialize cart
 */
function initCart() {
    // Try to load cart from localStorage
    const savedCart = localStorage.getItem('snackStoreCart');
    if (savedCart) {
        try {
            cartState = JSON.parse(savedCart);
            updateCartCount();
        } catch (e) {
            console.error('Failed to load cart from localStorage:', e);
        }
    }
    
    // Initialize cart count display
    updateCartCount();
}

/**
 * Load cart items from server
 */
function loadCartItems() {
    fetch(SITE_URL + '/includes/ajax.php?action=get_cart')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cartState = data.cart;
                renderCartItems();
                updateCartSummary();
                saveCartToStorage();
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            showCartError('Failed to load cart items');
        });
}

/**
 * Render cart items to the page
 */
function renderCartItems() {
    const cartItemsContainer = document.getElementById('cartItems');
    const emptyCartMessage = document.getElementById('emptyCart');
    
    if (!cartItemsContainer) return;
    
    if (!cartState.items || cartState.items.length === 0) {
        if (emptyCartMessage) emptyCartMessage.classList.remove('hidden');
        cartItemsContainer.innerHTML = '';
        return;
    }
    
    if (emptyCartMessage) emptyCartMessage.classList.add('hidden');
    
    let html = '';
    cartState.items.forEach(item => {
        html += `
            <div class="cart-item bg-white rounded-xl shadow-sm p-4 mb-4" data-item-id="${item.id}">
                <div class="flex flex-col md:flex-row items-start md:items-center">
                    <!-- Product Image -->
                    <div class="md:w-24 md:h-24 w-full h-48 mb-4 md:mb-0 md:mr-6">
                        <img src="${item.image_main || '/assets/images/placeholder.jpg'}" 
                             alt="${item.name}"
                             class="w-full h-full object-cover rounded-lg">
                    </div>
                    
                    <!-- Product Details -->
                    <div class="flex-1 mb-4 md:mb-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-800 mb-1">
                                    <a href="${SITE_URL}/product.php?slug=${item.slug}" 
                                       class="hover:text-amber-600">
                                        ${item.name}
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-500 mb-2">SKU: ${item.sku || 'N/A'}</p>
                                ${item.stock_quantity < 5 ? 
                                    `<p class="text-xs text-amber-600 mb-2">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Only ${item.stock_quantity} left in stock
                                    </p>` : ''}
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-medium text-gray-800">
                                    ₹${parseFloat(item.price).toFixed(2)}
                                </div>
                                ${item.compare_price > item.price ? 
                                    `<div class="text-sm text-gray-500 line-through">
                                        ₹${parseFloat(item.compare_price).toFixed(2)}
                                    </div>` : ''}
                            </div>
                        </div>
                        
                        <!-- Quantity Controls -->
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center">
                                <button type="button" onclick="updateQuantity(${item.id}, -1)" 
                                        class="quantity-btn w-8 h-8 flex items-center justify-center border border-gray-300 rounded-l-lg hover:bg-gray-50">
                                    <i class="fas fa-minus text-gray-600"></i>
                                </button>
                                <input type="number" min="1" max="${item.stock_quantity}" 
                                       value="${item.quantity}"
                                       data-item-id="${item.id}"
                                       class="quantity-input w-12 h-8 text-center border-t border-b border-gray-300 focus:outline-none">
                                <button type="button" onclick="updateQuantity(${item.id}, 1)" 
                                        class="quantity-btn w-8 h-8 flex items-center justify-center border border-gray-300 rounded-r-lg hover:bg-gray-50">
                                    <i class="fas fa-plus text-gray-600"></i>
                                </button>
                                <span class="text-sm text-gray-500 ml-2">
                                    Max: ${item.stock_quantity}
                                </span>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="text-lg font-medium text-gray-800">
                                    ₹${(item.price * item.quantity).toFixed(2)}
                                </div>
                                <button type="button" onclick="removeFromCart(${item.id})" 
                                        class="text-red-600 hover:text-red-700">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    cartItemsContainer.innerHTML = html;
    
    // Re-initialize quantity input listeners
    initQuantityInputs();
}

/**
 * Update cart summary
 */
function updateCartSummary() {
    // Update summary values
    document.getElementById('cartSubtotal').textContent = '₹' + cartState.subtotal.toFixed(2);
    document.getElementById('cartTax').textContent = '₹' + cartState.tax.toFixed(2);
    document.getElementById('cartShipping').textContent = cartState.shipping === 0 ? 'FREE' : '₹' + cartState.shipping.toFixed(2);
    document.getElementById('cartDiscount').textContent = cartState.discount > 0 ? '-₹' + cartState.discount.toFixed(2) : '₹0.00';
    document.getElementById('cartTotal').textContent = '₹' + cartState.total.toFixed(2);
    
    // Update free shipping message
    const freeShippingAmount = parseFloat(document.getElementById('freeShippingAmount')?.value || 500);
    const freeShippingMessage = document.getElementById('freeShippingMessage');
    const freeShippingProgress = document.getElementById('freeShippingProgress');
    
    if (freeShippingMessage && freeShippingProgress) {
        if (cartState.subtotal >= freeShippingAmount) {
            freeShippingMessage.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-2"></i> Free shipping unlocked!';
            freeShippingProgress.style.width = '100%';
        } else {
            const remaining = freeShippingAmount - cartState.subtotal;
            const percentage = (cartState.subtotal / freeShippingAmount) * 100;
            freeShippingMessage.textContent = `Add ₹${remaining.toFixed(2)} more for free shipping`;
            freeShippingProgress.style.width = `${Math.min(percentage, 100)}%`;
        }
    }
    
    // Update cart count in header
    updateCartCount();
}

/**
 * Update cart count in header
 */
function updateCartCount() {
    const cartCountElements = document.querySelectorAll('.cart-count');
    const totalItems = cartState.items?.reduce((sum, item) => sum + item.quantity, 0) || 0;
    
    cartCountElements.forEach(element => {
        element.textContent = totalItems;
        if (totalItems > 0) {
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    });
}

/**
 * Add item to cart
 */
function addToCart(productId, quantity = 1) {
    // Show loading state
    const button = event?.target || document.querySelector(`[data-add-to-cart="${productId}"]`);
    const originalText = button?.innerHTML;
    
    if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
        button.disabled = true;
    }
    
    fetch(SITE_URL + '/includes/ajax.php?action=add_to_cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity,
            csrf_token: getCsrfToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart state
            cartState = data.cart;
            
            // Show success message
            showToast('Item added to cart!', 'success');
            
            // Update UI
            updateCartCount();
            saveCartToStorage();
            
            // If on cart page, reload items
            if (document.getElementById('cartPage')) {
                loadCartItems();
            }
            
            // Show mini cart if available
            showMiniCart();
        } else {
            showToast(data.message || 'Failed to add item to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        // Restore button state
        if (button) {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

/**
 * Update item quantity
 */
function updateQuantity(itemId, change) {
    const quantityInput = document.querySelector(`.quantity-input[data-item-id="${itemId}"]`);
    const currentQuantity = parseInt(quantityInput?.value || 0);
    const newQuantity = currentQuantity + change;
    
    if (newQuantity < 1) {
        removeFromCart(itemId);
        return;
    }
    
    // Get max quantity from data attribute
    const maxQuantity = parseInt(quantityInput?.max || 999);
    if (newQuantity > maxQuantity) {
        showToast(`Maximum quantity is ${maxQuantity}`, 'warning');
        return;
    }
    
    updateCartItemQuantity(itemId, newQuantity);
}

/**
 * Update cart item quantity via AJAX
 */
function updateCartItemQuantity(itemId, quantity) {
    fetch(SITE_URL + '/includes/ajax.php?action=update_cart_quantity', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            cart_item_id: itemId,
            quantity: quantity,
            csrf_token: getCsrfToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cartState = data.cart;
            renderCartItems();
            updateCartSummary();
            saveCartToStorage();
            showToast('Quantity updated', 'success');
        } else {
            showToast(data.message || 'Failed to update quantity', 'error');
            // Reload cart to get correct state
            loadCartItems();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Remove item from cart
 */
function removeFromCart(itemId) {
    if (!confirm('Remove this item from cart?')) return;
    
    fetch(SITE_URL + '/includes/ajax.php?action=remove_from_cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            cart_item_id: itemId,
            csrf_token: getCsrfToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove item from DOM
            const itemElement = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
            if (itemElement) {
                itemElement.classList.add('removing');
                setTimeout(() => {
                    itemElement.remove();
                    // Check if cart is empty
                    const cartItems = document.querySelectorAll('.cart-item');
                    if (cartItems.length === 0) {
                        document.getElementById('emptyCart')?.classList.remove('hidden');
                    }
                }, 300);
            }
            
            // Update cart state
            cartState = data.cart;
            updateCartSummary();
            saveCartToStorage();
            showToast('Item removed from cart', 'success');
        } else {
            showToast(data.message || 'Failed to remove item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Initialize event listeners
 */
function initCartEventListeners() {
    // Add to cart buttons
    document.querySelectorAll('[data-add-to-cart]').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-add-to-cart');
            const quantity = this.getAttribute('data-quantity') || 1;
            addToCart(productId, parseInt(quantity));
        });
    });
    
    // Quick add to cart buttons
    document.querySelectorAll('.quick-add-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.closest('[data-product-id]')?.getAttribute('data-product-id');
            if (productId) {
                addToCart(productId, 1);
            }
        });
    });
    
    // View cart button
    document.getElementById('viewCartBtn')?.addEventListener('click', function() {
        window.location.href = SITE_URL + '/cart.php';
    });
    
    // Continue shopping button
    document.getElementById('continueShoppingBtn')?.addEventListener('click', function() {
        window.location.href = SITE_URL + '/shop.php';
    });
    
    // Clear cart button
    document.getElementById('clearCartBtn')?.addEventListener('click', function() {
        if (confirm('Clear all items from cart?')) {
            fetch(SITE_URL + '/includes/ajax.php?action=clear_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: getCsrfToken()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cartState = data.cart;
                    renderCartItems();
                    updateCartSummary();
                    saveCartToStorage();
                    showToast('Cart cleared', 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to clear cart', 'error');
            });
        }
    });
}

/**
 * Initialize quantity controls
 */
function initQuantityControls() {
    // Delegate quantity button events
    document.addEventListener('click', function(e) {
        if (e.target.closest('.quantity-btn')) {
            const button = e.target.closest('.quantity-btn');
            const input = button.closest('.flex').querySelector('.quantity-input');
            const itemId = input.getAttribute('data-item-id');
            const change = button.querySelector('.fa-minus') ? -1 : 1;
            
            updateQuantity(itemId, change);
        }
    });
}

/**
 * Initialize quantity inputs
 */
function initQuantityInputs() {
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const itemId = this.getAttribute('data-item-id');
            const quantity = parseInt(this.value);
            
            if (isNaN(quantity) || quantity < 1) {
                this.value = 1;
                updateCartItemQuantity(itemId, 1);
                return;
            }
            
            const max = parseInt(this.max || 999);
            if (quantity > max) {
                this.value = max;
                updateCartItemQuantity(itemId, max);
                return;
            }
            
            updateCartItemQuantity(itemId, quantity);
        });
        
        input.addEventListener('blur', function() {
            const value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.value = 1;
            }
        });
    });
}

/**
 * Initialize coupon functionality
 */
function initCouponFunctionality() {
    const applyCouponBtn = document.getElementById('applyCouponBtn');
    const couponInput = document.getElementById('couponCode');
    const removeCouponBtn = document.getElementById('removeCouponBtn');
    
    if (applyCouponBtn && couponInput) {
        applyCouponBtn.addEventListener('click', applyCoupon);
        
        // Allow pressing Enter in coupon input
        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyCoupon();
            }
        });
    }
    
    if (removeCouponBtn) {
        removeCouponBtn.addEventListener('click', removeCoupon);
    }
}

/**
 * Apply coupon code
 */
function applyCoupon() {
    const couponCode = document.getElementById('couponCode').value.trim();
    const couponMessage = document.getElementById('couponMessage');
    
    if (!couponCode) {
        if (couponMessage) {
            couponMessage.innerHTML = '<span class="text-red-600">Please enter a coupon code</span>';
            couponMessage.classList.remove('hidden');
        }
        return;
    }
    
    // Show loading
    const applyBtn = document.getElementById('applyCouponBtn');
    const originalText = applyBtn.innerHTML;
    applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Applying...';
    applyBtn.disabled = true;
    
    fetch(SITE_URL + '/includes/ajax.php?action=apply_coupon', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            coupon_code: couponCode,
            csrf_token: getCsrfToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart state
            cartState = data.cart;
            cartState.couponCode = couponCode;
            cartState.couponApplied = true;
            
            // Update UI
            updateCartSummary();
            saveCartToStorage();
            
            // Show success message
            if (couponMessage) {
                couponMessage.innerHTML = `<span class="text-green-600">Coupon applied successfully! Saved ₹${data.discount.toFixed(2)}</span>`;
                couponMessage.classList.remove('hidden');
            }
            
            // Show remove coupon button
            document.getElementById('removeCouponBtn')?.classList.remove('hidden');
            document.getElementById('couponCode').disabled = true;
            applyBtn.classList.add('hidden');
            
            showToast('Coupon applied successfully!', 'success');
        } else {
            if (couponMessage) {
                couponMessage.innerHTML = `<span class="text-red-600">${data.message}</span>`;
                couponMessage.classList.remove('hidden');
            }
            showToast(data.message || 'Invalid coupon code', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (couponMessage) {
            couponMessage.innerHTML = '<span class="text-red-600">An error occurred. Please try again.</span>';
            couponMessage.classList.remove('hidden');
        }
        showToast('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        applyBtn.innerHTML = originalText;
        applyBtn.disabled = false;
    });
}

/**
 * Remove applied coupon
 */
function removeCoupon() {
    const couponCode = document.getElementById('couponCode').value;
    const couponMessage = document.getElementById('couponMessage');
    
    fetch(SITE_URL + '/includes/ajax.php?action=remove_coupon', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            coupon_code: couponCode,
            csrf_token: getCsrfToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart state
            cartState = data.cart;
            cartState.couponCode = null;
            cartState.couponApplied = false;
            
            // Update UI
            updateCartSummary();
            saveCartToStorage();
            
            // Reset coupon form
            document.getElementById('couponCode').value = '';
            document.getElementById('couponCode').disabled = false;
            document.getElementById('applyCouponBtn').classList.remove('hidden');
            document.getElementById('removeCouponBtn').classList.add('hidden');
            
            if (couponMessage) {
                couponMessage.innerHTML = '<span class="text-green-600">Coupon removed</span>';
                couponMessage.classList.remove('hidden');
                setTimeout(() => couponMessage.classList.add('hidden'), 2000);
            }
            
            showToast('Coupon removed', 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to remove coupon', 'error');
    });
}

/**
 * Initialize proceed to checkout
 */
function initCheckoutProceed() {
    const proceedCheckoutBtn = document.getElementById('proceedCheckoutBtn');
    
    if (proceedCheckoutBtn) {
        proceedCheckoutBtn.addEventListener('click', function(e) {
            // Check if cart is empty
            if (!cartState.items || cartState.items.length === 0) {
                e.preventDefault();
                showToast('Your cart is empty', 'warning');
                return;
            }
            
            // Check if user is logged in
            const isLoggedIn = document.body.getAttribute('data-user-logged-in') === 'true';
            const guestCheckoutEnabled = document.body.getAttribute('data-guest-checkout') === 'true';
            
            if (!isLoggedIn && !guestCheckoutEnabled) {
                e.preventDefault();
                showToast('Please login to checkout', 'warning');
                setTimeout(() => {
                    window.location.href = SITE_URL + '/login.php?redirect=' + encodeURIComponent('/checkout.php');
                }, 1500);
            }
            
            // Optional: Save cart before proceeding
            saveCartToStorage();
        });
    }
}

/**
 * Show mini cart
 */
function showMiniCart() {
    const miniCart = document.getElementById('miniCart');
    if (!miniCart) return;
    
    // Update mini cart content
    const miniCartItems = miniCart.querySelector('.mini-cart-items');
    const miniCartTotal = miniCart.querySelector('.mini-cart-total');
    
    if (miniCartItems && miniCartTotal) {
        if (cartState.items && cartState.items.length > 0) {
            let itemsHtml = '';
            cartState.items.slice(0, 3).forEach(item => {
                itemsHtml += `
                    <div class="flex items-center py-3 border-b border-gray-100">
                        <div class="w-12 h-12 flex-shrink-0 mr-3">
                            <img src="${item.image_main || '/assets/images/placeholder.jpg'}" 
                                 alt="${item.name}"
                                 class="w-full h-full object-cover rounded">
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-800 truncate">${item.name}</h4>
                            <p class="text-xs text-gray-500">
                                ${item.quantity} × ₹${item.price.toFixed(2)}
                            </p>
                        </div>
                        <div class="text-sm font-medium text-gray-800">
                            ₹${(item.price * item.quantity).toFixed(2)}
                        </div>
                    </div>
                `;
            });
            
            if (cartState.items.length > 3) {
                itemsHtml += `
                    <div class="text-center py-3 text-sm text-gray-500">
                        +${cartState.items.length - 3} more items
                    </div>
                `;
            }
            
            miniCartItems.innerHTML = itemsHtml;
            miniCartTotal.textContent = '₹' + cartState.total.toFixed(2);
            
            // Show mini cart
            miniCart.classList.remove('hidden');
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                miniCart.classList.add('hidden');
            }, 3000);
        }
    }
}

/**
 * Save cart to localStorage
 */
function saveCartToStorage() {
    try {
        localStorage.setItem('snackStoreCart', JSON.stringify(cartState));
    } catch (e) {
        console.error('Failed to save cart to localStorage:', e);
    }
}

/**
 * Show cart error
 */
function showCartError(message) {
    const errorContainer = document.getElementById('cartError');
    if (errorContainer) {
        errorContainer.innerHTML = `
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                ${message}
            </div>
        `;
        errorContainer.classList.remove('hidden');
    }
}

/**
 * Show toast notification (reuse from admin.js or create simple version)
 */
function showToast(message, type = 'info') {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300`;
    
    const typeClasses = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'warning': 'bg-yellow-500 text-white',
        'info': 'bg-blue-500 text-white'
    };
    
    toast.className += ' ' + (typeClasses[type] || 'bg-gray-800 text-white');
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
        toast.classList.add('translate-x-0', 'opacity-100');
    }, 10);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Get CSRF token
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Global cart object
window.Cart = {
    addToCart,
    updateQuantity,
    removeFromCart,
    applyCoupon,
    removeCoupon,
    updateCartSummary,
    showMiniCart,
    loadCartItems
};