            </main>
            
            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <p class="text-sm text-gray-600">
                            © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-shield-alt mr-1"></i> Secure Admin Panel v1.0
                        </p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button onclick="clearCache()" 
                                class="text-sm text-gray-600 hover:text-amber-600">
                            <i class="fas fa-broom mr-1"></i> Clear Cache
                        </button>
                        <a href="<?php echo ADMIN_URL; ?>/reports.php" 
                           class="text-sm text-gray-600 hover:text-amber-600">
                            <i class="fas fa-chart-line mr-1"></i> Reports
                        </a>
                        <a href="<?php echo ADMIN_URL; ?>/settings.php" 
                           class="text-sm text-gray-600 hover:text-amber-600">
                            <i class="fas fa-cog mr-1"></i> Settings
                        </a>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-server mr-2"></i>
                            <span>MySQL: <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?>MB</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.tailwindcss.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin JavaScript -->
    <script src="<?php echo ADMIN_URL; ?>/assets/js/admin.js"></script>
    
    <!-- Initialize Select2 -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'classic',
                width: '100%'
            });
        });
    </script>
    
    <!-- Admin Panel JavaScript -->
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        });
        
        // Close sidebar on overlay click
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.add('-translate-x-full');
            this.classList.add('hidden');
        });
        
        // Toggle notifications dropdown
        document.getElementById('notificationButton').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notificationDropdown').classList.toggle('hidden');
            document.getElementById('userDropdown').classList.add('hidden');
        });
        
        // Toggle user dropdown
        document.getElementById('userMenuButton').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('userDropdown').classList.toggle('hidden');
            document.getElementById('notificationDropdown').classList.add('hidden');
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#notificationButton') && !e.target.closest('#notificationDropdown')) {
                document.getElementById('notificationDropdown').classList.add('hidden');
            }
            if (!e.target.closest('#userMenuButton') && !e.target.closest('#userDropdown')) {
                document.getElementById('userDropdown').classList.add('hidden');
            }
        });
        
        // Mark notification as read
        function markNotificationAsRead(notificationId) {
            fetch('<?php echo ADMIN_URL; ?>/includes/ajax.php?action=mark_notification_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    notification_id: notificationId,
                    csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove notification from UI
                    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.remove();
                    }
                    
                    // Update notification count
                    updateNotificationCount();
                }
            });
        }
        
        // Update notification count
        function updateNotificationCount() {
            fetch('<?php echo ADMIN_URL; ?>/includes/ajax.php?action=get_notification_count')
                .then(response => response.json())
                .then(data => {
                    const notificationDot = document.querySelector('.notification-dot');
                    if (data.count > 0) {
                        if (!notificationDot) {
                            const bellIcon = document.querySelector('#notificationButton i.fa-bell');
                            const dot = document.createElement('span');
                            dot.className = 'absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full notification-dot';
                            document.getElementById('notificationButton').appendChild(dot);
                        }
                    } else if (notificationDot) {
                        notificationDot.remove();
                    }
                });
        }
        
        // Clear cache function
        function clearCache() {
            if (confirm('Clear all cache? This will reset temporary data.')) {
                fetch('<?php echo ADMIN_URL; ?>/includes/ajax.php?action=clear_cache')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Cache cleared successfully.', 'success');
                        } else {
                            showToast('Failed to clear cache.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred.', 'error');
                    });
            }
        }
        
        // Show toast notification
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
            toast.className = `toast fixed bottom-4 right-4 ${typeClasses[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center transform translate-x-full opacity-0 transition-all duration-300`;
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
        
        // Time ago helper function
        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            let interval = Math.floor(seconds / 31536000);
            if (interval >= 1) return interval + " year" + (interval === 1 ? "" : "s") + " ago";
            
            interval = Math.floor(seconds / 2592000);
            if (interval >= 1) return interval + " month" + (interval === 1 ? "" : "s") + " ago";
            
            interval = Math.floor(seconds / 86400);
            if (interval >= 1) return interval + " day" + (interval === 1 ? "" : "s") + " ago";
            
            interval = Math.floor(seconds / 3600);
            if (interval >= 1) return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
            
            interval = Math.floor(seconds / 60);
            if (interval >= 1) return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
            
            return "just now";
        }
        
        // Update time ago elements
        function updateTimeAgoElements() {
            document.querySelectorAll('[data-time-ago]').forEach(element => {
                const dateString = element.getAttribute('data-time-ago');
                element.textContent = timeAgo(dateString);
            });
        }
        
        // Update time ago every minute
        setInterval(updateTimeAgoElements, 60000);
        
        // Initialize on load
        updateTimeAgoElements();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + / to focus search
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                document.querySelector('input[placeholder="Search..."]')?.focus();
            }
            
            // Esc to close modals and dropdowns
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal:not(.hidden)').forEach(modal => {
                    modal.classList.add('hidden');
                });
                document.getElementById('notificationDropdown').classList.add('hidden');
                document.getElementById('userDropdown').classList.add('hidden');
            }
            
            // Ctrl + D for dashboard
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                window.location.href = '<?php echo ADMIN_URL; ?>/dashboard.php';
            }
            
            // Ctrl + P for products
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = '<?php echo ADMIN_URL; ?>/products.php';
            }
            
            // Ctrl + O for orders
            if (e.ctrlKey && e.key === 'o') {
                e.preventDefault();
                window.location.href = '<?php echo ADMIN_URL; ?>/orders.php';
            }
        });
        
        // Auto-refresh dashboard stats every 30 seconds
        if (window.location.pathname.includes('dashboard.php')) {
            setInterval(function() {
                fetch('<?php echo ADMIN_URL; ?>/includes/ajax.php?action=get_dashboard_stats')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update stats in the quick stats bar
                            document.querySelectorAll('[data-stat="today_orders"]').forEach(el => {
                                el.textContent = data.stats.today_orders;
                            });
                            document.querySelectorAll('[data-stat="today_revenue"]').forEach(el => {
                                el.textContent = '₹' + data.stats.today_revenue;
                            });
                            document.querySelectorAll('[data-stat="new_customers"]').forEach(el => {
                                el.textContent = data.stats.new_customers;
                            });
                        }
                    });
            }, 30000);
        }
    </script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($page_js)): ?>
        <script>
            <?php echo $page_js; ?>
        </script>
    <?php endif; ?>
</body>
</html>