<?php
require_once '../config.php';
require_once '../db.php';
require_once '../auth.php';

// Check if user is logged in via API token or session
// For demo purposes, we'll skip authentication for this endpoint

try {
    // Get statistics
    $stats = getInvitationStats();
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($stats);
} catch (Exception $e) {
    // Log the error
    error_log('Stats API Error: ' . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la récupération des statistiques.'
    ]);
}