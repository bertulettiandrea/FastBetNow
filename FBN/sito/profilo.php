<?php
session_start();
require_once '../database.php';
require_once '../auth_helper.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: ../login.php');
    exit;
}

if (!isDatabaseConnected()) {
    http_response_code(503);
    die("Servizio temporaneamente non disponibile");
}

$email = $_SESSION['user_email'];

$userData = null;
$permissions = [];

try {
    $stmt = $pdo->prepare("
        SELECT u.email, u.nome, u.created_at, c.saldo, c.bonus, r.nome as ruolo
        FROM UTENTE u
        LEFT JOIN CONTO c ON u.email = c.email_intestatario
        LEFT JOIN UTENTE_RUOLO ur ON u.email = ur.email_utente
        LEFT JOIN RUOLO r ON ur.id_ruolo = r.id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $userData = $stmt->fetch();

    if (!$userData) {
        header('Location: ../logout.php');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT p.codice, p.descrizione
        FROM UTENTE_RUOLO ur
        JOIN RUOLO_PERMESSO rp ON ur.id_ruolo = rp.id_ruolo
        JOIN PERMESSO p ON rp.id_permesso = p.id
        WHERE ur.email_utente = ?
    ");
    $stmt->execute([$email]);
    $permissions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore profilo.php: " . $e->getMessage());
    http_response_code(500);
    die("Errore nel caricamento del profilo: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .section { margin: 20px 0; }
        .label { font-weight: bold; color: #555; }
        .value { color: #333; margin: 5px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; }
        .permission { display: inline-block; background: #007bff; color: white; padding: 5px 10px; margin: 5px 5px 5px 0; border-radius: 4px; font-size: 12px; }
        .button-group { margin-top: 20px; }
        a, button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; display: inline-block; margin-right: 10px; }
        a:hover, button:hover { background: #0056b3; }
        .danger { background: #dc3545; }
        .danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Profilo Utente</h1>

        <div class="section">
            <div class="label">Email:</div>
            <div class="value"><?php echo htmlspecialchars($userData['email']); ?></div>
        </div>

        <div class="section">
            <div class="label">Nome:</div>
            <div class="value"><?php echo htmlspecialchars($userData['nome'] ?? 'Non impostato'); ?></div>
        </div>

        <div class="section">
            <div class="label">Ruolo:</div>
            <div class="value"><?php echo htmlspecialchars($userData['ruolo'] ?? 'Utente'); ?></div>
        </div>

        <div class="section">
            <div class="label">Saldo Conto:</div>
            <div class="value">€ <?php echo number_format($userData['saldo'] ?? 0, 2, ',', '.'); ?></div>
        </div>

        <div class="section">
            <div class="label">Bonus:</div>
            <div class="value">€ <?php echo number_format($userData['bonus'] ?? 0, 2, ',', '.'); ?></div>
        </div>

        <div class="section">
            <div class="label">Data Registrazione:</div>
            <div class="value"><?php 
                if ($userData['created_at']) {
                    echo date('d/m/Y H:i', strtotime($userData['created_at']));
                } else {
                    echo 'Non disponibile';
                }
            ?></div>
        </div>

        <div class="section">
            <div class="label">Permessi:</div>
            <div class="value">
                <?php if (empty($permissions)): ?>
                    Nessun permesso
                <?php else: ?>
                    <?php foreach ($permissions as $perm): ?>
                        <span class="permission" title="<?php echo htmlspecialchars($perm['descrizione']); ?>">
                            <?php echo htmlspecialchars($perm['codice']); ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="button-group">
            <a href="index.php">Home</a>
            <a href="logout.php" class="danger">Logout</a>
        </div>
    </div>
</body>
</html>
