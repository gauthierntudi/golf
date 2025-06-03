<?php
require_once 'config.php';
require_once 'db.php';
require_once 'services/EmailService.php';
require_once 'vendor/autoload.php';

use Services\EmailService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode de requête invalide'], 405);
}

// Vérification du token CSRF (à implémenter selon votre système d'auth)
// if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
//     jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
// }

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Format JSON invalide');
    }

    if (!isset($input['subject']) || !isset($input['template']) || !isset($input['recipients'])) {
        throw new Exception('Champs requis manquants');
    }

    $subject = sanitizeInput($input['subject']);
    $template = $input['template'];
    $recipients = $input['recipients'];

    // Validation des données
    if (empty($subject) || strlen($subject) > 255) {
        throw new Exception('Sujet invalide');
    }

    if (empty($template)) {
        throw new Exception('Template vide');
    }

    if (empty($recipients) || !is_array($recipients)) {
        throw new Exception('Liste de destinataires invalide');
    }

    if (count($recipients) > 1000) { // Limite de sécurité
        throw new Exception('Trop de destinataires (max 1000)');
    }

    // Créer le service d'email
    $emailService = new EmailService(DEFAULT_EMAIL_PROVIDER);

    $sentCount = 0;
    $failedCount = 0;
    $skippedCount = 0;
    $results = [];
    $conn = getDbConnection();

    // Préparer la requête de mise à jour
    $updateStmt = $conn->prepare("
        UPDATE invitations
        SET email_sent = 1, email_sent_date = NOW()
        WHERE invitee_id = :id
    ");

    foreach ($recipients as $recipientId) {
        try {
            // Validation de l'ID
            if (!is_numeric($recipientId) || $recipientId <= 0) {
                $results[] = [
                    'id' => $recipientId,
                    'status' => 'failed',
                    'message' => 'ID destinataire invalide'
                ];
                $failedCount++;
                continue;
            }

            // Récupérer les données de l'invité
            $stmt = $conn->prepare("
                SELECT i.*, inv.email_sent, inv.email_sent_date
                FROM invitees i
                JOIN invitations inv ON i.id = inv.invitee_id
                WHERE i.id = :id AND i.status = 'active'
            ");
            
            $stmt->bindParam(':id', $recipientId, PDO::PARAM_INT);
            $stmt->execute();
            $invitee = $stmt->fetch();
            
            if (!$invitee) {
                $results[] = [
                    'id' => $recipientId,
                    'status' => 'failed',
                    'message' => 'Invité non trouvé ou inactif'
                ];
                $failedCount++;
                continue;
            }

            // Vérifier si l'email a déjà été envoyé
            if ($invitee['email_sent']) {
                $results[] = [
                    'id' => $recipientId,
                    'status' => 'skipped',
                    'message' => 'Email déjà envoyé le ' . $invitee['email_sent_date']
                ];
                $skippedCount++;
                continue;
            }

            // Vérifier l'adresse email
            if (empty($invitee['email']) || !isValidEmail($invitee['email'])) {
                $results[] = [
                    'id' => $recipientId,
                    'status' => 'failed',
                    'message' => 'Adresse email invalide'
                ];
                $failedCount++;
                continue;
            }

            // Générer le QR code
            $qrCodeUrl = SITE_URL . '/confirm.php?codeInvitation=' . urlencode($invitee['code_invitation']);
            $qrCodeImage = generateQrCodeSafe($qrCodeUrl);
            
            if (!$qrCodeImage) {
                $results[] = [
                    'id' => $recipientId,
                    'status' => 'failed',
                    'message' => 'Erreur génération QR code'
                ];
                $failedCount++;
                continue;
            }

            // Personnaliser le message
            $personalizedMessage = personalizeMessage($template, $invitee);

            // Charger et préparer le template email
            $emailBody = prepareEmailTemplate($subject, $personalizedMessage, $qrCodeUrl);
            
            if (!$emailBody) {
                throw new Exception('Erreur chargement template email');
            }

            // Préparer le texte alternatif
            $textBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $personalizedMessage)) . 
                        "\n\nPour confirmer votre présence, visitez : " . $qrCodeUrl;

            // Préparer les pièces jointes
            $attachments = [
                [
                    'Name' => 'invitation-qrcode.png',
                    'Path' => $qrCodeImage,
                    'ContentType' => 'image/png',
                    'ContentID' => 'qrcode'
                ]
            ];

            // Envoyer l'email
            $result = $emailService->send(
                $invitee['email'],
                trim($invitee['nom'] . ' ' . $invitee['postnom']),
                $subject,
                $emailBody,
                $textBody,
                $attachments
            );

            if ($result['success']) {
                // Mettre à jour le statut
                $updateStmt->bindParam(':id', $recipientId, PDO::PARAM_INT);
                $updateStmt->execute();

                // Logger le succès
                logEmailSent($recipientId, $subject, $personalizedMessage, 'success');

                $results[] = [
                    'id' => $recipientId,
                    'status' => 'success',
                    'message' => 'Email envoyé avec succès',
                    'provider' => $result['provider'] ?? 'unknown'
                ];
                
                $sentCount++;
            } else {
                logEmailSent($recipientId, $subject, $personalizedMessage, 'failed', $result['message']);
                
                $results[] = [
                    'id' => $recipientId,
                    'status' => 'failed',
                    'message' => $result['message']
                ];
                
                $failedCount++;
            }

        } catch (Exception $e) {
            error_log('Email Processing Error for recipient ' . $recipientId . ': ' . $e->getMessage());
            
            $results[] = [
                'id' => $recipientId,
                'status' => 'failed',
                'message' => 'Erreur technique : ' . $e->getMessage()
            ];
            
            $failedCount++;
        } finally {
            // Nettoyer le QR code
            if (isset($qrCodeImage) && file_exists($qrCodeImage)) {
                unlink($qrCodeImage);
            }
        }
    }

    // Retourner les résultats
    jsonResponse([
        'success' => true,
        'message' => 'Traitement des emails terminé',
        'data' => [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'total' => count($recipients),
            'results' => $results
        ]
    ]);

} catch (Exception $e) {
    error_log('Email Handler Error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}

// Fonctions utilitaires

function generateQrCodeSafe($url) {
    try {
        $qrDir = __DIR__ . '/../assets/qrcodes';
        if (!file_exists($qrDir)) {
            if (!mkdir($qrDir, 0755, true)) {
                throw new Exception('Impossible de créer le dossier QR codes');
            }
        }
        
        $filename = $qrDir . '/' . uniqid('qr_', true) . '.png';
        
        $renderer = new ImageRenderer(
            new RendererStyle(400, 2, null, null, null, true),
            new ImagickImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        $writer->writeFile($url, $filename);
        
        return file_exists($filename) ? $filename : false;
        
    } catch (Exception $e) {
        error_log('QR Code Generation Error: ' . $e->getMessage());
        return false;
    }
}

function personalizeMessage($template, $invitee) {
    $replacements = [
        '{Nom}' => $invitee['nom'] ?? '',
        '{Postnom}' => $invitee['postnom'] ?? '',
        '{Email}' => $invitee['email'] ?? '',
        '{Téléphone}' => $invitee['telephone'] ?? '',
        '{Entreprise}' => $invitee['entreprise'] ?? '',
        '{Fonction}' => $invitee['fonction'] ?? ''
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

function prepareEmailTemplate($subject, $message, $confirmationUrl) {
    try {
        if (!file_exists(EMAIL_TEMPLATE_HTML)) {
            throw new Exception('Template email non trouvé');
        }
        
        $template = file_get_contents(EMAIL_TEMPLATE_HTML);
        
        if ($template === false) {
            throw new Exception('Erreur lecture template email');
        }
        
        $replacements = [
            '{{subject}}' => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
            '{{event_name}}' => 'Open Golf 2025',
            '{{message}}' => nl2br($message),
            '{{confirmation_url}}' => htmlspecialchars($confirmationUrl, ENT_QUOTES, 'UTF-8'),
            '{{current_year}}' => date('Y'),
            '{{company_name}}' => 'Vodacom RDC',
            '{{site_url}}' => SITE_URL
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
        
    } catch (Exception $e) {
        error_log('Email Template Error: ' . $e->getMessage());
        return false;
    }
}