<?php
namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Postmark\PostmarkClient;

class EmailService {
    private $provider;
    private $mailer;
    private $postmarkClient;
    private $retryAttempts;

    public function __construct($provider = null, $retryAttempts = 3) {
        $this->provider = $provider ?? \DEFAULT_EMAIL_PROVIDER;
        $this->retryAttempts = $retryAttempts;
        
        $this->initializeProvider();
    }

    private function initializeProvider() {
        if ($this->provider === 'postmark') {
            $this->postmarkClient = new PostmarkClient(\POSTMARK_SERVER_TOKEN);
        } else {
            $this->mailer = new PHPMailer(true);
            $this->configureSMTP();
        }
    }

    private function configureSMTP() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->SMTPAuth = true;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Timeout = 30;
            $this->mailer->SMTPKeepAlive = true; // Maintenir la connexion
            
            // Configuration anti-spam
            $this->mailer->XMailer = ' '; // Supprimer le header X-Mailer
            $this->mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            switch ($this->provider) {
                case 'brevo':
                    $this->mailer->Host = \BREVO_SMTP_HOST;
                    $this->mailer->Port = \BREVO_SMTP_PORT;
                    $this->mailer->Username = \BREVO_SMTP_USER;
                    $this->mailer->Password = \BREVO_SMTP_KEY;
                    break;

                default:
                    $this->mailer->Host = \DEFAULT_SMTP_HOST;
                    $this->mailer->Port = \DEFAULT_SMTP_PORT;
                    $this->mailer->Username = \DEFAULT_SMTP_USER;
                    $this->mailer->Password = \DEFAULT_SMTP_PASS;
                    break;
            }
        } catch (PHPMailerException $e) {
            throw new \Exception('Erreur de configuration SMTP : ' . $e->getMessage());
        }
    }

    public function send($to, $toName, $subject, $htmlBody, $textBody, $attachments = []) {
        // Validation des paramètres
        if (!$this->validateEmailParams($to, $subject, $htmlBody)) {
            return [
                'success' => false,
                'message' => 'Paramètres d\'email invalides'
            ];
        }

        // Vérifier le rate limiting
        if (!checkRateLimit($this->provider)) {
            return [
                'success' => false,
                'message' => 'Limite de débit atteinte. Veuillez patienter.'
            ];
        }

        // Tentatives avec retry
        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            $result = $this->attemptSend($to, $toName, $subject, $htmlBody, $textBody, $attachments);
            
            if ($result['success']) {
                return $result;
            }
            
            // Si c'est la dernière tentative, retourner l'erreur
            if ($attempt === $this->retryAttempts) {
                return $result;
            }
            
            // Attendre avant la prochaine tentative (backoff exponentiel)
            sleep(pow(2, $attempt));
        }

        return [
            'success' => false,
            'message' => 'Échec après ' . $this->retryAttempts . ' tentatives'
        ];
    }

    private function attemptSend($to, $toName, $subject, $htmlBody, $textBody, $attachments) {
        if ($this->provider === 'postmark') {
            return $this->sendWithPostmark($to, $toName, $subject, $htmlBody, $textBody, $attachments);
        } else {
            return $this->sendWithSMTP($to, $toName, $subject, $htmlBody, $textBody, $attachments);
        }
    }

    private function validateEmailParams($to, $subject, $htmlBody) {
        if (empty($to) || !isValidEmail($to)) {
            return false;
        }
        
        if (empty($subject) || strlen($subject) > 255) {
            return false;
        }
        
        if (empty($htmlBody)) {
            return false;
        }
        
        return true;
    }

    private function sendWithPostmark($to, $toName, $subject, $htmlBody, $textBody, $attachments) {
        try {
            $postmarkAttachments = [];
            foreach ($attachments as $attachment) {
                if (!file_exists($attachment['Path'])) {
                    continue;
                }
                
                $postmarkAttachments[] = [
                    'Name' => $attachment['Name'],
                    'Content' => base64_encode(file_get_contents($attachment['Path'])),
                    'ContentType' => $attachment['ContentType'],
                    'ContentID' => $attachment['ContentID'] ?? null
                ];
            }

            $this->postmarkClient->sendEmail(
                \POSTMARK_SENDER_SIGNATURE,
                $to,
                $subject,
                $htmlBody,
                $textBody,
                true, // Track opens
                null, // Reply to
                null, // CC
                null, // BCC
                null, // Headers
                $postmarkAttachments
            );

            return [
                'success' => true,
                'message' => 'Email envoyé avec succès via Postmark',
                'provider' => 'postmark'
            ];
        } catch (\Exception $e) {
            error_log('Postmark Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur Postmark : ' . $e->getMessage(),
                'provider' => 'postmark'
            ];
        }
    }

    private function sendWithSMTP($to, $toName, $subject, $htmlBody, $textBody, $attachments) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            $this->mailer->setFrom(\EMAIL_FROM, \EMAIL_FROM_NAME);
            $this->mailer->addAddress($to, $toName);
            
            // Headers anti-spam
            $this->mailer->addCustomHeader('List-Unsubscribe', '<mailto:unsubscribe@vodacom-experience.space>');
            $this->mailer->addCustomHeader('X-Priority', '3');
            $this->mailer->addCustomHeader('X-MSMail-Priority', 'Normal');

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody;

            // Gestion des pièces jointes
            foreach ($attachments as $attachment) {
                if (!file_exists($attachment['Path'])) {
                    continue;
                }
                
                if (isset($attachment['ContentID'])) {
                    $this->mailer->addEmbeddedImage(
                        $attachment['Path'],
                        $attachment['ContentID'],
                        $attachment['Name'],
                        'base64',
                        $attachment['ContentType']
                    );
                } else {
                    $this->mailer->addAttachment(
                        $attachment['Path'],
                        $attachment['Name'],
                        'base64',
                        $attachment['ContentType']
                    );
                }
            }

            $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Email envoyé avec succès via SMTP',
                'provider' => $this->provider
            ];
        } catch (PHPMailerException $e) {
            error_log('SMTP Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur SMTP : ' . $e->getMessage(),
                'provider' => $this->provider
            ];
        }
    }

    public function sendBatch($recipients, $subject, $htmlTemplate, $textTemplate, $attachments = []) {
        $results = [];
        $batchSize = \EMAIL_BATCH_SIZE;
        $batches = array_chunk($recipients, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $recipient) {
                // Personnaliser le contenu pour chaque destinataire
                $personalizedHtml = $this->personalizeContent($htmlTemplate, $recipient);
                $personalizedText = $this->personalizeContent($textTemplate, $recipient);
                
                $result = $this->send(
                    $recipient['email'],
                    $recipient['name'],
                    $subject,
                    $personalizedHtml,
                    $personalizedText,
                    $attachments
                );
                
                $results[] = array_merge($result, ['recipient_id' => $recipient['id']]);
                
                // Pause entre les emails pour éviter le spam
                usleep(100000); // 0.1 seconde
            }
            
            // Pause plus longue entre les lots
            if ($batchIndex < count($batches) - 1) {
                sleep(2);
            }
        }
        
        return $results;
    }

    private function personalizeContent($template, $recipient) {
        $placeholders = [
            '{Nom}' => $recipient['nom'] ?? '',
            '{Postnom}' => $recipient['postnom'] ?? '',
            '{Email}' => $recipient['email'] ?? '',
            '{Téléphone}' => $recipient['telephone'] ?? '',
            '{CodeInvitation}' => $recipient['code_invitation'] ?? ''
        ];
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    public function __destruct() {
        if ($this->mailer && $this->mailer->getSMTPInstance()) {
            $this->mailer->smtpClose();
        }
    }
}