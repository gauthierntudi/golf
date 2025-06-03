<?php
require_once 'config.php';

/**
 * Create database tables if they don't exist
 */
function createTables() {
    $conn = getDbConnection();
    
    // Create invitees table with updated fields
    $conn->exec("CREATE TABLE IF NOT EXISTS invitees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100),
        fonction VARCHAR(100),
        entreprise VARCHAR(100),
        telephone VARCHAR(50),
        email VARCHAR(255),
        code_invitation VARCHAR(20) UNIQUE NOT NULL,
        date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_contact (telephone, email)
    )");
    
    // Create invitations table
    $conn->exec("CREATE TABLE IF NOT EXISTS invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invitee_id INT NOT NULL,
        email_sent TINYINT(1) DEFAULT 0,
        email_sent_date DATETIME NULL,
        confirmed TINYINT(1) DEFAULT 0,
        confirmation_date DATETIME NULL,
        invitation_generated TINYINT(1) DEFAULT 0,
        invitation_path VARCHAR(255) NULL,
        FOREIGN KEY (invitee_id) REFERENCES invitees(id) ON DELETE CASCADE
    )");
    
    // Create email_logs table
    $conn->exec("CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invitee_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) NOT NULL,
        error_message TEXT NULL,
        FOREIGN KEY (invitee_id) REFERENCES invitees(id) ON DELETE CASCADE
    )");
}

/**
 * Add a new invitee to the database
 */
function addInvitee($nom, $fonction, $entreprise, $telephone, $email) {
    $conn = getDbConnection();
    
    // Generate a unique invitation code
    $codeInvitation = generateUniqueCode();
    while (checkInvitationCodeExists($codeInvitation)) {
        $codeInvitation = generateUniqueCode();
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Check for duplicate based on non-empty contact info
        $duplicateCheck = false;
        if (!empty($telephone) || !empty($email)) {
            $whereConditions = [];
            $params = [];
            
            if (!empty($telephone)) {
                $whereConditions[] = "telephone = :telephone";
                $params[':telephone'] = $telephone;
            }
            if (!empty($email)) {
                $whereConditions[] = "email = :email";
                $params[':email'] = $email;
            }
            
            if (!empty($whereConditions)) {
                $sql = "SELECT COUNT(*) FROM invitees WHERE " . implode(" OR ", $whereConditions);
                $stmt = $conn->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $duplicateCheck = (int)$stmt->fetchColumn() > 0;
            }
        }
        
        if ($duplicateCheck) {
            $conn->rollBack();
            return [
                'success' => false,
                'error' => 'duplicate',
                'message' => 'Un invité avec ce numéro de téléphone ou cette adresse email existe déjà.'
            ];
        }
        
        // Insert into invitees table
        $stmt = $conn->prepare("INSERT INTO invitees (nom, fonction, entreprise, telephone, email, code_invitation) 
                               VALUES (:nom, :fonction, :entreprise, :telephone, :email, :code_invitation)");
        
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':fonction', $fonction);
        $stmt->bindParam(':entreprise', $entreprise);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':code_invitation', $codeInvitation);
        $stmt->execute();
        
        // Get the inserted invitee's ID
        $inviteeId = $conn->lastInsertId();
        
        // Create an invitation record
        $stmt = $conn->prepare("INSERT INTO invitations (invitee_id) VALUES (:invitee_id)");
        $stmt->bindParam(':invitee_id', $inviteeId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'invitee_id' => $inviteeId,
            'code_invitation' => $codeInvitation
        ];
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log('Database Error: ' . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'database',
            'message' => 'Une erreur est survenue lors de l\'ajout de l\'invité.'
        ];
    }
}

/**
 * Check if an invitation code already exists
 */
function checkInvitationCodeExists($code) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM invitees WHERE code_invitation = :code");
    $stmt->bindParam(':code', $code);
    $stmt->execute();
    
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Get invitee by invitation code
 */
function getInviteeByCode($code) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT i.*, inv.confirmed, inv.email_sent, inv.invitation_generated
        FROM invitees i
        JOIN invitations inv ON i.id = inv.invitee_id
        WHERE i.code_invitation = :code
    ");
    
    $stmt->bindParam(':code', $code);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Mark an invitation as confirmed
 */
