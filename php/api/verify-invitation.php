<?php
require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');

function isValidCode($code) {
    return preg_match('/^[A-Z0-9]{10}$/', $code);
}

try {
    if (!isset($_GET['code'])) {
        throw new Exception('Paramètre "code" manquant', 400);
    }

    $code = sanitizeInput($_GET['code']);

    if (!isValidCode($code)) {
        throw new Exception('Le code doit contenir exactement 10 caractères alphanumériques majuscules', 400);
    }

    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT i.*, inv.confirmed, inv.scan_count
        FROM invitees i
        JOIN invitations inv ON i.id = inv.invitee_id
        WHERE i.code_invitation = :code
        LIMIT 1
    ");
    $stmt->bindParam(':code', $code);
    $stmt->execute();
    $guest = $stmt->fetch();

    if (!$guest) {
        throw new Exception('Aucune invitation trouvée avec ce code', 404);
    }

    if (!$guest['confirmed']) {
        throw new Exception('Cette invitation n\'a pas été confirmée', 403);
    }

    $updateStmt = $conn->prepare("
        UPDATE invitations 
        SET scan_count = scan_count + 1, last_scan_at = NOW() 
        WHERE invitee_id = :invitee_id
    ");
    $updateStmt->bindParam(':invitee_id', $guest['id']);
    $updateStmt->execute();

    echo json_encode([
        'success' => true,
        'guest' => [
            'nom' => $guest['nom'],
            'email' => $guest['email'] ?? '',
            'fonction' => $guest['fonction'],
            'entreprise' => $guest['entreprise'],
            'scan_count' => $guest['scan_count'] + 1
        ],
        'scan_info' => [
            'count' => $guest['scan_count'] + 1,
            'is_first_scan' => ($guest['scan_count'] + 1) === 1
        ]
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
