<?php
require_once '../vendor/autoload.php';
require_once '../database.php';
require_once 'config.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header('Content-Type: application/json');

// 1. VALIDAZIONE JWT (Chi sta facendo l'operazione?)
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$jwt = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) { $jwt = $matches[1]; }

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token mancante"]);
    exit;
}

try {
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    $adminEmail = $decoded->sub; // Email di chi esegue l'azione
    
    // OPZIONALE: Controlla se l'admin ha davvero il ruolo ADMIN nel payload
    if ($decoded->role !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Azione permessa solo agli Admin"]);
        exit;
    }

    global $mysqli;
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
    $targetEmail = $data['email'] ?? ''; // L'utente da modificare

    // Impedisci di modificare se stessi per sicurezza
    if ($targetEmail === $adminEmail) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non puoi modificare i tuoi permessi']);
        exit;
    }

    // 2. RECUPERO ID_RUOLO DEL TARGET (L'utente che riceve/perde il permesso)
    $queryRuolo = "SELECT id_ruolo FROM UTENTE_RUOLO WHERE email_utente = ?";
    $stmtRuolo = $mysqli->prepare($queryRuolo);
    $stmtRuolo->bind_param("s", $targetEmail);
    $stmtRuolo->execute();
    $resTarget = $stmtRuolo->get_result();
    
    if ($resTarget->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Utente target non trovato']);
        exit;
    }
    $idRuoloTarget = $resTarget->fetch_assoc()['id_ruolo'];

    // 3. ESECUZIONE AZIONI
    if ($action == 'remove') {
        $permessoId = intval($data['permesso_id']);
        $queryDelete = "DELETE FROM RUOLO_PERMESSO WHERE id_ruolo = ? AND id_permesso = ?";
        $stmt = $mysqli->prepare($queryDelete);
        $stmt->bind_param("ii", $idRuoloTarget, $permessoId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Permesso rimosso con successo']);
        }

    } else if ($action == 'add') {
        $codice = $data['codice'];
        $desc = $data['descrizione'] ?? '';

        // Recupera o crea l'ID del permesso
        $stmtCheck = $mysqli->prepare("SELECT id FROM PERMESSO WHERE codice = ?");
        $stmtCheck->bind_param("s", $codice);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($resCheck->num_rows > 0) {
            $permessoId = $resCheck->fetch_assoc()['id'];
        } else {
            $stmtIns = $mysqli->prepare("INSERT INTO PERMESSO (codice, descrizione) VALUES (?, ?)");
            $stmtIns->bind_param("ss", $codice, $desc);
            $stmtIns->execute();
            $permessoId = $mysqli->insert_id;
        }

        // Associa il permesso al ruolo del target
        $queryInsert = "INSERT IGNORE INTO RUOLO_PERMESSO (id_ruolo, id_permesso) VALUES (?, ?)";
        $stmtFinal = $mysqli->prepare($queryInsert);
        $stmtFinal->bind_param("ii", $idRuoloTarget, $permessoId);
        
        if ($stmtFinal->execute()) {
            echo json_encode(['success' => true, 'message' => 'Permesso aggiunto con successo']);
        }
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token non valido: ' . $e->getMessage()]);
}
?>