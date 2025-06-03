// Confirmation Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form submission
    const confirmationForm = document.getElementById('confirmation-form');
    
    if (confirmationForm) {
        confirmationForm.addEventListener('submit', handleConfirmation);
    }
    
    // Initialize animations
    initConfirmationAnimations();
});

// Handle Confirmation Form Submission
function handleConfirmation(event) {
    event.preventDefault();
    
    // Get form elements
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Disable submit button and show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Get form data
    const formData = new FormData(form);
    
    // Submit form
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        // Replace the current page content with the new HTML
        document.documentElement.innerHTML = html;
        
        // Re-initialize scripts after page content is replaced
        const scripts = document.querySelectorAll('script');
        scripts.forEach(script => {
            if (script.src) {
                const newScript = document.createElement('script');
                newScript.src = script.src;
                document.body.appendChild(newScript);
            }
        });
        
        // Reinitialize animations
        initConfirmationAnimations();
    })
    .catch(error => {
        console.error('Error during confirmation:', error);
        
        // Enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = 'Confirm Attendance';
        
        // Show error message
        alert('An error occurred while processing your confirmation. Please try again.');
    });
}

// Initialize Confirmation Page Animations
function initConfirmationAnimations() {
    // Animate confirmation card
    const confirmationCard = document.querySelector('.confirmation-card');
    if (confirmationCard) {
        gsap.from(confirmationCard, {
            y: 30,
            opacity: 0,
            duration: 0.8,
            ease: 'power2.out'
        });
    }
    
    // Animate success card
    const successCard = document.querySelector('.success-card');
    if (successCard) {
        gsap.from(successCard, {
            scale: 0.9,
            opacity: 0,
            duration: 0.8,
            ease: 'back.out(1.7)'
        });
        
        // Animate success icon
        const successIcon = successCard.querySelector('.success-icon');
        if (successIcon) {
            gsap.from(successIcon, {
                scale: 0,
                rotation: -180,
                opacity: 0,
                duration: 1,
                ease: 'elastic.out(1, 0.3)',
                delay: 0.2
            });
        }
        
        // Animate next steps section
        const nextSteps = successCard.querySelector('.next-steps');
        if (nextSteps) {
            gsap.from(nextSteps, {
                y: 20,
                opacity: 0,
                duration: 0.8,
                ease: 'power2.out',
                delay: 0.5
            });
        }
    }
    
    // Animate error card
    const errorCard = document.querySelector('.error-card');
    if (errorCard) {
        gsap.from(errorCard, {
            scale: 0.9,
            opacity: 0,
            duration: 0.8,
            ease: 'power2.out'
        });
        
        // Animate error icon
        const errorIcon = errorCard.querySelector('.error-icon');
        if (errorIcon) {
            gsap.from(errorIcon, {
                scale: 0,
                opacity: 0,
                duration: 0.8,
                ease: 'back.out(1.7)',
                delay: 0.2
            });
        }
    }
    
    // Add interactive animations to form elements
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach((group, index) => {
        gsap.from(group, {
            y: 20,
            opacity: 0,
            duration: 0.5,
            ease: 'power2.out',
            delay: 0.1 * index
        });
    });
}

// Add form validation
function validateConfirmationForm() {
    const form = document.getElementById('confirmation-form');
    
    if (!form) return true;
    
    // Since most fields are readonly and the attendance is a radio button
    // that's already set, there's not much to validate in this form
    
    // This is a placeholder for future enhancements
    const notes = form.querySelector('#notes').value.trim();
    
    // Check for any problematic content in notes
    if (notes.length > 500) {
        alert('Notes are too long. Please limit to 500 characters.');
        return false;
    }
    
    return true;
}