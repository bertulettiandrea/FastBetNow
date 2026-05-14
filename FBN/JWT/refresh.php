<?php
require_once '../vendor/autoload.php';
require_once '../database.php';
require_once 'config.php';

use \Firebase\JWT\JWT;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['refresh_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'refresh_token richiesto']);
    exit;
}

$refreshToken = $data['refresh_token'];
global $pdo;

$stmt = $pdo->prepare("SELECT email FROM UTENTE WHERE refresh_token = ?");
$stmt->execute([$refreshToken]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Refresh token non valido']);
    exit;
}

$email = $user['email'];

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
    'sub'  => $email,
    'role' => $ruolo,
    'permissions' => $permessi,
    'perm_count'  => count($permessi)
];

$newAccessToken = JWT::encode($payload, JWT_SECRET, 'HS256');
$newRefreshToken = bin2hex(random_bytes(40));

$updateStmt = $pdo->prepare("UPDATE UTENTE SET refresh_token = ? WHERE email = ?");
$updateStmt->execute([$newRefreshToken, $email]);

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'access_token' => $newAccessToken,
    'refresh_token' => $newRefreshToken,
    'expires_in' => ACCESS_TOKEN_EXPIRATION
]);
?>