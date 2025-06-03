<?php
require_once 'php/config.php';
require_once 'php/db.php';

// Get invitation code from URL
$invitationCode = isset($_GET['codeInvitation']) ? sanitizeInput($_GET['codeInvitation']) : null;

// Get invitee data if code is provided
$invitee = null;
$error = null;
$success = false;

if ($invitationCode) {
    $invitee = getInviteeByCode($invitationCode);
    
    if (!$invitee) {
        $error = 'Invalid invitation code. Please check your invitation.';
    } elseif ($invitee['confirmed']) {
        $success = true;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $invitee && !$invitee['confirmed']) {
    $result = confirmInvitation($invitationCode);
    
    if ($result['success']) {
        $success = true;
        $invitee = $result['invitee'];
    } else {
        $error = $result['message'];
    }
}

$pageTitle = "Confirm Attendance";
include 'includes/header.php';
?>

<main class="confirmation-container">
    <?php if (!$invitationCode): ?>
        <div class="error-card">
            <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
            <h1>Missing Invitation Code</h1>
            <p>No invitation code was provided. Please check your invitation link.</p>
        </div>
    <?php elseif ($error): ?>
        <div class="error-card">
            <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
            <h1>Error</h1>
            <p><?php echo $error; ?></p>
        </div>
    <?php elseif ($success): ?>
        <div class="success-card">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h1>Thank You!</h1>
            <p>Your attendance has been confirmed, <?php echo htmlspecialchars($invitee['nom'] . ' ' . $invitee['postnom']); ?>!</p>
            
            <div class="next-steps">
                <h2>Next Steps</h2>
                <p>Generate your personalized invitation to access the event:</p>
                <a href="generate-invitation.php?code=<?php echo $invitationCode; ?>" class="btn btn-primary">
                    Generate My Invitation
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="confirmation-card">
            <h1>Confirm Your Attendance</h1>
            <p>Hello, <?php echo htmlspecialchars($invitee['nom'] . ' ' . $invitee['postnom']); ?>!</p>
            <p>Please confirm your attendance by completing this form:</p>
            
            <form id="confirmation-form" method="post">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($invitee['nom'] . ' ' . $invitee['postnom']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($invitee['email']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Are you attending?</label>
                    <div class="radio-group">
                        <input type="radio" id="attending-yes" name="attending" value="yes" checked>
                        <label for="attending-yes">Yes, I will attend</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Additional Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Confirm Attendance</button>
            </form>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>