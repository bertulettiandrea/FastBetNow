<?php
// JWT/refresh.php
header('Content-Type: application/json');

require_once '../database.php';
require_once '../vendor/autoload.php';
require_once 'config.php';

use \Firebase\JWT\JWT;

global $mysqli;

// Recuperiamo il refresh token inviato dal client (solitamente via JSON)
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->refresh_token)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Refresh token mancante"]);
    exit;
}

$refreshToken = $data->refresh_token;

try {
    // Verifichiamo se il refresh token esiste nel database
    $stmt = $mysqli->prepare("SELECT email, nome FROM UTENTE WHERE refresh_token = ?");
    $stmt->bind_param("s", $refreshToken);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Il refresh token è valido! Generiamo un nuovo Access Token (JWT)
        $issuedAt = time();
        $expire = $issuedAt + ACCESS_TOKEN_EXPIRATION; // Altri 5 minuti
        
        $payload = [
            'iat'  => $issuedAt,
            'exp'  => $expire,
            'sub'  => $user['email']
        ];

        $newAccessToken = JWT::encode($payload, JWT_SECRET, 'HS256');

        echo json_encode([
            "status" => "success",
            "access_token" => $newAccessToken
        ]);
    } else {
        // Refresh token non trovato o revocato
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Sessione scaduta, effettua di nuovo il login"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Errore server: " . $e->getMessage()]);
}