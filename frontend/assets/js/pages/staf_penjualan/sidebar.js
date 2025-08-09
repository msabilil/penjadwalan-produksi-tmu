/**
 * Staf Penjualan - Sidebar JavaScript
 * Handle sidebar interactions and navigation
 */

// Initialize sidebar functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
});

/**
 * Initialize sidebar functionality
 */
function initializeSidebar() {
    // Highlight current page
    highlightCurrentPage();
    
    // Initialize tooltip functionality
    initializeTooltips();
    
    // Initialize responsive sidebar
    initializeResponsiveSidebar();
}

/**
 * Highlight current page in navigation
 */
function highlightCurrentPage() {
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop().replace('.php', '');
    
    // Remove existing active states
    const navLinks = document.querySelectorAll('aside nav a');
    navLinks.forEach(link => {
        link.classList.remove('bg-green-50', 'text-green-600', 'border-r-4', 'border-green-600');
        
        // Check if this link matches current page
        const linkHref = link.getAttribute('href');
        if (linkHref && linkHref.includes(currentPage + '.php')) {
            link.classList.add('bg-green-50', 'text-green-600', 'border-r-4', 'border-green-600');
            
            // Update icon color
            const icon = link.querySelector('svg');
            if (icon) {
                icon.classList.remove('text-gray-400', 'group-hover:text-green-500');
                icon.classList.add('text-green-600');
            }
        }
    });
}

/**
 * Initialize tooltips for navigation items
 */
function initializeTooltips() {
    const navItems = document.querySelectorAll('aside nav a');
    
    navItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const text = this.textContent.trim();
            
            // Create tooltip if it doesn't exist
            if (!this.querySelector('.tooltip')) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 pointer-events-none transition-opacity duration-200 z-50';
                tooltip.textContent = text;
                this.appendChild(tooltip);
                
                // Show tooltip
                setTimeout(() => {
                    tooltip.classList.remove('opacity-0');
                }, 100);
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.tooltip');
            if (tooltip) {
                tooltip.classList.add('opacity-0');
                setTimeout(() => {
                    tooltip.remove();
                }, 200);
            }
        });
    });
}

/**
 * Initialize responsive sidebar for mobile
 */
function initializeResponsiveSidebar() {
    // Create mobile menu toggle if not exists
    if (window.innerWidth <= 768) {
        createMobileMenuToggle();
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            createMobileMenuToggle();
        } else {
            removeMobileMenuToggle();
        }
    });
}

/**
 * Create mobile menu toggle button
 */
function createMobileMenuToggle() {
    const sidebar = document.querySelector('aside');
    const main = document.querySelector('main');
    
    if (!sidebar || !main) return;
    
    // Check if toggle already exists
    if (document.getElementById('mobileMenuToggle')) return;
    
    // Create toggle button
    const toggleButton = document.createElement('button');
    toggleButton.id = 'mobileMenuToggle';
    toggleButton.className = 'fixed top-4 left-4 z-50 bg-green-600 text-white p-2 rounded-md shadow-lg md:hidden';
    toggleButton.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    `;
    
    // Add to body
    document.body.appendChild(toggleButton);
    
    // Initially hide sidebar on mobile
    sidebar.classList.add('-translate-x-full', 'fixed', 'z-40', 'transition-transform', 'duration-300');
    main.classList.add('ml-0');
    
    // Toggle functionality
    toggleButton.addEventListener('click', function() {
        const isHidden = sidebar.classList.contains('-translate-x-full');
        
        if (isHidden) {
            sidebar.classList.remove('-translate-x-full');
            this.innerHTML = `
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            `;
        } else {
            sidebar.classList.add('-translate-x-full');
            this.innerHTML = `
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            `;
        }
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (!sidebar.contains(e.target) && !toggleButton.contains(e.target)) {
            sidebar.classList.add('-translate-x-full');
            toggleButton.innerHTML = `
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            `;
        }
    });
}

/**
 * Remove mobile menu toggle
 */
function removeMobileMenuToggle() {
    const toggleButton = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('aside');
    const main = document.querySelector('main');
    
    if (toggleButton) {
        toggleButton.remove();
    }
    
    if (sidebar) {
        sidebar.classList.remove('-translate-x-full', 'fixed', 'z-40', 'transition-transform', 'duration-300');
    }
    
    if (main) {
        main.classList.remove('ml-0');
    }
}

/**
 * Logout confirmation function
 */
function confirmLogout() {
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sistem?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Logging out...',
                text: 'Sedang keluar dari sistem',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirect to logout
            setTimeout(() => {
                window.location.href = '../../../backend/utils/logout.php';
            }, 1000);
        }
    });
}

/**
 * Show notification badge (for future use)
 */
function showNotificationBadge(menuItem, count) {
    const link = document.querySelector(`a[href="${menuItem}.php"]`);
    if (!link) return;
    
    // Remove existing badge
    const existingBadge = link.querySelector('.notification-badge');
    if (existingBadge) {
        existingBadge.remove();
    }
    
    if (count > 0) {
        const badge = document.createElement('span');
        badge.className = 'notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center';
        badge.textContent = count > 99 ? '99+' : count;
        
        link.style.position = 'relative';
        link.appendChild(badge);
    }
}

/**
 * Add smooth scroll behavior to sidebar links
 */
function addSmoothScrolling() {
    const navLinks = document.querySelectorAll('aside nav a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add loading state
            const text = this.textContent.trim();
            const loadingText = 'Memuat...';
            
            if (text !== loadingText) {
                this.style.opacity = '0.7';
                this.style.pointerEvents = 'none';
                
                // Restore after a short delay
                setTimeout(() => {
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                }, 500);
            }
        });
    });
}

// Initialize smooth scrolling
document.addEventListener('DOMContentLoaded', addSmoothScrolling);

// Make confirmLogout available globally
window.confirmLogout = confirmLogout;
