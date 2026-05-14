<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth_helper.php';
require_once __DIR__ . '/../tenant_helper.php';
require_once __DIR__ . '/../services/BetService.php';

$method = $_SERVER['REQUEST_METHOD'];
$token = getBearerToken();

if (!$token || !verifyJWT($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

$payload = decodeJWT($token);
$email = $payload['sub'];

if (!hasPermissionJWT($pdo, $email, 'PUNTA_SCHEDINA')) {
    http_response_code(403);
    echo json_encode(['error' => 'Non hai permessi per puntare']);
    exit;
}

// Verificare accesso al tenant
requireTenantAccess();

global $pdo;

$tenantId = getTenantIdFromRequest();

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['selezioni']) || !is_array($data['selezioni'])) {
        http_response_code(400);
        echo json_encode(['error' => 'selezioni array richiesto']);
        exit;
    }

    if (!isset($data['importo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'importo richiesto']);
        exit;
    }

    $importo = (float) $data['importo'];
    $selezioni = $data['selezioni'];

    try {
        $betService = new BetService($pdo, $tenantId);
        $result = $betService->placeSchedinaMultiplaBet(
            $email,
            $selezioni,
            $importo,
            count($selezioni),
            array_reduce($selezioni, function ($carry, $sel) {
                return $carry * (float) $sel['quota'];
            }, 1.0),
            0,
            0,
            0,
            0
        );

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'id_schedina' => $result['id_schedina'],
            'importo' => $result['importo'],
            'quota_totale' => $result['quota_totale'],
            'vincita_potenziale' => $result['vincita_potenziale'],
            'created_at' => $result['created_at']
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($method === 'GET') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id schedina richiesto']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            id, 
            importo_totale, 
            quota_totale, 
            vincita_potenziale, 
            stato, 
            created_at, 
            updated_at
        FROM SCHEDINA
        WHERE id = ? AND email_utente = ? AND tenant_id = ?
    ");
    $stmt->execute([$id, $email, $tenantId]);
    $schedina = $stmt->fetch();

    if (!$schedina) {
        http_response_code(404);
        echo json_encode(['error' => 'Schedina non trovata']);
        exit;
    }

    $puntateStmt = $pdo->prepare("
        SELECT 
            id,
            id_partita,
            squadra_casa,
            squadra_trasferta,
            segno,
            quota,
            importo,
            vincita_potenziale,
            stato
        FROM PUNTATA
        WHERE id_schedina = ? AND tenant_id = ?
    ");
    $puntateStmt->execute([$id, $tenantId]);
    $puntate = $puntateStmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'id_schedina' => (int) $schedina['id'],
        'importo' => (float) $schedina['importo_totale'],
        'quota_totale' => (float) $schedina['quota_totale'],
        'vincita_potenziale' => (float) $schedina['vincita_potenziale'],
        'stato' => $schedina['stato'],
        'created_at' => $schedina['created_at'],
        'updated_at' => $schedina['updated_at'],
        'puntate' => array_map(function ($p) {
            return [
                'id' => (int) $p['id'],
                'id_partita' => (int) $p['id_partita'],
                'squadra_casa' => $p['squadra_casa'],
                'squadra_trasferta' => $p['squadra_trasferta'],
                'segno' => $p['segno'],
                'quota' => (float) $p['quota'],
                'importo' => (float) $p['importo'],
                'vincita_potenziale' => (float) $p['vincita_potenziale'],
                'stato' => $p['stato']
            ];
        }, $puntate)
    ]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
}
?>
