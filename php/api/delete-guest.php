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

    // Supprimer l'invité
    $conn = getDbConnection();
    
    // Commencer une transaction
    $conn->beginTransaction();

    try {
        // Supprimer d'abord les enregistrements liés dans la table invitations
        $stmt = $conn->prepare("DELETE FROM invitations WHERE invitee_id = :id");
        $stmt->bindParam(':id', $guestId);
        $stmt->execute();

        // Ensuite supprimer l'invité
        $stmt = $conn->prepare("DELETE FROM invitees WHERE id = :id");
        $stmt->bindParam(':id', $guestId);
        $stmt->execute();

        // Valider la transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Invité supprimé avec succès'
        ]);

    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}