<?php
require_once '../vendor/autoload.php';
require_once '../database.php';
require_once 'config.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header('Content-Type: application/json');

// 1. Recupero del token dall'header Authorization
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$jwt = null;

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $jwt = $matches[1];
}

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token mancante. Accesso negato."]);
    exit;
}

try {
    // 2. Validazione del Token
    // Se è scaduto o manomesso, il metodo decode lancia un'eccezione
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    $userEmail = $decoded->sub; // Estraiamo l'email (il subject)

    global $mysqli;

    // 3. Query JOIN per ottenere i codici dei permessi
    $query = "
        SELECT DISTINCT P.codice 
        FROM UTENTE_RUOLO UR
        JOIN RUOLO_PERMESSO RP ON UR.id_ruolo = RP.id_ruolo
        JOIN PERMESSO P ON RP.id_permesso = P.id
        WHERE UR.email_utente = ?
    ";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    $permessi = [];
    while ($row = $result->fetch_assoc()) {
        $permessi[] = $row['codice'];
    }

    // 4. Risposta con i dati protetti
    echo json_encode([
        "status" => "success",
        "email" => $userEmail,
        "permessi" => $permessi
    ]);

} catch (Exception $e) {
    // Se il token è scaduto (ExpiredException) o invalido
    http_response_code(401);
    echo json_encode([
        "status" => "error", 
        "message" => "Token non valido o scaduto: " . $e->getMessage()
    ]);
}