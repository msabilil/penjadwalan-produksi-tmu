/**
 * Detail Pesanan JavaScript
 * Handles Purchase Order functionality
 */

// Print functionality
function printPO(pesananId) {
    // Open print version in new window
    const printUrl = `generate_po_pdf.php?id=${pesananId}&print=1`;
    window.open(printUrl, '_blank', 'width=800,height=600');
}

// Enhanced print functionality without confirmation
function confirmPrint() {
    // Get pesanan ID from URL or page context
    const urlParams = new URLSearchParams(window.location.search);
    const pesananId = urlParams.get('id');
    if (pesananId) {
        printPO(pesananId);
    } else {
        console.error('Pesanan ID not found');
    }
}

// Download functionality with loading state
function downloadPOWithLoading(pesananId) {
    const downloadBtn = document.querySelector('.action-button.download');
    const originalText = downloadBtn.innerHTML;
    
    // Show loading state
    downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Generating PDF...</span>';
    downloadBtn.style.pointerEvents = 'none';
    
    // Create download link
    const downloadUrl = `?id=${pesananId}&download=po`;
    
    // Trigger download
    window.location.href = downloadUrl;
    
    // Restore button after delay
    setTimeout(() => {
        downloadBtn.innerHTML = originalText;
        downloadBtn.style.pointerEvents = '';
    }, 3000);
}

// Initialize page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add print shortcut (Ctrl+P)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            confirmPrint();
        }
    });
    
    // Add ESC key to go back
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const backButton = document.querySelector('.back-button');
            if (backButton) {
                window.location.href = backButton.href;
            }
        }
    });
    
    // Enhance print button
    const printButton = document.querySelector('.action-button.print');
    if (printButton) {
        printButton.addEventListener('click', function(e) {
            e.preventDefault();
            // Extract pesanan ID from onclick attribute or data attribute
            const pesananId = this.onclick ? this.onclick.toString().match(/\d+/)[0] : null;
            if (pesananId) {
                printPO(pesananId);
            } else {
                confirmPrint();
            }
        });
    }
    
    // Add loading animation to images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        img.addEventListener('error', function() {
            // Handle image load errors
            console.log('Image failed to load:', this.src);
        });
        
        // Set initial opacity for smooth loading
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s';
    });
    
    // Auto-focus on load for accessibility
    document.body.focus();
    
    // Add smooth scrolling for any anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Handle responsive table scrolling
    const tables = document.querySelectorAll('.po-table');
    tables.forEach(table => {
        if (table.scrollWidth > table.clientWidth) {
            table.style.overflowX = 'auto';
            table.style.display = 'block';
            table.style.whiteSpace = 'nowrap';
        }
    });
    
    // Add visual feedback for interactive elements
    const buttons = document.querySelectorAll('button, .action-button');
    buttons.forEach(button => {
        button.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        button.addEventListener('mouseup', function() {
            this.style.transform = 'scale(1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// Window resize handler for responsive adjustments
window.addEventListener('resize', function() {
    // Re-check table responsiveness
    const tables = document.querySelectorAll('.po-table');
    tables.forEach(table => {
        if (table.scrollWidth > table.clientWidth) {
            table.style.overflowX = 'auto';
        } else {
            table.style.overflowX = 'visible';
        }
    });
});

// Before print event handler
window.addEventListener('beforeprint', function() {
    // Add any pre-print adjustments here
    document.body.classList.add('printing');
});

// After print event handler
window.addEventListener('afterprint', function() {
    // Clean up after printing
    document.body.classList.remove('printing');
});

// Export functions for external use
window.DetailPesanan = {
    print: printPO,
    confirmPrint: confirmPrint,
    downloadWithLoading: downloadPOWithLoading
};
