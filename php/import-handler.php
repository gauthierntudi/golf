<?php
require_once 'config.php';
require_once 'db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Définir l'en-tête de réponse JSON
header('Content-Type: application/json');

try {
    // Activer l'affichage des erreurs pour le débogage
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode de requête invalide');
    }

    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['excel-file'])) {
        throw new Exception('Aucun fichier n\'a été envoyé');
    }

    if ($_FILES['excel-file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = array(
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par PHP',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload du fichier'
        );
        throw new Exception($uploadErrors[$_FILES['excel-file']['error']]);
    }

    // Vérifier si le fichier est bien un fichier Excel
    $mimeType = mime_content_type($_FILES['excel-file']['tmp_name']);
    $allowedTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream'
    ];

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Type de fichier invalide. Type détecté : ' . $mimeType);
    }

    // Vérifier si le fichier temporaire existe et est lisible
    if (!is_readable($_FILES['excel-file']['tmp_name'])) {
        throw new Exception('Le fichier temporaire n\'est pas accessible en lecture');
    }

    // Traiter le fichier uploadé
    $tempFilePath = $_FILES['excel-file']['tmp_name'];
    
    try {
        $spreadsheet = IOFactory::load($tempFilePath);
    } catch (Exception $e) {
        throw new Exception('Erreur lors de la lecture du fichier Excel : ' . $e->getMessage());
    }

    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestDataRow();
    $highestColumn = $worksheet->getHighestDataColumn();
    
    // Initialiser les compteurs
    $importedCount = 0;
    $duplicatesCount = 0;
    $errors = [];
    
    // Commencer à partir de la ligne 2 (en supposant que la ligne 1 est l'en-tête)
    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            // Obtenir les données de chaque ligne
            $rowData = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false)[0];
            
            // Extraire les données (en permettant les champs vides)
            $nom = trim($rowData[0] ?? '');
            $fonction = trim($rowData[1] ?? '');
            $entreprise = trim($rowData[2] ?? '');
            $telephone = trim($rowData[3] ?? '');
            $email = trim($rowData[4] ?? '');
            
            // Ignorer les lignes complètement vides
            if (empty($nom) && empty($fonction) && empty($entreprise) && empty($telephone) && empty($email)) {
                continue;
            }
            
            // Valider l'email si fourni
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ligne $row : Adresse email invalide : $email";
                continue;
            }
            
            // Ajouter l'invité à la base de données
            $result = addInvitee($nom, $fonction, $entreprise, $telephone, $email);
            
            if ($result['success']) {
                $importedCount++;
            } else if ($result['error'] === 'duplicate') {
                $duplicatesCount++;
                $errors[] = "Ligne $row : " . $result['message'];
            } else {
                $errors[] = "Ligne $row : " . $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = "Erreur à la ligne $row : " . $e->getMessage();
            continue;
        }
    }
    
    // Retourner les résultats de l'importation
    echo json_encode([
        'success' => true,
        'message' => 'Importation terminée',
        'data' => [
            'imported' => $importedCount,
            'duplicates' => $duplicatesCount,
            'errors' => $errors
        ]
    ]);

} catch (Exception $e) {
    error_log('Erreur d\'importation Excel : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors du traitement du fichier Excel : ' . $e->getMessage()
    ]);
}