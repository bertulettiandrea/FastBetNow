<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'database.php';
require_once 'vendor/autoload.php';
require_once 'JWT/config.php';
 
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

global $pdo;

$error = '';
$success = '';
$isJsonRequest = false;

function getUserRoleAndPermissions($email, $pdo) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.nome as ruolo 
        FROM UTENTE u
        JOIN UTENTE_RUOLO ur ON u.email = ur.email_utente
        JOIN RUOLO r ON ur.id_ruolo = r.id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $roleResult = $stmt->fetch();
    
    if (!$roleResult) {
        return ['ruolo' => 'UTENTE', 'permessi' => []];
    }

    $stmtPerm = $pdo->prepare("
        SELECT p.codice, p.descrizione
        FROM RUOLO_PERMESSO rp
        JOIN PERMESSO p ON rp.id_permesso = p.id
        WHERE rp.id_ruolo = ?
    ");
    $stmtPerm->execute([$roleResult['id']]);
    
    $permessi = [];
    while ($perm = $stmtPerm->fetch()) {
        $permessi[] = $perm['codice'];
    }
    
    return [
        'ruolo' => $roleResult['ruolo'],
        'permessi' => $permessi
    ];
}

function generateUserTokens($email, $nome, $ruolo, $permessi, $pdo) {
    $issuedAt = time();
    $expire = $issuedAt + ACCESS_TOKEN_EXPIRATION;
    $payload = [
        'iat'  => $issuedAt,
        'exp'  => $expire,
        'sub'  => $email,
        'nome' => $nome,
        'ruolo' => $ruolo,
        'permessi' => $permessi
    ];
    $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

    $refreshToken = bin2hex(random_bytes(40));
    $upd = $pdo->prepare("UPDATE UTENTE SET refresh_token = ? WHERE email = ?");
    $upd->execute([$refreshToken, $email]);

    return ['access' => $jwt, 'refresh' => $refreshToken];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    (isset($_SERVER['HTTP_CONTENT_TYPE']) && strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false ||
     strpos(file_get_contents("php://input"), '{') === 0)) {
   
    $isJsonRequest = true;
    $data = json_decode(file_get_contents("php://input"));
   
    if(isset($data->email) && isset($data->password)){
        $stmt = $pdo->prepare("SELECT email, password, nome FROM UTENTE WHERE email = ?");
        $stmt->execute([$data->email]);
        $row = $stmt->fetch();
       
        if ($row && password_verify($data->password, $row['password'])) {
            $roleData = getUserRoleAndPermissions($row['email'], $pdo);
            $tokens = generateUserTokens($row['email'], $row['nome'], $roleData['ruolo'], $roleData['permessi'], $pdo);
           
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "success",
                "access_token" => $tokens['access'],
                "refresh_token" => $tokens['refresh'],
                "user" => $row['nome'],
                "email" => $row['email'],
                "ruolo" => $roleData['ruolo'],
                "permessi" => $roleData['permessi']
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Credenziali errate"]);
            exit;
        }
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
   
    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT email, password, nome FROM UTENTE WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
       
        if ($row && password_verify($password, $row['password'])) {
            $roleData = getUserRoleAndPermissions($row['email'], $pdo);

            $tokens = generateUserTokens($row['email'], $row['nome'], $roleData['ruolo'], $roleData['permessi'], $pdo);

            $_SESSION['user_email'] = $email;
            $_SESSION['user_nome'] = $row['nome'];
            $_SESSION['access_token'] = $tokens['access'];
            $_SESSION['user_ruolo'] = $roleData['ruolo'];
            $_SESSION['user_permessi'] = $roleData['permessi'];
            
            header('Location: sito/index.php', true, 303);
            exit();
        } else {
            $error = 'Email o password non validi';
        }
    }
}

if (!$isJsonRequest) {
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - FastBetNow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: white; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; width: 100%; max-width: 400px; }
        .login-header h1 { color: #667eea; font-weight: 700; text-align: center; }
        .form-group { margin-bottom: 20px; position: relative; }
        .btn-login { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1><i class="bi bi-lightning-fill"></i> FastBetNow</h1>
        </div>
        <?php if ($error): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Accedi</button>
        </form>
        <div class="text-center mt-4">
            <p class="text-muted">Non hai un account? <a href="register.php" class="text-decoration-none fw-bold">Registrati</a></p>
        </div>
    </div>
</body>
</html>
<?php } ?>