<?php
// Database Configuration
define('DB_HOST', 'localhost:8889');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'invitation_system');

// Application Configuration
define('SITE_URL', 'https://siteweb.com'); // Replace with your actual site URL
define('EMAIL_FROM', 'invitations@yourdomain.com');
define('EMAIL_FROM_NAME', 'Event Invitation System');

// Invitation Configuration
define('INVITATION_TEMPLATE', 'assets/img/invitation_template.jpg');
define('QR_CODE_SIZE', 250); // Size of QR code in pixels
define('QR_CODE_POS_X', 650); // X position of QR code on invitation template
define('QR_CODE_POS_Y', 420); // Y position of QR code on invitation template
define('NAME_POS_X', 400); // X position of name on invitation template
define('NAME_POS_Y', 300); // Y position of name on invitation template
define('NAME_FONT_SIZE', 24); // Font size for name on invitation
define('NAME_FONT', 'assets/fonts/Montserrat-Bold.ttf'); // Font file for name

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// TimeZone
date_default_timezone_set('UTC');

// Database Connection
function getDbConnection() {
    static $conn;
    
    if ($conn === null) {
        try {
            $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
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