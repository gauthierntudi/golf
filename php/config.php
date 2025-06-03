<?php
// Database Configuration
define('DB_HOST', 'localhost:8889');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'invitation_system');

// Application Configuration
define('SITE_URL', 'https://vodacom-experience.space');
define('EMAIL_FROM', 'no-reply@vodacom-experience.space');
define('EMAIL_FROM_NAME', 'Invitation Golf 2025');

// Email Provider Configuration
define('DEFAULT_EMAIL_PROVIDER', 'default'); // 'default', 'brevo', ou 'postmark'

// Default SMTP Configuration
define('SMTP_HOST', 'mail.vodacom-experience.space');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contact@vodacom-experience.space');
define('SMTP_PASS', 'w.mAuA*NNHSs');

// Utilisez :
define('DEFAULT_SMTP_HOST', 'mail.vodacom-experience.space');
define('DEFAULT_SMTP_PORT', 587);
define('DEFAULT_SMTP_USER', 'contact@vodacom-experience.space');
define('DEFAULT_SMTP_PASS', 'w.mAuA*NNHSs');

// Brevo SMTP Configuration
define('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
define('BREVO_SMTP_PORT', 587);
define('BREVO_SMTP_USER', 'contact@vodacom-experience.space');
define('BREVO_SMTP_KEY', 'xsmtpsib-7eb3f0bbbcfe42b6448d5160869781dbf692e686dea721cea370fb1e0ad65335-7WMYgGARj5OdXCN9');

// Postmark Configuration
define('POSTMARK_SERVER_TOKEN', '6237289f-711d-4e55-ad3f-5f74c7818d0a');
define('POSTMARK_SENDER_SIGNATURE', EMAIL_FROM);

// Invitation Configuration
define('INVITATION_TEMPLATE', '../assets/img/invitation_template.jpg');
define('QR_CODE_SIZE', 250);
define('QR_CODE_POS_X', 75);
define('QR_CODE_POS_Y', 1220);
define('NAME_POS_X', 220);
define('NAME_POS_Y', 400);
define('NAME_FONT_SIZE', 35);
define('NAME_FONT', '../assets/fonts/VodafoneExB.ttf');

// Upload Configuration
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// TimeZone
date_default_timezone_set('UTC');

// Email Templates
define('EMAIL_TEMPLATE_HTML', __DIR__ . '/templates/email_template.html');

// Database Connection
function getDbConnection() {
    static $conn;
    
    if ($conn === null) {
        try {
            $conn = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('La connexion à la base de données a échoué. Veuillez réessayer plus tard.');
        }
    }
    
    return $conn;
}

// Utility Functions
function generateUniqueCode($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $code;
}

function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}