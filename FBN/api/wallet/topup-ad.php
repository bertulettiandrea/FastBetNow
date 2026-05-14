<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

$token = getBearerToken();

if (!$token || !verifyJWT($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

$payload = decodeJWT($token);
$email = $payload['sub'];

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['amount']) || $data['amount'] <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Importo non valido']);
    exit;
}

$amount = (float) $data['amount'];
$maxAmount = 50.00;

if ($amount > $maxAmount) {
    http_response_code(400);
    echo json_encode(['error' => "Importo massimo per topup: €$maxAmount"]);
    exit;
}

global $pdo;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT saldo FROM CONTO WHERE email_intestatario = ? FOR UPDATE");
    $stmt->execute([$email]);
    $conto = $stmt->fetch();

    if (!$conto) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Conto non trovato']);
        exit;
    }

    $newSaldo = $conto['saldo'] + $amount;

    $updateStmt = $pdo->prepare("UPDATE CONTO SET saldo = saldo + ? WHERE email_intestatario = ?");
    $updateStmt->execute([$amount, $email]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'email' => $email,
        'amount_added' => $amount,
        'new_saldo' => (float) $newSaldo,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
