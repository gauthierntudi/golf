// Invitation Generator JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form submission
    const generateForm = document.getElementById('generate-invitation-form');
    
    if (generateForm) {
        generateForm.addEventListener('submit', handleGenerateInvitation);
    }
    
    // Initialize share button
    const shareButton = document.getElementById('share-invitation');
    
    if (shareButton) {
        shareButton.addEventListener('click', handleShareInvitation);
    }
    
    // Initialize download button
    const downloadButton = document.getElementById('download-invitation');
    
    if (downloadButton) {
        downloadButton.addEventListener('click', function(e) {
            // The href attribute should already be set to the invitation image URL
            // This is just to track the download event
            console.log('Invitation downloaded');
        });
    }
    
    // Initialize animations
    initInvitationAnimations();
    
    // Auto-generate invitation if confirmed but not yet generated
    const generatingAnimation = document.querySelector('.generating-animation');
    const generateButton = generateForm?.querySelector('button[type="submit"]');
    
    if (generatingAnimation && generateButton) {
        // Auto-submit the form
        setTimeout(() => {
            generateButton.click();
        }, 1500);
    }
});

// Handle Generate Invitation Form Submission
function handleGenerateInvitation(event) {
    event.preventDefault();
    
    // Get form elements
    const form = event.target;
    const invitationCode = form.querySelector('input[name="code"]').value;
    const submitButton = form.querySelector('button[type="submit"]');
    const generatingAnimation = document.querySelector('.generating-animation');
    const invitationPreview = document.querySelector('.invitation-preview');
    const invitationActions = document.querySelector('.invitation-actions');
    const invitationImage = document.getElementById('invitation-image');
    const downloadButton = document.getElementById('download-invitation');
    
    // Disable submit button and show loading
    submitButton.disabled = true;
    submitButton.style.display = 'none';
    generatingAnimation.style.display = 'flex';
    
    // Create form data
    const formData = new FormData();
    formData.append('code', invitationCode);
    
    // Submit form
    fetch('php/invitation-generator.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Hide generating animation
            generatingAnimation.style.display = 'none';
            
            // Show invitation preview
            invitationPreview.classList.remove('hidden');
            invitationActions.classList.remove('hidden');
            
            // Set invitation image source
            invitationImage.src = data.data.invitation_url;
            
            // Set download button href
            if (downloadButton) {
                downloadButton.href = data.data.invitation_url;
                downloadButton.setAttribute('download', 'invitation.jpg');
            }
            
            // Animate invitation reveal
            gsap.from(invitationPreview, {
                scale: 0.9,
                opacity: 0,
                duration: 0.8,
                ease: 'back.out(1.7)'
            });
            
            gsap.from(invitationActions, {
                y: 20,
                opacity: 0,
                duration: 0.5,
                delay: 0.5,
                ease: 'power2.out'
            });
        } else {
            // Show error message
            alert('Error: ' + data.message);
            
            // Enable submit button and hide loading
            submitButton.disabled = false;
            submitButton.style.display = 'block';
            generatingAnimation.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error generating invitation:', error);
        
        // Show error message
        alert('An error occurred while generating your invitation. Please try again.');
        
        // Enable submit button and hide loading
        submitButton.disabled = false;
        submitButton.style.display = 'block';
        generatingAnimation.style.display = 'none';
    });
}

// Handle Share Invitation
function handleShareInvitation() {
    const invitationImage = document.getElementById('invitation-image');
    
    if (!invitationImage || !invitationImage.src) {
        alert('No invitation available to share.');
        return;
    }
    
    // Check if Web Share API is supported
    if (navigator.share) {
        navigator.share({
            title: 'My Event Invitation',
            text: 'Here is my invitation to the event!',
            url: window.location.href
        })
        .then(() => console.log('Invitation shared successfully'))
        .catch((error) => console.log('Error sharing invitation:', error));
    } else {
        // Fallback for browsers that don't support Web Share API
        prompt('Copy this link to share your invitation:', window.location.href);
    }
}

// Initialize Invitation Generator Animations
function initInvitationAnimations() {
    // Animate invitation card
    const invitationCard = document.querySelector('.invitation-card');
    if (invitationCard) {
        gsap.from(invitationCard, {
            y: 30,
            opacity: 0,
            duration: 0.8,
            ease: 'power2.out'
        });
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
    }
    
    // Animate spinner
    const spinner = document.querySelector('.spinner');
    if (spinner) {
        gsap.to(spinner, {
            rotation: 360,
            duration: 1,
            ease: 'none',
            repeat: -1
        });
    }
    
    // If invitation is already generated, animate it
    const existingInvitationPreview = document.querySelector('.invitation-preview:not(.hidden)');
    if (existingInvitationPreview) {
        gsap.from(existingInvitationPreview, {
            scale: 0.95,
            opacity: 0,
            duration: 0.8,
            ease: 'power2.out'
        });
    }
    
    // Animate invitation actions
    const existingInvitationActions = document.querySelector('.invitation-actions:not(.hidden)');
    if (existingInvitationActions) {
        gsap.from(existingInvitationActions, {
            y: 20,
            opacity: 0,
            duration: 0.5,
            delay: 0.3,
            ease: 'power2.out'
        });
    }
}