<?php
require_once '../vendor/autoload.php';
require_once '../database.php';
require_once 'config.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = [];
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';

if (!$authHeader && function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}

if (!$authHeader && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}

if (!$authHeader && !empty($_SERVER['HTTP_X_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_X_AUTHORIZATION'];
}

$jwt = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) { $jwt = $matches[1]; }

if (!$jwt) {
    $jwt = $data['access_token'] ?? $data['token'] ?? ($_GET['access_token'] ?? $_GET['token'] ?? null);
}

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token mancante"]);
    exit;
}

try {
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    $adminEmail = $decoded->sub;

    if ($decoded->role !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Azione permessa solo agli Admin"]);
        exit;
    }

    global $pdo;
    $action = $data['action'] ?? null;
    $targetEmail = $data['email'] ?? ''; 

    if ($targetEmail === $adminEmail) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non puoi modificare i tuoi permessi', 'azione' => $action, 'email_target' => $targetEmail]);
        exit;
    }

    $queryRuolo = "SELECT id_ruolo FROM UTENTE_RUOLO WHERE email_utente = ?";
    $stmtRuolo = $pdo->prepare($queryRuolo);
    $stmtRuolo->execute([$targetEmail]);
    $targetRole = $stmtRuolo->fetch();

    if (!$targetRole) {
        echo json_encode(['success' => false, 'message' => 'Utente target non trovato','azione' => $action, 'email_target' => $targetEmail]);
        exit;
    }
    $idRuoloTarget = $targetRole['id_ruolo'];

    if ($action == 'remove') {
        $permessoId = intval($data['permesso_id']);
        $queryDelete = "DELETE FROM RUOLO_PERMESSO WHERE id_ruolo = ? AND id_permesso = ?";
        $stmt = $pdo->prepare($queryDelete);
        
        if ($stmt->execute([$idRuoloTarget, $permessoId])) {
            echo json_encode(['success' => true, 'message' => 'Permesso rimosso con successo']);
        }

    } else if ($action == 'add') {
        $codice = $data['codice'];
        $desc = $data['descrizione'] ?? '';

        $stmtCheck = $pdo->prepare("SELECT id FROM PERMESSO WHERE codice = ?");
        $stmtCheck->execute([$codice]);
        $existingPerm = $stmtCheck->fetch();

        if ($existingPerm) {
            $permessoId = $existingPerm['id'];
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO PERMESSO (codice, descrizione) VALUES (?, ?)");
            $stmtIns->execute([$codice, $desc]);
            $permessoId = $pdo->lastInsertId();
        }
        $queryInsert = "INSERT IGNORE INTO RUOLO_PERMESSO (id_ruolo, id_permesso) VALUES (?, ?)";
        $stmtFinal = $pdo->prepare($queryInsert);
        
        if ($stmtFinal->execute([$idRuoloTarget, $permessoId])) {
            echo json_encode(['success' => true, 'message' => 'Permesso aggiunto con successo']);
        }
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token non valido: ' . $e->getMessage()]);
}
?>