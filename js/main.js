// Main JavaScript file

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize GSAP animations
    initAnimations();
    
    // Initialize UI components
    initUI();
    
    // Initialize event listeners
    initEventListeners();
});

// Initialize GSAP Animations
function initAnimations() {
    // Fade in elements with the fade-in class
    gsap.to('.fade-in', {
        opacity: 1,
        duration: 0.8,
        stagger: 0.1,
        ease: 'power2.out'
    });
    
    // Slide up elements with the slide-up class
    gsap.to('.slide-up', {
        opacity: 1,
        y: 0,
        duration: 0.8,
        stagger: 0.1,
        ease: 'power2.out'
    });
    
    // Slide right elements with the slide-right class
    gsap.to('.slide-right', {
        opacity: 1,
        x: 0,
        duration: 0.8,
        stagger: 0.1,
        ease: 'power2.out'
    });
    
    // Scale in elements with the scale-in class
    gsap.to('.scale-in', {
        opacity: 1,
        scale: 1,
        duration: 0.8,
        stagger: 0.1,
        ease: 'back.out(1.7)'
    });
    
    // Animate stat cards on the dashboard
    gsap.from('.stat-card', {
        y: 20,
        opacity: 0,
        duration: 0.6,
        stagger: 0.1,
        ease: 'power2.out',
        delay: 0.2
    });
}

// Initialize UI Components
function initUI() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileMenuToggle && mainNav) {
        mobileMenuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            this.classList.toggle('active');
            
            if (mainNav.classList.contains('active')) {
                gsap.fromTo(mainNav, 
                    { opacity: 0, y: -20 }, 
                    { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' }
                );
            }
        });
    }
    
    // File input display
    const fileInput = document.getElementById('excel-file');
    const fileNameDisplay = document.getElementById('file-name');
    
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = 'No file chosen';
            }
        });
    }
}

// Initialize Event Listeners
function initEventListeners() {
    // User menu dropdown
    const userInfo = document.querySelector('.user-info');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (userInfo && dropdownMenu) {
        userInfo.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('active');
            
            if (dropdownMenu.classList.contains('active')) {
                gsap.fromTo(dropdownMenu, 
                    { opacity: 0, y: 10 }, 
                    { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' }
                );
            }
        });
        
        document.addEventListener('click', function() {
            if (dropdownMenu.classList.contains('active')) {
                dropdownMenu.classList.remove('active');
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('guest-search');
    const searchBtn = document.getElementById('search-btn');
    
    if (searchInput && searchBtn) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                // Implement search functionality
                console.log('Searching for:', searchTerm);
                // In a real app, this would trigger the search function
            }
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });
    }
    
    // Status filter
    const statusFilter = document.getElementById('status-filter');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            console.log('Filtering by status:', selectedStatus);
            // In a real app, this would trigger the filter function
        });
    }
}

// Helper Functions
function showLoading(element) {
    if (element) {
        element.classList.add('loading');
    }
}

function hideLoading(element) {
    if (element) {
        element.classList.remove('loading');
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        </div>
        <div class="notification-content">
            <p>${message}</p>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to the DOM
    document.body.appendChild(notification);
    
    // Animate in
    gsap.fromTo(notification, 
        { opacity: 0, x: 50 }, 
        { opacity: 1, x: 0, duration: 0.3, ease: 'power2.out' }
    );
    
    // Set up auto-remove
    setTimeout(() => {
        gsap.to(notification, {
            opacity: 0,
            x: 50,
            duration: 0.3,
            ease: 'power2.in',
            onComplete: () => {
                notification.remove();
            }
        });
    }, 5000);
    
    // Set up close button
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        gsap.to(notification, {
            opacity: 0,
            x: 50,
            duration: 0.3,
            ease: 'power2.in',
            onComplete: () => {
                notification.remove();
            }
        });
    });
}

// Export functions for use in other modules
window.appUtils = {
    showLoading,
    hideLoading,
    showNotification
};