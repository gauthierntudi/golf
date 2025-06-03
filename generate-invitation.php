<?php
require_once 'php/config.php';
require_once 'php/db.php';

// Get invitation code from URL
$invitationCode = isset($_GET['code']) ? sanitizeInput($_GET['code']) : null;

// Get invitee data if code is provided
$invitee = null;
$error = null;

if ($invitationCode) {
    $invitee = getInviteeByCode($invitationCode);
    
    if (!$invitee) {
        $error = 'Code d\'invitation invalide. Veuillez vérifier votre invitation.';
    } elseif (!$invitee['confirmed']) {
        $error = 'Veuillez confirmer votre présence avant de générer votre invitation.';
    }
}

$pageTitle = "Générer l'invitation";
include 'includes/header.php';
?>

<main class="generate-invitation-container">
    <?php if (!$invitationCode || $error): ?>
        <div class="error-card">
            <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
            <h1>Erreur</h1>
            <p><?php echo $error ?? 'Aucun code d\'invitation n\'a été fourni. Veuillez vérifier votre lien.'; ?></p>
            <?php if (!$invitee['confirmed'] && $invitee): ?>
                <a href="confirm.php?codeInvitation=<?php echo $invitationCode; ?>" class="btn btn-primary">
                    Confirmer ma présence
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="invitation-card">
            <h1>Votre Invitation</h1>
            <p>Bonjour, <?php echo htmlspecialchars($invitee['nom']); ?> !</p>
            
            <?php if ($invitee['invitation_generated'] && $invitee['invitation_path']): ?>
                <div class="invitation-preview">
                    <img src="<?php echo htmlspecialchars('/' . $invitee['invitation_path']); ?>" alt="Votre Invitation" class="invitation-image">
                </div>
                
                <div class="invitation-actions">
                    <a href="<?php echo htmlspecialchars('/' . $invitee['invitation_path']); ?>" download class="btn btn-primary">
                        <i class="fas fa-download"></i> Télécharger l'invitation
                    </a>
                    <button id="share-invitation" class="btn btn-secondary">
                        <i class="fas fa-share-alt"></i> Partager l'invitation
                    </button>
                </div>
            <?php else: ?>
                <p>Nous générons votre invitation personnalisée...</p>
                
                <div class="generating-animation">
                    <div class="spinner"></div>
                </div>
                
                <form id="generate-invitation-form">
                    <input type="hidden" name="code" value="<?php echo htmlspecialchars($invitationCode); ?>">
                    <button type="submit" class="btn btn-primary">Générer mon invitation</button>
                </form>
                
                <div class="invitation-preview hidden">
                    <img src="" alt="Votre Invitation" id="invitation-image" class="invitation-image">
                </div>
                
                <div class="invitation-actions hidden">
                    <a href="#" id="download-invitation" class="btn btn-primary">
                        <i class="fas fa-download"></i> Télécharger l'invitation
                    </a>
                    <button id="share-invitation" class="btn btn-secondary">
                        <i class="fas fa-share-alt"></i> Partager l'invitation
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>