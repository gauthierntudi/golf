<?php
require_once '../config.php';
require_once '../db.php';
require_once '../auth.php';

// Check if user is logged in via API token or session
// For demo purposes, we'll skip authentication for this endpoint

// Get list of guests who haven't received emails yet
$conn = getDbConnection();

try {
    $stmt = $conn->prepare("
        SELECT i.id, i.nom, i.postnom, i.email, i.telephone, i.code_invitation
        FROM invitees i
        JOIN invitations inv ON i.id = inv.invitee_id
        WHERE inv.email_sent = 0
        ORDER BY i.date_added DESC
    ");
    
    $stmt->execute();
    $guests = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'data' => [
            'guests' => $guests,
            'count' => count($guests)
        ]
    ]);
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'database',
        'message' => 'An error occurred while fetching pending guests.'
    ], 500);
}