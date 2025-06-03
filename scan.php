<?php
require_once 'php/config.php';
require_once 'php/db.php';

$pageTitle = "Scanner d'invitations";
include 'includes/header.php';
?>
<style>
.scanner-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background-color: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

#reader {
    width: 100vw;
    height: 100vh;
    max-width: none !important;
    max-height: none !important;
}

#reader video {
    width: 100vw !important;
    height: 100vh !important;
    object-fit: cover;
}

#reader__scan_region {
    width: 100% !important;
    height: 100% !important;
    min-height: 100vh !important;
    position: relative;
}

#reader__scan_region::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 250px;
    height: 250px;
    border: 2px solid var(--primary-color);
    border-radius: 20px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    }
    50% {
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);
    }
    100% {
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    }
}

#reader__scan_region img {
    display: none;
}

#reader__dashboard {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.7) !important;
    border-radius: 12px;
    padding: 10px !important;
    backdrop-filter: blur(10px);
}

#reader__dashboard_section_swaplink {
    color: white !important;
}

#reader__dashboard_section_csr span {
    color: white !important;
}

.result-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: ;
    justify-content: ;
    padding: var(--spacing-lg);
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-normal);
    z-index: 1000;
    backdrop-filter: blur(10px);
}

.result-container.visible {
    opacity: 1;
    visibility: visible;
}

/*.result-content {
    background-color: var(--bg-white);
    padding: var(--spacing-xl);
    border-radius: var(--border-radius-lg);
    text-align: center;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    transform: translateY(0);
    transition: transform var(--transition-normal);
}*/

.result-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: ;
    justify-content: ;
    z-index: 1000;
    backdrop-filter: blur(15px);
    padding: 0px;
}

.result-content {
    background-color: #fff;
    border-top-left-radius: 32px;
    border-top-right-radius: 32px;
    width: 100%;
    overflow: hidden;
    height: 60vh;
    position: fixed;
    text-align: center;
    padding-bottom: 40px;
    padding-top: 40px;
    bottom: 0;
    left: 0;
    right: 0;
    box-shadow: 0 4px 20px rgba(255, 255, 255, 0.2);
}

/* En-tête */
.guest-header {
    padding: 20px;
    text-align: center;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 24px;
}

.guest-title {
    margin: 0;
    color: #212529;
    font-size: 1.5rem;
}

.guest-subtitle {
    margin: 5px 0 0;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Section statut */
.scan-status {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.success-icon {
    color: #28a745;
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.result-title {
    margin: 0;
    color: #212529;
}

.scan-count {
    margin: 5px 0 0;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Détails */
.guest-details {
    padding: 15px 20px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
}

.detail-label {
    color: #6c757d;
    font-weight: 500;
}

.detail-value {
    color: #212529;
    text-align: right;
}

.verified {
    color: #28a745;
    font-weight: 500;
}

/* Bouton fermer */
.btn-close {
    display: block;
    width: 100%;
    padding: 15px;
    background-color: #f8f9fa;
    border: none;
    border-top: 1px solid #eee;
    color: #dc3545;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-close:hover {
    background-color: #e9ecef;
}

.btn-close i {
    margin-right: 8px;
}

/* Amélioration de l'animation de pulse */
@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
        border-color: var(--primary-color);
    }
    50% {
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);
        border-color: var(--primary-light);
    }
}

.btn{
   margin-top: 10px; 
}
</style>

<main class="scanner-container">
    <div id="reader"></div>
    
    <div id="result" class="result-container hidden">
        <div class="result-content">
            <div class="scan-status">
                <div class="result-icon">
                    <i class="fas fa-spinner fa-spin scanning-icon"></i>
                    <i class="fas fa-check-circle success-icon hidden"></i>
                    <i class="fas fa-times-circle error-icon hidden"></i>
                </div>
                <h2 class="result-title">Vérification en cours...</h2>
                <p class="result-message"></p>
            </div>
            
            <div class="guest-details hidden" style="margin-bottom: 20px;">
                <div class="detail-item">
                    <span class="detail-label">Nom :</span>
                    <span id="guest-name" class="detail-value">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fonction :</span>
                    <span id="guest-function" class="detail-value">-</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Entreprise :</span>
                    <span id="guest-company" class="detail-value">-</span>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://unpkg.com/html5-qrcode"></script>
<script src="js/scanner.js"></script>

<?php include 'includes/footer.php'; ?>