function confirmInvitation($code) {
    $conn = getDbConnection();
    
    try {
        $invitee = getInviteeByCode($code);
        
        if (!$invitee) {
            return [
                'success' => false,
                'error' => 'not_found',
                'message' => 'Invitation code not found.'
            ];
        }
        
        if ($invitee['confirmed']) {
            return [
                'success' => false,
                'error' => 'already_confirmed',
                'message' => 'This invitation has already been confirmed.'
            ];
        }
        
        $stmt = $conn->prepare("
            UPDATE invitations
            SET confirmed = 1, confirmation_date = NOW()
            WHERE invitee_id = :invitee_id
        ");
        
        $stmt->bindParam(':invitee_id', $invitee['id']);
        $stmt->execute();
        
        return [
            'success' => true,
            'invitee' => $invitee
        ];
    } catch (PDOException $e) {
        error_log('Database Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'database',
            'message' => 'An error occurred while confirming the invitation.'
        ];
    }
}

/**
 * Get statistics for dashboard
 */
function getInvitationStats() {
    $conn = getDbConnection();
    
    try {
        // Total invitees
        $stmt = $conn->query("SELECT COUNT(*) FROM invitees");
        $totalInvitees = (int)$stmt->fetchColumn();
        
        // Confirmed invitations
        $stmt = $conn->query("SELECT COUNT(*) FROM invitations WHERE confirmed = 1");
        $confirmedCount = (int)$stmt->fetchColumn();
        
        // Emails sent
        $stmt = $conn->query("SELECT COUNT(*) FROM invitations WHERE email_sent = 1");
        $emailsSentCount = (int)$stmt->fetchColumn();
        
        // Pending (not confirmed)
        $pendingCount = $totalInvitees - $confirmedCount;
        
        return [
            'success' => true,
            'stats' => [
                'total_invitees' => $totalInvitees,
                'confirmed_count' => $confirmedCount,
                'emails_sent_count' => $emailsSentCount,
                'pending_count' => $pendingCount
            ]
        ];
    } catch (PDOException $e) {
        error_log('Database Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'database',
            'message' => 'An error occurred while fetching invitation statistics.'
        ];
    }
}

/**
 * Get guests list with pagination
 */
function getGuestsList($page = 1, $limit = 10, $search = '', $statusFilter = 'all') {
    $conn = getDbConnection();
    $offset = ($page - 1) * $limit;
    
    try {
        $params = [];
        $whereConditions = [];
        
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $whereConditions[] = "(i.nom LIKE :search OR i.fonction LIKE :search OR i.entreprise LIKE :search OR i.email LIKE :search OR i.telephone LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        
        switch ($statusFilter) {
            case 'confirmed':
                $whereConditions[] = "inv.confirmed = 1";
                break;
            case 'pending':
                $whereConditions[] = "inv.confirmed = 0";
                break;
            case 'email_sent':
                $whereConditions[] = "inv.email_sent = 1";
                break;
            case 'email_pending':
                $whereConditions[] = "inv.email_sent = 0";
                break;
        }
        
        $whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);
        
        // Count total matching records
        $countQuery = "SELECT COUNT(*) FROM invitees i JOIN invitations inv ON i.id = inv.invitee_id $whereClause";
        $stmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalRecords = (int)$stmt->fetchColumn();
        
        // Get paginated results
        $query = "
            SELECT 
                i.id, i.nom, i.fonction, i.entreprise, i.email, i.telephone, i.code_invitation,
                inv.confirmed, inv.confirmation_date, inv.email_sent, inv.email_sent_date,
                inv.invitation_generated, inv.invitation_path
            FROM invitees i
            JOIN invitations inv ON i.id = inv.invitee_id
            $whereClause
            ORDER BY i.date_added DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $guests = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => [
                'guests' => $guests,
                'pagination' => [
                    'total' => $totalRecords,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($totalRecords / $limit)
                ]
            ]
        ];
    } catch (PDOException $e) {
        error_log('Database Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'database',
            'message' => 'An error occurred while fetching the guest list.'
        ];
    }
}

/**
 * Log email sending
 */
function logEmailSent($inviteeId, $subject, $message, $status, $errorMessage = null) {
    $conn = getDbConnection();
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO email_logs (invitee_id, subject, message, status, error_message)
            VALUES (:invitee_id, :subject, :message, :status, :error_message)
        ");
        
        $stmt->bindParam(':invitee_id', $inviteeId);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':error_message', $errorMessage);
        $stmt->execute();
        
        if ($status === 'success') {
            $stmt = $conn->prepare("
                UPDATE invitations
                SET email_sent = 1, email_sent_date = NOW()
                WHERE invitee_id = :invitee_id
            ");
            
            $stmt->bindParam(':invitee_id', $inviteeId);
            $stmt->execute();
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Database Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update invitation generated status
 */
function updateInvitationGenerated($inviteeId, $invitationPath) {
    $conn = getDbConnection();
    
    try {
        $stmt = $conn->prepare("
            UPDATE invitations
            SET invitation_generated = 1, invitation_path = :path
            WHERE invitee_id = :invitee_id
        ");
        
        $stmt->bindParam(':invitee_id', $inviteeId);
        $stmt->bindParam(':path', $invitationPath);
        $stmt->execute();
        
        return true;
    } catch (PDOException $e) {
        error_log('Database Error: ' . $e->getMessage());
        return false;
    }
}

// Create tables if they don't exist
createTables();