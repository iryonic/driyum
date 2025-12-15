/**
 * Snack Store - Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize AJAX requests
    initAjax();
    
    // Initialize cart functionality
    initCart();
    
    // Initialize wishlist functionality
    initWishlist();
});

/**
 * Tooltips initialization
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.position = 'fixed';
            tooltip.style.background = 'rgba(0,0,0,0.8)';
            tooltip.style.color = 'white';
            tooltip.style.padding = '5px 10px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.fontSize = '12px';
            tooltip.style.zIndex = '9999';
            tooltip.style.top = (rect.top - 35) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            
            this.tooltipElement = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltipElement) {
                this.tooltipElement.remove();
                this.tooltipElement = null;
            }
        });
    });
}

/**
 * Form validation initialization
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                    
                    // Create error message
                    let errorMsg = field.parentNode.querySelector('.error-message');
                    if (!errorMsg) {
                        errorMsg = document.createElement('p');
                        errorMsg.className = 'error-message text-red-500 text-xs mt-1';
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.classList.remove('border-red-500');
                    const errorMsg = field.parentNode.querySelector('.error-message');
                    if (errorMsg) errorMsg.remove();
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fill in all required fields.', 'error');
            }
        });
    });
}

/**
 * AJAX initialization
 */
function initAjax() {
    // Set up CSRF token for AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (csrfToken) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }
}

/**
 * Cart functionality
 */
function initCart() {
    // Update cart quantity
    document.querySelectorAll('.update-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const cartItemId = this.dataset.cartId;
            const action = this.dataset.action;
            
            updateCartItem(cartItemId, action);
        });
    });
    
    // Remove cart item
    document.querySelectorAll('.remove-cart-item').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const cartItemId = this.dataset.cartId;
            
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                removeCartItem(cartItemId);
            }
        });
    });
}

/**
 * Update cart item quantity
 */
function updateCartItem(cartItemId, action) {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_cart&cart_id=' + cartItemId + '&type=' + action
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Remove cart item
 */
function removeCartItem(cartItemId) {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove_cart&cart_id=' + cartItemId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Wishlist functionality
 */
function initWishlist() {
    document.querySelectorAll('.toggle-wishlist').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            
            toggleWishlist(productId);
        });
    });
}

/**
 * Toggle wishlist item
 */
function toggleWishlist(productId) {
    fetch('<?php echo SITE_URL; ?>/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_wishlist&product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // Update button state
            const button = document.querySelector(`.toggle-wishlist[data-product-id="${productId}"]`);
            if (button) {
                if (data.in_wishlist) {
                    button.innerHTML = '<i class="fas fa-heart"></i>';
                    button.classList.add('text-red-500');
                } else {
                    button.innerHTML = '<i class="far fa-heart"></i>';
                    button.classList.remove('text-red-500');
                }
            }
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Remove existing toasts
    document.querySelectorAll('.toast').forEach(toast => toast.remove());
    
    // Create toast container if it doesn't exist
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast p-4 rounded-lg shadow-lg flex items-center justify-between min-w-64 max-w-md ${getToastClass(type)}`;
    
    const content = document.createElement('div');
    content.className = 'flex items-center';
    
    const icon = document.createElement('i');
    icon.className = getToastIcon(type) + ' mr-3';
    
    const text = document.createElement('span');
    text.textContent = message;
    
    content.appendChild(icon);
    content.appendChild(text);
    
    const closeBtn = document.createElement('button');
    closeBtn.className = 'ml-4 text-white opacity-75 hover:opacity-100';
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.addEventListener('click', () => toast.remove());
    
    toast.appendChild(content);
    toast.appendChild(closeBtn);
    
    container.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

/**
 * Get toast CSS class based on type
 */
function getToastClass(type) {
    switch (type) {
        case 'success': return 'bg-green-500 text-white';
        case 'error': return 'bg-red-500 text-white';
        case 'warning': return 'bg-yellow-500 text-white';
        default: return 'bg-blue-500 text-white';
    }
}

/**
 * Get toast icon based on type
 */
function getToastIcon(type) {
    switch (type) {
        case 'success': return 'fas fa-check-circle';
        case 'error': return 'fas fa-exclamation-circle';
        case 'warning': return 'fas fa-exclamation-triangle';
        default: return 'fas fa-info-circle';
    }
}

/**
 * Format price
 */
function formatPrice(price) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 2
    }).format(price);
}

/**
 * Debounce function for performance
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Toggle mobile menu
 */
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
}

/**
 * Validate email
 */
function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        showToast('Failed to copy', 'error');
    });
}

/**
 * Load more products (infinite scroll)
 */
function initInfiniteScroll() {
    let loading = false;
    let page = 2;
    const container = document.getElementById('products-container');
    const loadMoreBtn = document.getElementById('load-more');
    
    if (!container || !loadMoreBtn) return;
    
    loadMoreBtn.addEventListener('click', function() {
        if (loading) return;
        
        loading = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading...';
        this.disabled = true;
        
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        
        fetch(url)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newProducts = doc.querySelectorAll('#products-container .product-card');
                
                if (newProducts.length > 0) {
                    newProducts.forEach(product => {
                        container.appendChild(product);
                    });
                    page++;
                } else {
                    loadMoreBtn.style.display = 'none';
                }
                
                loading = false;
                loadMoreBtn.innerHTML = 'Load More';
                loadMoreBtn.disabled = false;
            })
            .catch(error => {
                loading = false;
                loadMoreBtn.innerHTML = 'Load More';
                loadMoreBtn.disabled = false;
                showToast('Failed to load more products', 'error');
            });
    });
}

// Initialize infinite scroll on page load
initInfiniteScroll();