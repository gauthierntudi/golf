// Email Sender JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Get the email settings form
    const emailSettingsForm = document.getElementById('email-settings-form');
    
    if (emailSettingsForm) {
        emailSettingsForm.addEventListener('submit', handleEmailSend);
    }
    
    // Set up select all checkbox
    const selectAllCheckbox = document.getElementById('select-all');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            // In a real implementation, this would toggle all individual guest checkboxes
            console.log('Select all changed:', this.checked);
        });
    }
});

// Handle Email Send
function handleEmailSend(event) {
    event.preventDefault();
    
    // Get form elements
    const form = event.target;
    const emailSubject = form.querySelector('#email-subject').value.trim();
    const emailTemplate = form.querySelector('#email-template').value.trim();
    const submitButton = form.querySelector('button[type="submit"]');
    const emailProgress = document.querySelector('.email-progress');
    const progressBar = emailProgress.querySelector('.progress-bar');
    const emailsSent = document.getElementById('emails-sent');
    const totalEmails = document.getElementById('total-emails');
    
    // Validate inputs
    if (!emailSubject) {
        window.appUtils.showNotification('Please enter an email subject.', 'error');
        return;
    }
    
    if (!emailTemplate) {
        window.appUtils.showNotification('Please enter an email message.', 'error');
        return;
    }
    
    // In a real implementation, this would get the selected recipients
    // For this demo, we'll just assume we're sending to all pending guests
    
    // Show a loading notification
    window.appUtils.showNotification('Preparing to send invitations...', 'info');
    
    // Fetch the list of recipients who haven't received emails yet
    fetch('php/api/pending-guests.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch recipients');
            }
            
            const recipients = data.data.guests.map(guest => guest.id);
            
            if (recipients.length === 0) {
                window.appUtils.showNotification('No pending invitations to send.', 'warning');
                return;
            }
            
            // Update the total emails count
            if (totalEmails) totalEmails.textContent = recipients.length;
            if (emailsSent) emailsSent.textContent = '0';
            
            // Show progress container and disable submit button
            emailProgress.classList.remove('hidden');
            submitButton.disabled = true;
            
            // Prepare the request data
            const requestData = {
                subject: emailSubject,
                template: emailTemplate,
                recipients: recipients
            };
            
            // Send the email request
            return sendEmails(requestData, progressBar, emailsSent, recipients.length);
        })
        .then(result => {
            if (result && result.success) {
                // Email sending completed
                submitButton.disabled = false;
                
                // Show success notification
                window.appUtils.showNotification(
                    `Successfully sent ${result.data.sent} invitations.` + 
                    (result.data.failed > 0 ? ` Failed to send ${result.data.failed} invitations.` : ''),
                    result.data.failed > 0 ? 'warning' : 'success'
                );
                
                // Refresh dashboard stats
                if (typeof loadDashboardStats === 'function') {
                    loadDashboardStats();
                }
                
                // Refresh guest list
                if (typeof loadGuestList === 'function') {
                    loadGuestList(1);
                }
            }
        })
        .catch(error => {
            // Handle errors
            console.error('Error sending emails:', error);
            
            // Enable submit button
            submitButton.disabled = false;
            
            // Show error notification
            window.appUtils.showNotification('Error sending invitations: ' + error.message, 'error');
        });
}

// Send Emails and Update Progress
function sendEmails(requestData, progressBar, emailsSentElement, totalEmails) {
    return new Promise((resolve, reject) => {
        // Reset progress bar
        gsap.set(progressBar, { width: '0%' });
        
        // Make the API request
        fetch('php/email-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Simulate progressive updates for better UX
            simulateProgressUpdates(progressBar, emailsSentElement, totalEmails, () => {
                resolve(data);
            });
        })
        .catch(error => {
            reject(error);
        });
    });
}

// Simulate Progressive Updates (for better UX)
function simulateProgressUpdates(progressBar, emailsSentElement, totalEmails, callback) {
    let currentProgress = 0;
    let currentEmailsSent = 0;
    const interval = 100; // Update every 100ms
    const increment = 1; // Increment by 1% each time
    
    // Create an array of "milestones" where we'll update the emails sent count
    const milestones = [];
    const emailIncrement = Math.ceil(totalEmails / 20); // Update about 20 times
    
    for (let i = emailIncrement; i <= totalEmails; i += emailIncrement) {
        milestones.push(Math.min(i, totalEmails));
    }
    
    const updateProgress = () => {
        currentProgress += increment;
        
        // Update progress bar width
        gsap.to(progressBar, {
            width: `${currentProgress}%`,
            duration: 0.1,
            ease: 'power1.out'
        });
        
        // Check if we've reached a milestone to update emails sent count
        if (milestones.length > 0 && (currentProgress / 100) * totalEmails >= milestones[0]) {
            currentEmailsSent = milestones.shift();
            
            if (emailsSentElement) {
                // Update with a nice counter animation
                gsap.to(emailsSentElement, {
                    innerText: currentEmailsSent,
                    duration: 0.3,
                    snap: { innerText: 1 }
                });
            }
        }
        
        if (currentProgress < 100) {
            setTimeout(updateProgress, interval);
        } else {
            // Ensure final count is updated
            if (emailsSentElement) {
                emailsSentElement.textContent = totalEmails;
            }
            
            // Wait a moment before calling the callback
            setTimeout(callback, 500);
        }
    };
    
    // Start the updates
    updateProgress();
}

// Format Email Template with Guest Data
function formatEmailTemplate(template, guestData) {
    // Replace placeholders with actual data
    let formattedTemplate = template;
    
    for (const [key, value] of Object.entries(guestData)) {
        formattedTemplate = formattedTemplate.replace(new RegExp(`{${key}}`, 'g'), value);
    }
    
    return formattedTemplate;
}

// Preview Formatted Email
function previewFormattedEmail(template) {
    // Get a sample guest for preview
    const sampleGuest = {
        Nom: 'John',
        Postnom: 'Doe',
        Email: 'john.doe@example.com',
        Téléphone: '+1234567890'
    };
    
    // Format the template with sample data
    const formattedEmail = formatEmailTemplate(template, sampleGuest);
    
    // Create a modal for preview
    const previewModal = document.createElement('div');
    previewModal.className = 'modal email-preview-modal';
    previewModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Email Preview</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="email-preview">
                    ${formattedEmail}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
            </div>
        </div>
    `;
    
    // Add the modal to the DOM
    document.body.appendChild(previewModal);
    
    // Show the modal with animation
    gsap.fromTo(
        previewModal,
        { opacity: 0 },
        { opacity: 1, duration: 0.3, ease: 'power2.out' }
    );
    
    gsap.fromTo(
        previewModal.querySelector('.modal-content'),
        { y: -50, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.5, ease: 'back.out(1.7)' }
    );
    
    // Set up close button
    const closeButtons = previewModal.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Hide with animation
            gsap.to(previewModal, {
                opacity: 0,
                duration: 0.3,
                ease: 'power2.in',
                onComplete: () => {
                    previewModal.remove();
                }
            });
        });
    });
    
    // Close when clicking outside the modal content
    previewModal.addEventListener('click', (e) => {
        if (e.target === previewModal) {
            closeButtons[0].click();
        }
    });
}