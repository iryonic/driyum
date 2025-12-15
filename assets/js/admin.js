/**
 * Snack Store - Admin JavaScript
 * Contains admin panel specific functionality
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all admin functionality
    initAdminSidebar();
    initDataTables();
    initImageUploaders();
    initDeleteConfirmations();
    initStatusToggles();
    initModalHandlers();
    initChartInitialization();
});

/**
 * Initialize admin sidebar toggle
 */
function initAdminSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            if (overlay) {
                overlay.classList.toggle('hidden');
            }
        });
        
        // Close sidebar on overlay click
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }
        
        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        });
    }
}

/**
 * Initialize DataTables for admin tables
 */
function initDataTables() {
    // Check if DataTables is available
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.datatable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No matching records found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            order: [[0, 'desc']],
            responsive: true,
            autoWidth: false,
            dom: '<"flex justify-between items-center mb-4"<"flex items-center"l><"flex items-center"f>>rt<"flex justify-between items-center mt-4"<"flex items-center"i><"flex items-center"p>>'
        });
    }
}

/**
 * Initialize image upload previews
 */
function initImageUploaders() {
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (!file) return;
            
            // Find preview container
            const previewId = this.getAttribute('data-preview') || 
                             this.id + 'Preview';
            const preview = document.getElementById(previewId);
            
            if (!preview) return;
            
            // Remove existing preview
            preview.innerHTML = '';
            
            // Create new preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'max-w-full h-auto rounded-lg shadow';
                img.style.maxHeight = '200px';
                
                const container = document.createElement('div');
                container.className = 'mt-2 text-center';
                container.appendChild(img);
                
                // Add file info
                const info = document.createElement('p');
                info.className = 'text-xs text-gray-500 mt-2';
                info.textContent = `${file.name} (${formatFileSize(file.size)})`;
                container.appendChild(info);
                
                preview.appendChild(container);
            };
            reader.readAsDataURL(file);
        });
    });
}

/**
 * Initialize delete confirmation dialogs
 */
function initDeleteConfirmations() {
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const message = this.getAttribute('data-confirm') || 
                           'Are you sure you want to delete this item?';
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Initialize status toggle buttons
 */
function initStatusToggles() {
    document.querySelectorAll('[data-toggle-status]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('data-url');
            const itemId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = this.getAttribute('data-new-status');
            
            if (!url || !itemId) return;
            
            if (confirm(`Change status to ${newStatus}?`)) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id: itemId,
                        status: newStatus,
                        csrf_token: getCsrfToken()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button state
                        this.setAttribute('data-status', newStatus);
                        this.setAttribute('data-new-status', currentStatus);
                        
                        // Update button appearance
                        const statusClasses = {
                            'active': 'bg-green-100 text-green-800 hover:bg-green-200',
                            'inactive': 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                            'pending': 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                            'processing': 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                            'shipped': 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200',
                            'delivered': 'bg-green-100 text-green-800 hover:bg-green-200',
                            'cancelled': 'bg-red-100 text-red-800 hover:bg-red-200'
                        };
                        
                        // Update button text and classes
                        this.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        this.className = this.className.replace(/bg-\w+-\d+ text-\w+-\d+ hover:bg-\w+-\d+/g, 
                            statusClasses[newStatus] || 'bg-gray-100 text-gray-800 hover:bg-gray-200');
                        
                        // Show success message
                        showToast('Status updated successfully!', 'success');
                    } else {
                        showToast(data.message || 'Failed to update status', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                });
            }
        });
    });
}

/**
 * Initialize modal handlers
 */
function initModalHandlers() {
    // Open modal buttons
    document.querySelectorAll('[data-modal-toggle]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal-toggle');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        });
    });
    
    // Close modal buttons
    document.querySelectorAll('[data-modal-hide]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal-hide');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });
    
    // Close modal on background click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });
}

/**
 * Initialize charts if Chart.js is available
 */
