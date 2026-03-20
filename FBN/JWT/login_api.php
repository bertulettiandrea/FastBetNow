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

global $pdo;

$stmt = $pdo->prepare("SELECT email, password FROM UTENTE WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {

    $queryInfo = "
        SELECT R.nome as ruolo, P.codice, P.descrizione 
        FROM UTENTE_RUOLO UR
        JOIN RUOLO R ON UR.id_ruolo = R.id
        LEFT JOIN RUOLO_PERMESSO RP ON R.id = RP.id_ruolo
        LEFT JOIN PERMESSO P ON RP.id_permesso = P.id
        WHERE UR.email_utente = ?
    ";
    $stmtI = $pdo->prepare($queryInfo);
    $stmtI->execute([$email]);

    $ruolo = "Ospite";
    $permessi = [];
    while ($row = $stmtI->fetch()) {
        $ruolo = $row['ruolo'];
        if ($row['codice']) {
            $permessi[] = [
                'cod' => $row['codice'],
                'desc' => $row['descrizione']
            ];
        }
    }

    $issuedAt = time();
    $expire = $issuedAt + ACCESS_TOKEN_EXPIRATION;

    $payload = [
        'iat'  => $issuedAt,
        'exp'  => $expire,
        'sub'  => $user['email'],
        'role' => $ruolo,
        'permissions' => $permessi,
        'perm_count'  => count($permessi)
    ];

    $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');
    $refreshToken = bin2hex(random_bytes(40));

    $updateStmt = $pdo->prepare("UPDATE UTENTE SET refresh_token = ? WHERE email = ?");
    $updateStmt->execute([$refreshToken, $email]);

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
