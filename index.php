<?php
require_once 'php/config.php';
require_once 'php/auth.php';

// Ensure user is logged in
checkAuthentication();

$pageTitle = "Dashboard | Invitation Management System";
include 'includes/header.php';
?>

<main class="dashboard-container">
    <div class="dashboard-header">
        <h1>Invitation Management System</h1>
        <p>Import your guest list, send invitations, and track responses</p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3>Total Invitees</h3>
                <p id="total-invitees">Loading...</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <h3>Confirmed</h3>
                <p id="confirmed-count">Loading...</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            <div class="stat-content">
                <h3>Emails Sent</h3>
                <p id="emails-sent-count">Loading...</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3>Pending</h3>
                <p id="pending-count">Loading...</p>
            </div>
        </div>
    </div>

    <section class="dashboard-actions">
        <div class="action-card import-card">
            <h2>Import Guest List</h2>
            <p>Upload an Excel file with your guests' information</p>
            <form id="excel-upload-form" enctype="multipart/form-data">
                <div class="file-upload-container">
                    <input type="file" id="excel-file" name="excel-file" accept=".xlsx,.xls" required>
                    <label for="excel-file">Choose Excel File</label>
                    <span id="file-name">No file chosen</span>
                </div>
                <div class="progress-container hidden">
                    <div class="progress-bar"></div>
                </div>
                <button type="submit" class="btn btn-primary">Import Guests</button>
            </form>
            <div id="import-results" class="hidden">
                <h3>Import Results</h3>
                <p><span id="records-imported">0</span> records imported</p>
                <p><span id="duplicates-found">0</span> duplicates ignored</p>
            </div>
        </div>

        <div class="action-card email-card">
            <h2>Send Invitations</h2>
            <p>Send personalized emails with QR codes to your guests</p>
            <form id="email-settings-form">
                <div class="form-group">
                    <label for="email-subject">Email Subject</label>
                    <input type="text" id="email-subject" name="email-subject" required 
                           placeholder="e.g., You're Invited to Our Event!">
                </div>
                <div class="form-group">
                    <label for="email-template">Email Message</label>
                    <textarea id="email-template" name="email-template" required rows="5"
                              placeholder="Dear {Nom} {Postnom}, we are pleased to invite you..."></textarea>
                    <small>Use {Nom}, {Postnom}, {Email}, {Téléphone} as placeholders for guest information.</small>
                </div>
                <div class="form-group">
                    <label>Select Recipients</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="select-all" checked>
                        <label for="select-all">All Unsent Invitations</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Send Invitations</button>
            </form>
            <div class="email-progress hidden">
                <h3>Sending Progress</h3>
                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
                <p><span id="emails-sent">0</span> of <span id="total-emails">0</span> emails sent</p>
            </div>
        </div>
    </section>

    <section class="guest-list-section">
        <h2>Guest List</h2>
        <div class="table-controls">
            <div class="search-container">
                <input type="text" id="guest-search" placeholder="Search guests...">
                <button id="search-btn"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-container">
                <select id="status-filter">
                    <option value="all">All Status</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="pending">Pending</option>
                    <option value="email_sent">Email Sent</option>
                    <option value="email_pending">Email Pending</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table id="guest-table" class="guest-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Email Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="guest-table-body">
                    <!-- Guests will be loaded here via JavaScript -->
                    <tr class="table-loading">
                        <td colspan="6">Loading guest list...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination">
            <button id="prev-page" disabled><i class="fas fa-chevron-left"></i> Previous</button>
            <span id="page-info">Page 1 of 1</span>
            <button id="next-page" disabled>Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>