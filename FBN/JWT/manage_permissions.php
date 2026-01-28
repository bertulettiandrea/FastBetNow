<?php
session_start();
include_once '../database.php';

header('Content-Type: application/json');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

global $mysqli;
$email = $_SESSION['user_email'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

try {
    if ($action == 'remove') {
        // Nessuno può modificare i propri permessi
        $targetEmail = $data['email'] ?? '';
        if ($targetEmail === $email) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non puoi modificare i tuoi permessi']);
            exit;
        }
        
        // Rimuovi un permesso dall'utente
        $permessoId = intval($data['permesso_id']);
        
        // Primo: ottieni l'id_ruolo dell'utente
        $queryRuolo = "SELECT id_ruolo FROM UTENTE_RUOLO WHERE email_utente = ?";
        $stmtRuolo = $mysqli->prepare($queryRuolo);
        $stmtRuolo->bind_param("s", $email);
        $stmtRuolo->execute();
        $resultRuolo = $stmtRuolo->get_result();
        
        if (!$resultRuolo || $resultRuolo->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
            exit;
        }
        
        $rowRuolo = $resultRuolo->fetch_assoc();
        $idRuolo = $rowRuolo['id_ruolo'];
        
        // Rimuovi l'associazione ruolo-permesso
        $queryDelete = "DELETE FROM RUOLO_PERMESSO WHERE id_ruolo = ? AND id_permesso = ?";
        $stmtDelete = $mysqli->prepare($queryDelete);
        $stmtDelete->bind_param("ii", $idRuolo, $permessoId);
        
        if ($stmtDelete->execute()) {
            echo json_encode(['success' => true, 'message' => 'Permesso rimosso']);
        } else {
            echo json_encode(['success' => false, 'message' => $mysqli->error]);
        }
        
    } else if ($action == 'add') {
        // Nessuno può modificare i propri permessi
        $targetEmail = $data['email'] ?? '';
        if ($targetEmail === $email) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non puoi modificare i tuoi permessi']);
            exit;
        }
        
        // Aggiungi un permesso all'utente
        $codice = $data['codice'];
        $descrizione = $data['descrizione'] ?? '';
        
        // Verifica che il permesso non esista già
        $queryCheck = "SELECT id FROM PERMESSO WHERE codice = ?";
        $stmtCheck = $mysqli->prepare($queryCheck);
        $stmtCheck->bind_param("s", $codice);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        
        $permessoId = null;
        if ($resultCheck->num_rows > 0) {
            $rowCheck = $resultCheck->fetch_assoc();
            $permessoId = $rowCheck['id'];
        } else {
            // Crea un nuovo permesso
            $queryInsertPermesso = "INSERT INTO PERMESSO (codice, descrizione) VALUES (?, ?)";
            $stmtInsertPermesso = $mysqli->prepare($queryInsertPermesso);
            $stmtInsertPermesso->bind_param("ss", $codice, $descrizione);
            
            if (!$stmtInsertPermesso->execute()) {
                echo json_encode(['success' => false, 'message' => $mysqli->error]);
                exit;
            }
            $permessoId = $mysqli->insert_id;
        }
        
        // Ottieni l'id_ruolo dell'utente
        $queryRuolo = "SELECT id_ruolo FROM UTENTE_RUOLO WHERE email_utente = ?";
        $stmtRuolo = $mysqli->prepare($queryRuolo);
        $stmtRuolo->bind_param("s", $email);
        $stmtRuolo->execute();
        $resultRuolo = $stmtRuolo->get_result();
        
        if (!$resultRuolo || $resultRuolo->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
            exit;
        }
        
        $rowRuolo = $resultRuolo->fetch_assoc();
        $idRuolo = $rowRuolo['id_ruolo'];
        
        // Verifica che l'associazione non esista già
        $queryCheckAssoc = "SELECT id FROM RUOLO_PERMESSO WHERE id_ruolo = ? AND id_permesso = ?";
        $stmtCheckAssoc = $mysqli->prepare($queryCheckAssoc);
        $stmtCheckAssoc->bind_param("ii", $idRuolo, $permessoId);
        $stmtCheckAssoc->execute();
        
        if ($stmtCheckAssoc->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Questo permesso è già assegnato']);
            exit;
        }
        
        // Aggiungi l'associazione
        $queryInsert = "INSERT INTO RUOLO_PERMESSO (id_ruolo, id_permesso) VALUES (?, ?)";
        $stmtInsert = $mysqli->prepare($queryInsert);
        $stmtInsert->bind_param("ii", $idRuolo, $permessoId);
        
        if ($stmtInsert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Permesso aggiunto']);
        } else {
            echo json_encode(['success' => false, 'message' => $mysqli->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
