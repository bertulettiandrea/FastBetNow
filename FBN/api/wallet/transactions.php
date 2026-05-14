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

$limit = (int) ($_GET['limit'] ?? 20);
$offset = (int) ($_GET['offset'] ?? 0);

$limit = min($limit, 100);
$offset = max($offset, 0);

$stmt = $pdo->prepare("
    SELECT 
        id_schedina, 
        importo, 
        quota_totale, 
        vincita_potenziale, 
        stato, 
        created_at, 
        updated_at
    FROM SCHEDINA
    WHERE email_utente = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$email, $limit, $offset]);
$transactions = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM SCHEDINA WHERE email_utente = ?");
$countStmt->execute([$email]);
$count = $countStmt->fetch()['total'];

http_response_code(200);
echo json_encode([
    'email' => $email,
    'total' => (int) $count,
    'limit' => $limit,
    'offset' => $offset,
    'transactions' => array_map(function ($t) {
        return [
            'id_schedina' => (int) $t['id_schedina'],
            'importo' => (float) $t['importo'],
            'quota_totale' => (float) $t['quota_totale'],
            'vincita_potenziale' => (float) $t['vincita_potenziale'],
            'stato' => $t['stato'],
            'created_at' => $t['created_at'],
            'updated_at' => $t['updated_at']
        ];
    }, $transactions)
]);
?>
