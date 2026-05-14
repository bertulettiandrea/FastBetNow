<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth_helper.php';
require_once __DIR__ . '/../tenant_helper.php';

$token = getBearerToken();

if (!$token || !verifyJWT($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

$payload = decodeJWT($token);
$email = $payload['sub'];

if (!hasPermissionJWT($pdo, $email, 'VISUALIZZA_PARTITE')) {
    http_response_code(403);
    echo json_encode(['error' => 'Non hai permessi per visualizzare le partite']);
    exit;
}

// Verificare accesso al tenant
requireTenantAccess();

global $pdo;

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'id partita richiesto']);
    exit;
}

$tenantId = getTenantIdFromRequest();

$stmt = $pdo->prepare("
    SELECT 
        id_partita,
        tenant_id,
        squadra_casa,
        squadra_trasferta,
        data_inizio,
        campionato,
        quota_casa,
        quota_pareggio,
        quota_trasferta,
        stato,
        created_at,
        updated_at
    FROM PARTITA
    WHERE id_partita = ? AND tenant_id = ?
");
$stmt->execute([$id, $tenantId]);
$partita = $stmt->fetch();

if (!$partita) {
    http_response_code(404);
    echo json_encode(['error' => 'Partita non trovata']);
    exit;
}

http_response_code(200);
echo json_encode([
    'id_partita' => (int) $partita['id_partita'],
    'squadra_casa' => $partita['squadra_casa'],
    'squadra_trasferta' => $partita['squadra_trasferta'],
    'data_inizio' => $partita['data_inizio'],
    'campionato' => $partita['campionato'],
    'odds' => [
        'casa' => (float) $partita['quota_casa'],
        'pareggio' => (float) $partita['quota_pareggio'],
        'trasferta' => (float) $partita['quota_trasferta']
    ],
    'stato' => $partita['stato'],
    'updated_at' => $partita['updated_at']
]);
?>
