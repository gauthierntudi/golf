<?php
require_once 'config.php';

/**
 * Simple authentication system
 */

// Function to check if user is authenticated
function checkAuthentication() {
    // For demonstration purposes, this is a simple check
    // In a production environment, implement proper authentication
    if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
        // Not authenticated, redirect to login page
        header('Location: login.php');
        exit;
    }
}

// Function to authenticate user
function authenticateUser($username, $password) {
    // For demonstration purposes, hardcoded credentials
    // In a production environment, use proper password hashing and database storage
    $validUsername = 'admin';
    $validPassword = 'password123';
    
    if ($username === $validUsername && $password === $validPassword) {
        $_SESSION['user_authenticated'] = true;
        $_SESSION['username'] = $username;
        return true;
    }
    
    return false;
}

// Function to log out user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Destroy the session
    session_destroy();
}