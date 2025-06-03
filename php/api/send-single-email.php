<?php
require_once '../config.php';
require_once '../db.php';
require_once '../auth.php';
require_once '../utils/qr_code.php';
require_once '../services/EmailService.php';
require_once '../../vendor/autoload.php';

use Services\EmailService;

try {
    // Vérifier si les données requises sont présentes
    if (!isset($_POST['guest_id']) || !isset($_POST['subject']) || !isset($_POST['message'])) {
        throw new Exception('Données manquantes');
    }

    $guestId = (int)$_POST['guest_id'];
    $subject = sanitizeInput($_POST['subject']);
    $message = $_POST['message'];

    // Récupérer les informations de l'invité
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT i.*, inv.email_sent
        FROM invitees i
        JOIN invitations inv ON i.id = inv.invitee_id
        WHERE i.id = :id
    ");

    $stmt->bindParam(':id', $guestId);
    $stmt->execute();
    $guest = $stmt->fetch();

    if (!$guest) {
        throw new Exception('Invité non trouvé');
    }

    if ($guest['email_sent']) {
        throw new Exception('L\'email a déjà été envoyé à cet invité');
    }

    if (empty($guest['email'])) {
        throw new Exception('Cet invité n\'a pas d\'adresse email');
    }

    // Générer le QR code
    $qrCodeUrl = SITE_URL . '/confirm.php?codeInvitation=' . $guest['code_invitation'];
    $qrCodeImage = generateQrCode($qrCodeUrl);

    // Charger le template d'email
    $template = file_get_contents(EMAIL_TEMPLATE_HTML);
    
    // Remplacer les placeholders dans le template
    $replacements = [
        '{{subject}}' => $subject,
        '{{event_name}}' => 'Open Golf 2025',
        '{{message}}' => nl2br($message),
        '{{confirmation_url}}' => $qrCodeUrl,
        '{{current_year}}' => date('Y'),
        '{{company_name}}' => 'Vodacom RDC'
    ];
    
    $emailBody = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $template
    );

    // Créer le service d'email avec le provider par défaut
    $emailService = new EmailService(DEFAULT_EMAIL_PROVIDER);

    // Préparer le texte alternatif
    $textBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $message)) . 
                "\n\nPour confirmer votre présence, visitez : " . $qrCodeUrl;

    // Préparer les pièces jointes
    $attachments = [
        [
            'Name' => 'qrcode.png',
            'Path' => $qrCodeImage,
            'ContentType' => 'image/png',
            'ContentID' => 'qrcode'
        ]
    ];

    // Envoyer l'email
    $result = $emailService->send(
        $guest['email'],
        $guest['nom'],
        $subject,
        $emailBody,
        $textBody,
        $attachments
    );

    if ($result['success']) {
        // Mettre à jour le statut d'envoi
        $stmt = $conn->prepare("
            UPDATE invitations
            SET email_sent = 1, email_sent_date = NOW()
            WHERE invitee_id = :id
        ");
        $stmt->bindParam(':id', $guestId);
        $stmt->execute();

        // Enregistrer l'envoi dans les logs
        logEmailSent($guestId, $subject, $message, 'success');

        echo json_encode([
            'success' => true,
            'message' => 'Email envoyé avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de l\'envoi de l\'email : ' . $result['message']);
    }

    // Nettoyer le fichier QR code
    if (file_exists($qrCodeImage)) {
        unlink($qrCodeImage);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
