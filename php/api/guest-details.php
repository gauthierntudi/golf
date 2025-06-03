<?php
require_once '../config.php';
require_once '../db.php';
require_once '../auth.php';

try {
    // Vérifier si l'ID est fourni
    if (!isset($_GET['id'])) {
        throw new Exception('ID de l\'invité manquant');
    }

    $guestId = (int)$_GET['id'];

    // Récupérer les détails de l'invité
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT i.*, inv.confirmed, inv.confirmation_date, inv.email_sent, inv.email_sent_date
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

    // Retourner les détails
    echo json_encode([
        'success' => true,
        'guest' => $guest
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}