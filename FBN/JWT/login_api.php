<?php
require_once '../vendor/autoload.php';
require_once '../database.php';
require_once 'config.php';

use \Firebase\JWT\JWT;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Dati incompleti"]);
    exit;
}

$email = $data->email;
$password = $data->password;

global $mysqli;

$stmt = $mysqli->prepare("SELECT email, password FROM UTENTE WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    
    $issuedAt = time();
    $expire = $issuedAt + ACCESS_TOKEN_EXPIRATION;

    $payload = [
        'iat'  => $issuedAt,
        'exp'  => $expire,
        'sub'  => $user['email']
    ];

    $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');
    $refreshToken = bin2hex(random_bytes(40));

    // Salvataggio refresh token nel DB
    $updateStmt = $mysqli->prepare("UPDATE UTENTE SET refresh_token = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $refreshToken, $email);
    $updateStmt->execute();

    echo json_encode([
        "status" => "success",
        "access_token" => $jwt,
        "refresh_token" => $refreshToken,
        "expires_in" => ACCESS_TOKEN_EXPIRATION
    ]);

} else {
    http_response_code(401);
    echo json_encode(["message" => "Credenziali errate"]);
}