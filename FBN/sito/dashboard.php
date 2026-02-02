<?php
session_start();
include_once '../database.php'; 

if (!isset($_SESSION['user_email'])) {
    header("Location: ../login.php");
    exit();
}

global $mysqli;
$email = $_SESSION['user_email'];
$nome_utente = $_SESSION['user_nome'];
$ruolo_nome = "Ospite"; 

try {
    // 1. RECUPERO DATI DAL DB (Solo per costruire il Token)
    $query = "SELECT R.nome FROM UTENTE_RUOLO UR JOIN RUOLO R ON UR.id_ruolo = R.id WHERE UR.email_utente = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) { $ruolo_nome = $row['nome']; }

    $queryP = "SELECT P.codice, P.descrizione FROM UTENTE_RUOLO UR 
               JOIN RUOLO_PERMESSO RP ON UR.id_ruolo = RP.id_ruolo
               JOIN PERMESSO P ON RP.id_permesso = P.id
               WHERE UR.email_utente = ?";
    $stmtP = $mysqli->prepare($queryP);
    $stmtP->bind_param("s", $email);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    
    $lista_permessi_db = [];
    while ($p = $resP->fetch_assoc()) {
        $lista_permessi_db[] = [
            'cod' => $p['codice'],
            'desc' => $p['descrizione']
        ];
    }

    // 2. GENERAZIONE JWT
    function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload_data = [
        'iss' => 'FastBetNow',
        'email' => $email,
        'role' => $ruolo_nome,
        'permissions' => $lista_permessi_db,
        'perm_count' => count($lista_permessi_db),
        'iat' => time(),
        'exp' => time() + 3600
    ];
    
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode(json_encode($payload_data));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'secret_key_123', true);
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . base64UrlEncode($signature);

    // 3. ESTRAZIONE DATI DAL JWT (Fonte di verità per la stampa)
    $tokenParts = explode('.', $jwt);
    $payloadDecoded = json_decode(base64_decode($tokenParts[1]), true);
    
    $display_permissions = $payloadDecoded['permissions'];
    $display_count = $payloadDecoded['perm_count'];

} catch (Exception $e) {
    die("Errore: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - FastBetNow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .jwt-code { background: #282c34; color: #61dafb; padding: 15px; border-radius: 8px; word-break: break-all; font-family: monospace; font-size: 0.8rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <div class="container">
        <span class="navbar-brand">FastBetNow</span>
        <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card p-4 text-center">
                <h4><?php echo htmlspecialchars($nome_utente); ?></h4>
                <span class="badge <?php echo ($ruolo_nome == 'ADMIN') ? 'bg-danger' : 'bg-success'; ?> mb-3">
                    <?php echo htmlspecialchars($ruolo_nome); ?>
                </span>
                <hr>
                <p class="mb-0">Permessi totali nel JWT:</p>
                <h2 class="display-6 fw-bold text-primary"><?php echo $display_count; ?></h2>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card p-4 mb-4">
                <h5 class="text-secondary">JWT Session Token</h5>
                <div class="jwt-code"><?php echo $jwt; ?></div>
            </div>

            <div class="card p-4">
                <h5 class="mb-3">Dettaglio Permessi (dal Payload)</h5>
                <?php if ($display_count > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($display_permissions as $p): ?>
                            <div class="list-group-item px-0">
                                <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($p['cod']); ?></h6>
                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($p['desc']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Nessun permesso incluso nel token.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Per debug: visualizza il payload decodificato in console
    console.log("JWT Payload:", <?php echo json_encode($payloadDecoded); ?>);
</script>

</body>
</html>