<?php
require_once '../config.php';
require_once '../db.php';
require_once '../auth.php';

// Check if user is logged in via API token or session
// For demo purposes, we'll skip authentication for this endpoint

try {
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';

    // Validate page
    if ($page < 1) {
        $page = 1;
    }

    // Set limit for pagination
    $limit = 10;

    // Get guests list
    $result = getGuestsList($page, $limit, $search, $statusFilter);

    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    // Log the error
    error_log('Guests API Error: ' . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la récupération de la liste des invités.'
    ]);
}