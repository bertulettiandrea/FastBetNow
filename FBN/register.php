<?php 
session_start();
include_once 'database.php';

global $mysqli;

$error = '';
$success = '';
$isJsonRequest = false;

// 1. GESTIONE REGISTRAZIONE API (JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['HTTP_CONTENT_TYPE']) && 
    strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
    
    $isJsonRequest = true;
    $data = json_decode(file_get_contents("php://input"));
    
    if(isset($data->email, $data->password, $data->nome, $data->cognome)){
        try {
            $mysqli->begin_transaction();
            
            // Verifica duplicato
            $stmt = $mysqli->prepare("SELECT email FROM UTENTE WHERE email = ?");
            $stmt->bind_param("s", $data->email);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception("Email già occupata");
            }

            $hashed = password_hash($data->password, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("INSERT INTO UTENTE (email, password, nome, cognome, refresh_token) VALUES (?, ?, ?, ?, NULL)");
            $stmt->bind_param("ssss", $data->email, $hashed, $data->nome, $data->cognome);
            $stmt->execute();

            $mysqli->query("INSERT INTO UTENTE_RUOLO (email_utente, id_ruolo) VALUES ('$data->email', 2)");
            $mysqli->query("INSERT INTO CONTO (email_intestatario, saldo, bonus) VALUES ('$data->email', 0, 0)");

            $mysqli->commit();
            header('Content-Type: application/json');
            echo json_encode(["status" => "success", "message" => "Utente creato"]);
            exit;
        } catch (Exception $e) {
            $mysqli->rollback();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit;
        }
    }
}

// 2. GESTIONE REGISTRAZIONE HTML
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');

    if ($email && $password && $nome && $cognome) {
        try {
            $mysqli->begin_transaction();
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $mysqli->prepare("INSERT INTO UTENTE (email, password, nome, cognome, refresh_token) VALUES (?, ?, ?, ?, NULL)");
            $stmt->bind_param("ssss", $email, $hashed, $nome, $cognome);
            
            if ($stmt->execute()) {
                $mysqli->query("INSERT INTO UTENTE_RUOLO (email_utente, id_ruolo) VALUES ('$email', 2)");
                $mysqli->query("INSERT INTO CONTO (email_intestatario, saldo, bonus) VALUES ('$email', 0, 0)");
                $mysqli->commit();
                $success = 'Registrazione ok! Ora puoi accedere.';
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = "Errore: " . $e->getMessage();
        }
    }
}

if (!$isJsonRequest) {
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione - FastBetNow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .register-card { background: white; border-radius: 15px; padding: 40px; width: 100%; max-width: 450px; }
    </style>
</head>
<body>
    <div class="register-card">
        <h2 class="text-center mb-4">Crea Account</h2>
        <?php if ($error): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
        <?php if ($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="register" value="1">
            <div class="row">
                <div class="col-6 mb-3"><label>Nome</label><input type="text" name="nome" class="form-control" required></div>
                <div class="col-6 mb-3"><label>Cognome</label><input type="text" name="cognome" class="form-control" required></div>
            </div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
            <button type="submit" class="btn btn-primary w-100">Registrati</button>
        </form>
        <div class="text-center mt-4">
            <p class="text-muted">Hai già un account? <a href="login.php" class="text-decoration-none fw-bold">Accedi</a></p>
        </div>
    </div>
</body>
</html>
<?php } ?>