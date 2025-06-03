// Dashboard specific JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard statistics
    loadDashboardStats();
    
    // Load guest list with pagination
    loadGuestList(1);
    
    // Set up pagination controls
    setupPagination();
    
    // Initialize animations
    initDashboardAnimations();
});

// Load Dashboard Statistics
function loadDashboardStats() {
    fetch('php/api/stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateStatsDisplay(data.stats);
            } else {
                console.error('Error loading stats:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
        });
}

// Update Stats Display
function updateStatsDisplay(stats) {
    const totalInvitees = document.getElementById('total-invitees');
    const confirmedCount = document.getElementById('confirmed-count');
    const emailsSentCount = document.getElementById('emails-sent-count');
    const pendingCount = document.getElementById('pending-count');
    
    if (totalInvitees) totalInvitees.textContent = stats.total_invitees;
    if (confirmedCount) confirmedCount.textContent = stats.confirmed_count;
    if (emailsSentCount) emailsSentCount.textContent = stats.emails_sent_count;
    if (pendingCount) pendingCount.textContent = stats.pending_count;
    
    // Animate numbers with GSAP
    gsap.from([totalInvitees, confirmedCount, emailsSentCount, pendingCount], {
        textContent: 0,
        duration: 1.5,
        ease: 'power1.out',
        snap: { textContent: 1 },
        stagger: 0.1
    });
}

// Load Guest List with Pagination
function loadGuestList(page, search = '', statusFilter = 'all') {
    const tableBody = document.getElementById('guest-table-body');
    
    if (!tableBody) return;
    
    // Show loading state
    tableBody.innerHTML = '<tr class="table-loading"><td colspan="6">Chargement de la liste des invités...</td></tr>';
    
    fetch(`php/api/guests.php?page=${page}&search=${search}&status=${statusFilter}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderGuestList(data.data.guests);
                updatePagination(data.data.pagination);
            } else {
                tableBody.innerHTML = `<tr class="table-loading"><td colspan="6">Erreur: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            tableBody.innerHTML = '<tr class="table-loading"><td colspan="6">Erreur lors du chargement de la liste. Veuillez réessayer.</td></tr>';
            console.error('Error fetching guest list:', error);
        });
}

