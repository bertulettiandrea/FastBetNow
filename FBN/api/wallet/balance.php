<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../auth_helper.php';

$token = getBearerToken();

if (!$token || !verifyJWT($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

$payload = decodeJWT($token);
$email = $payload['sub'];

global $pdo;

$stmt = $pdo->prepare("SELECT saldo, bonus FROM CONTO WHERE email_intestatario = ?");
$stmt->execute([$email]);
$conto = $stmt->fetch();

if (!$conto) {
    http_response_code(404);
    echo json_encode(['error' => 'Conto non trovato']);
    exit;
}

http_response_code(200);
echo json_encode([
    'email' => $email,
    'saldo' => (float) $conto['saldo'],
    'bonus' => (float) $conto['bonus'],
    'total' => (float) $conto['saldo'] + (float) $conto['bonus']
]);
?>