function initChartInitialization() {
    if (typeof Chart !== 'undefined') {
        // Revenue chart
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue',
                        data: [12000, 19000, 15000, 25000, 22000, 30000],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Sales chart
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: ['Snacks', 'Dry Fruits', 'Nuts', 'Chocolates', 'Beverages'],
                    datasets: [{
                        label: 'Sales',
                        data: [65, 59, 80, 81, 56],
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(239, 68, 68, 0.7)'
                        ],
                        borderColor: [
                            '#f59e0b',
                            '#10b981',
                            '#3b82f6',
                            '#8b5cf6',
                            '#ef4444'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }
}

/**
 * AJAX file upload handler
 */
function uploadFile(input, url, onSuccess, onError) {
    const file = input.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', getCsrfToken());
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && onSuccess) {
            onSuccess(data);
        } else if (onError) {
            onError(data.message || 'Upload failed');
        }
    })
    .catch(error => {
        if (onError) onError('Network error: ' + error.message);
    });
}

/**
 * Bulk action handler
 */
function handleBulkAction(action, selectedIds) {
    if (!selectedIds.length) {
        showToast('Please select items to perform this action', 'warning');
        return;
    }
    
    const confirmationMessages = {
        'delete': 'Are you sure you want to delete the selected items?',
        'activate': 'Are you sure you want to activate the selected items?',
        'deactivate': 'Are you sure you want to deactivate the selected items?',
        'feature': 'Are you sure you want to feature the selected items?',
        'unfeature': 'Are you sure you want to remove from featured?'
    };
    
    const message = confirmationMessages[action] || 
                   'Are you sure you want to perform this action?';
    
    if (!confirm(message)) return;
    
    const url = ADMIN_URL + '/includes/ajax.php?action=bulk_' + action;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            ids: selectedIds,
            csrf_token: getCsrfToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Action completed successfully', 'success');
            // Reload page or update table
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Action failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Export data function
 */
function exportData(type, filters = {}) {
    let url = ADMIN_URL + '/includes/export.php?type=' + type;
    
    // Add filters to URL
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            url += '&' + key + '=' + encodeURIComponent(filters[key]);
        }
    });
    
    // Open in new tab to trigger download
    window.open(url, '_blank');
}

/**
 * Generate report function
 */
function generateReport(type, startDate, endDate) {
    if (!startDate || !endDate) {
        showToast('Please select start and end dates', 'warning');
        return;
    }
    
    if (startDate > endDate) {
        showToast('Start date must be before end date', 'error');
        return;
    }
    
    const url = ADMIN_URL + '/includes/report.php?type=' + type + 
                '&start=' + startDate + '&end=' + endDate;
    
    window.open(url, '_blank');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Remove existing toasts
    document.querySelectorAll('.toast').forEach(toast => toast.remove());
    
    const typeClasses = {
        'success': 'bg-green-500',
        'error': 'bg-red-500',
        'warning': 'bg-yellow-500',
        'info': 'bg-blue-500'
    };
    
    const typeIcons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-times-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast fixed top-4 right-4 ${typeClasses[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center transform translate-x-full opacity-0 transition-all duration-300`;
    toast.innerHTML = `
        <i class="${typeIcons[type]} mr-3"></i>
        <span>${message}</span>
        <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
        toast.classList.add('translate-x-0', 'opacity-100');
    }, 10);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * Get CSRF token from meta tag
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
 * Throttle function for performance
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        showToast('Failed to copy to clipboard', 'error');
    });
}

/**
 * Generate password
 */
function generatePassword(length = 12) {
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    return password;
}

/**
 * Validate email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone number (Indian format)
 */
function validatePhone(phone) {
    const re = /^[6-9]\d{9}$/;
    return re.test(phone.replace(/\D/g, ''));
}

/**
 * Format price
 */
function formatPrice(amount) {
    return '₹' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Calculate discount percentage
 */
function calculateDiscount(originalPrice, discountedPrice) {
    if (originalPrice <= 0 || discountedPrice >= originalPrice) return 0;
    return Math.round(((originalPrice - discountedPrice) / originalPrice) * 100);
}

// Global admin object
window.Admin = {
    showToast,
    copyToClipboard,
    generatePassword,
    validateEmail,
    validatePhone,
    formatPrice,
    calculateDiscount,
    exportData,
    generateReport,
    handleBulkAction,
    uploadFile
};