// Render Guest List
function renderGuestList(guests) {
    const tableBody = document.getElementById('guest-table-body');
    
    if (!tableBody) return;
    
    if (guests.length === 0) {
        tableBody.innerHTML = '<tr class="table-loading"><td colspan="6">Aucun invité trouvé.</td></tr>';
        return;
    }
    
    let html = '';
    
    guests.forEach(guest => {
        // Déterminer si le bouton d'email doit être désactivé
        const emailDisabled = !guest.email;
        const emailButtonTitle = emailDisabled ? 'Pas d\'adresse email disponible' : 'Envoyer un email';
        
        html += `
            <tr>
                <td>${guest.nom || ''}</td>
                <td>${guest.email || 'Non renseigné'}</td>
                <td>${guest.telephone || 'Non renseigné'}</td>
                <td>
                    <span class="status-badge status-${guest.confirmed ? 'confirmed' : 'pending'}">
                        ${guest.confirmed ? 'Confirmé' : 'En attente'}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${guest.email_sent ? 'sent' : 'pending'}">
                        ${guest.email_sent ? 'Envoyé' : 'En attente'}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon view-guest" data-id="${guest.id}" title="Voir les détails">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon send-email" 
                                data-id="${guest.id}" 
                                title="${emailButtonTitle}"
                                ${emailDisabled ? 'disabled' : ''}
                                ${guest.email_sent ? 'disabled' : ''}>
                            <i class="fas fa-envelope"></i>
                        </button>
                        <button class="btn-icon delete-guest" data-id="${guest.id}" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Animate table rows with GSAP
    gsap.from(tableBody.querySelectorAll('tr'), {
        y: 20,
        opacity: 0,
        duration: 0.5,
        stagger: 0.05,
        ease: 'power2.out'
    });
    
    // Add event listeners to action buttons
    attachGuestActionListeners();
}

// Update Pagination
function updatePagination(pagination) {
    const pageInfo = document.getElementById('page-info');
    const prevPage = document.getElementById('prev-page');
    const nextPage = document.getElementById('next-page');
    
    if (pageInfo) pageInfo.textContent = `Page ${pagination.page} sur ${pagination.total_pages}`;
    
    if (prevPage) {
        prevPage.disabled = pagination.page <= 1;
        prevPage.dataset.page = pagination.page - 1;
    }
    
    if (nextPage) {
        nextPage.disabled = pagination.page >= pagination.total_pages;
        nextPage.dataset.page = pagination.page + 1;
    }
}

// Setup Pagination Controls
function setupPagination() {
    const prevPage = document.getElementById('prev-page');
    const nextPage = document.getElementById('next-page');
    const searchInput = document.getElementById('guest-search');
    const statusFilter = document.getElementById('status-filter');
    
    if (prevPage) {
        prevPage.addEventListener('click', function() {
            if (!this.disabled) {
                const page = parseInt(this.dataset.page);
                const search = searchInput ? searchInput.value : '';
                const status = statusFilter ? statusFilter.value : 'all';
                loadGuestList(page, search, status);
            }
        });
    }
    
    if (nextPage) {
        nextPage.addEventListener('click', function() {
            if (!this.disabled) {
                const page = parseInt(this.dataset.page);
                const search = searchInput ? searchInput.value : '';
                const status = statusFilter ? statusFilter.value : 'all';
                loadGuestList(page, search, status);
            }
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('keyup', debounce(function() {
            const search = this.value;
            const status = statusFilter ? statusFilter.value : 'all';
            loadGuestList(1, search, status);
        }, 500));
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const status = this.value;
            const search = searchInput ? searchInput.value : '';
            loadGuestList(1, search, status);
        });
    }
}

// Attach Event Listeners to Guest Action Buttons
function attachGuestActionListeners() {
    const viewButtons = document.querySelectorAll('.view-guest');
    const sendEmailButtons = document.querySelectorAll('.send-email');
    const deleteButtons = document.querySelectorAll('.delete-guest');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const guestId = this.dataset.id;
            showGuestDetails(guestId);
        });
    });
    
    sendEmailButtons.forEach(button => {
        button.addEventListener('click', function() {
            const guestId = this.dataset.id;
            showEmailForm(guestId);
        });
    });
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const guestId = this.dataset.id;
            deleteGuest(guestId);
        });
    });
}

// Show Guest Details Modal
function showGuestDetails(guestId) {
    fetch(`php/api/guest-details.php?id=${guestId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const guest = data.guest;
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Détails de l'invité</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="guest-details">
                                <p><strong>Nom:</strong> ${guest.nom || '-'}</p>
                                <p><strong>Fonction:</strong> ${guest.fonction || '-'}</p>
                                <p><strong>Entreprise:</strong> ${guest.entreprise || '-'}</p>
                                <p><strong>Email:</strong> ${guest.email || '-'}</p>
                                <p><strong>Téléphone:</strong> ${guest.telephone || '-'}</p>
                                <p><strong>Statut:</strong> ${guest.confirmed ? 'Confirmé' : 'En attente'}</p>
                                <p><strong>Email envoyé:</strong> ${guest.email_sent ? 'Oui' : 'Non'}</p>
                                ${guest.email_sent ? `<p><strong>Date d'envoi:</strong> ${new Date(guest.email_sent_date).toLocaleString()}</p>` : ''}
                                ${guest.confirmed ? `<p><strong>Date de confirmation:</strong> ${new Date(guest.confirmation_date).toLocaleString()}</p>` : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Animation d'entrée
                gsap.from(modal.querySelector('.modal-content'), {
                    y: -50,
                    opacity: 0,
                    duration: 0.3,
                    ease: 'power2.out'
                });
                
                // Gestionnaire de fermeture
                const closeModal = () => {
                    gsap.to(modal, {
                        opacity: 0,
                        duration: 0.3,
                        ease: 'power2.in',
                        onComplete: () => modal.remove()
                    });
                };
                
                modal.querySelector('.modal-close').addEventListener('click', closeModal);
                modal.addEventListener('click', e => {
                    if (e.target === modal) closeModal();
                });
            } else {
                window.appUtils.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching guest details:', error);
            window.appUtils.showNotification('Erreur lors de la récupération des détails de l\'invité', 'error');
        });
}

// Show Email Form Modal
// Show Email Form Modal
function showEmailForm(guestId) {
    fetch(`php/api/guest-details.php?id=${guestId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const guest = data.guest;
                
                // Vérifier si l'invité a une adresse email
                if (!guest.email) {
                    iziToast.error({
                        title: 'Erreur',
                        message: 'Cet invité n\'a pas d\'adresse email',
                        position: 'topRight',
                        timeout: 5000
                    });
                    return;
                }
                
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Envoyer l'invitation par email</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="single-email-form">
                                <input type="hidden" name="guest_id" value="${guest.id}">
                                <div class="form-group">
                                    <label>Destinataire</label>
                                    <input type="text" value="${guest.nom} (${guest.email})" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="email-subject">Objet</label>
                                    <input type="text" id="email-subject" name="subject" required
                                           value="Invitation à notre événement" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="email-message">Message</label>
                                    <textarea id="email-message" name="message" required rows="6" class="form-control">
Cher/Chère ${guest.nom},

Nous avons le plaisir de vous inviter à notre événement.

Merci de confirmer votre présence en cliquant sur le lien qui sera fourni dans l'email.

Cordialement,
L'équipe organisatrice</textarea>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="button-text">Envoyer</span>
                                        <span class="button-loader hidden">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </span>
                                    </button>
                                    <button type="button" class="btn btn-secondary modal-close">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Animation d'entrée
                gsap.from(modal.querySelector('.modal-content'), {
                    y: -50,
                    opacity: 0,
                    duration: 0.3,
                    ease: 'power2.out'
                });
                
                // Gestionnaire de fermeture
                const closeModal = () => {
                    gsap.to(modal, {
                        opacity: 0,
                        duration: 0.3,
                        ease: 'power2.in',
                        onComplete: () => modal.remove()
                    });
                };
                
                modal.querySelector('.modal-close').addEventListener('click', closeModal);
                modal.addEventListener('click', e => {
                    if (e.target === modal) closeModal();
                });
                
                // Gestionnaire d'envoi d'email
                const emailForm = modal.querySelector('#single-email-form');
                const submitButton = emailForm.querySelector('button[type="submit"]');
                const buttonText = submitButton.querySelector('.button-text');
                const buttonLoader = submitButton.querySelector('.button-loader');
                
                emailForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Désactiver le bouton et afficher le loader
                    submitButton.disabled = true;
                    buttonText.classList.add('hidden');
                    buttonLoader.classList.remove('hidden');
                    
                    const formData = new FormData(this);
                    
                    fetch('php/api/send-single-email.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            iziToast.success({
                                title: 'Succès',
                                message: 'Email envoyé avec succès',
                                position: 'topRight',
                                timeout: 5000
                            });
                            closeModal();
                            loadGuestList(1); // Rafraîchir la liste
                        } else {
                            iziToast.error({
                                title: 'Erreur',
                                message: data.message,
                                position: 'topRight',
                                timeout: 5000
                            });
                            // Réactiver le bouton
                            submitButton.disabled = false;
                            buttonText.classList.remove('hidden');
                            buttonLoader.classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error sending email:', error);
                        iziToast.error({
                            title: 'Erreur',
                            message: 'Une erreur est survenue lors de l\'envoi de l\'email',
                            position: 'topRight',
                            timeout: 5000
                        });
                        // Réactiver le bouton
                        submitButton.disabled = false;
                        buttonText.classList.remove('hidden');
                        buttonLoader.classList.add('hidden');
                    });
                });
            } else {
                iziToast.error({
                    title: 'Erreur',
                    message: data.message,
                    position: 'topRight',
                    timeout: 5000
                });
            }
        })
        .catch(error => {
            console.error('Error fetching guest details:', error);
            iziToast.error({
                title: 'Erreur',
                message: 'Erreur lors de la récupération des détails de l\'invité',
                position: 'topRight',
                timeout: 5000
            });
        });
}

// Delete Guest
function deleteGuest(guestId) {
    showConfirmDialog({
        title: 'Confirmation de suppression',
        message: 'Êtes-vous sûr de vouloir supprimer cet invité ?',
        icon: 'fa-trash',
        confirmText: 'Supprimer',
        cancelText: 'Annuler',
        onConfirm: () => {
            fetch(`php/api/delete-guest.php?id=${guestId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.appUtils.showNotification('Invité supprimé avec succès', 'success');
                    loadGuestList(1); // Rafraîchir la liste
                    loadDashboardStats(); // Mettre à jour les statistiques
                } else {
                    window.appUtils.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting guest:', error);
                window.appUtils.showNotification('Erreur lors de la suppression de l\'invité', 'error');
            });
        }
    });
}

// Custom Confirm Dialog
function showConfirmDialog({ title, message, icon, confirmText, cancelText, onConfirm }) {
    const dialog = document.createElement('div');
    dialog.className = 'confirm-dialog';
    dialog.innerHTML = `
        <div class="confirm-dialog-content">
            <div class="confirm-dialog-header">
                <h3><i class="fas ${icon}"></i> ${title}</h3>
            </div>
            <div class="confirm-dialog-body">
                <p>${message}</p>
            </div>
            <div class="confirm-dialog-footer">
                <button class="btn btn-secondary cancel-btn">${cancelText}</button>
                <button class="btn btn-danger confirm-btn">${confirmText}</button>
            </div>
        </div>
    `;

    document.body.appendChild(dialog);

    // Animation d'entrée
    requestAnimationFrame(() => {
        dialog.classList.add('active');
    });

    // Gestionnaires d'événements
    const closeDialog = () => {
        dialog.classList.remove('active');
        setTimeout(() => dialog.remove(), 300);
    };

    dialog.querySelector('.cancel-btn').addEventListener('click', closeDialog);
    dialog.querySelector('.confirm-btn').addEventListener('click', () => {
        onConfirm();
        closeDialog();
    });
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) closeDialog();
    });
}

// Initialize Dashboard Animations
function initDashboardAnimations() {
    // Animate the dashboard header
    gsap.from('.dashboard-header', {
        y: -20,
        opacity: 0,
        duration: 0.8,
        ease: 'power2.out'
    });
    
    // Animate the action cards
    gsap.from('.action-card', {
        y: 30,
        opacity: 0,
        duration: 0.8,
        stagger: 0.2,
        ease: 'power2.out',
        delay: 0.4
    });
    
    // Animate the guest list section
    gsap.from('.guest-list-section', {
        y: 30,
        opacity: 0,
        duration: 0.8,
        ease: 'power2.out',
        delay: 0.8
    });
}

// Debounce Function
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}