<?php 

session_start();


include_once 'database.php';

global $mysqli;

//include firebase jwt
require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$error = '';
$success = '';
$isJsonRequest = false;

//check if it's a JSON API request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['HTTP_CONTENT_TYPE']) && 
    strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
    
    $isJsonRequest = true;
    $data = json_decode(file_get_contents("php://input"));
    
    //check if email, password, nome, cognome are set
    if(isset($data->email) && isset($data->password) && isset($data->nome) && isset($data->cognome)){
        $email = $data->email;
        $password = $data->password;
        $nome = $data->nome;
        $cognome = $data->cognome;
        
        try {
            // Check if user exists
            $stmt = $mysqli->prepare("SELECT email FROM UTENTE WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->fetch_assoc()) {
                header('Content-Type: application/json');
                http_response_code(409);
                echo json_encode(["status" => "error", "message" => "Email già registrata"]);
                exit;
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user
                $stmt = $mysqli->prepare("INSERT INTO UTENTE (email, password, nome, cognome, confermato) VALUES (?, ?, ?, ?, 0)");
                $stmt->bind_param("ssss", $email, $hashed_password, $nome, $cognome);
                
                if ($stmt->execute()) {
                    header('Content-Type: application/json');
                    echo json_encode(["status" => "success", "message" => "Registrazione completata! Accedi per continuare."]);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode(["status" => "error", "message" => "Errore nella registrazione"]);
                    exit;
                }
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Errore nel database: " . $e->getMessage()]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Tutti i campi sono obbligatori"]);
        exit;
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // HTML Form submission
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : '';
    
    if (!empty($email) && !empty($password) && !empty($password_confirm) && !empty($nome) && !empty($cognome)) {
        if ($password !== $password_confirm) {
            $error = 'Le password non coincidono';
        } elseif (strlen($password) < 6) {
            $error = 'La password deve essere almeno 6 caratteri';
        } else {
            try {
                // Check if user exists
                $stmt = $mysqli->prepare("SELECT email FROM UTENTE WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->fetch_assoc()) {
                    $error = 'Email già registrata';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Insert new user
                    $stmt = $mysqli->prepare("INSERT INTO UTENTE (email, password, nome, cognome, confermato) VALUES (?, ?, ?, ?, 0)");
                    $stmt->bind_param("ssss", $email, $hashed_password, $nome, $cognome);
                    
                    if ($stmt->execute()) {
                        $success = 'Registrazione completata! Reindirizzamento al login...';
                        header('Refresh: 2; url=login.php');
                    } else {
                        $error = 'Errore nella registrazione';
                    }
                }
            } catch (Exception $e) {
                $error = 'Errore nel database: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Tutti i campi sono obbligatori';
    }
}

// If not JSON request, display HTML form
if (!$isJsonRequest) {
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - FastBetNow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            backdrop-filter: blur(10px);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .register-header h1 {
            color: #667eea;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: #999;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 40px;
            cursor: pointer;
            color: #667eea;
            border: none;
            background: none;
            padding: 5px;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: none;
            padding: 12px 15px;
        }
        
        .alert-danger {
            background-color: #fee;
            color: #c33;
        }
        
        .alert-success {
            background-color: #efe;
            color: #3c3;
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        
        .register-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            font-size: 12px;
            margin-top: 5px;
            padding: 8px;
            border-radius: 4px;
            display: none;
        }
        
        .password-strength.weak {
            background-color: #ffebee;
            color: #c33;
            display: block;
        }
        
        .password-strength.medium {
            background-color: #fff3e0;
            color: #f57c00;
            display: block;
        }
        
        .password-strength.strong {
            background-color: #e8f5e9;
            color: #2e7d32;
            display: block;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1><i class="bi bi-lightning-fill"></i> FastBetNow</h1>
                <p>Crea il tuo account</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" id="registerForm">
                <input type="hidden" name="register" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            placeholder="Il tuo nome"
                            value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="cognome">Cognome</label>
                        <input 
                            type="text" 
                            id="cognome" 
                            name="cognome" 
                            placeholder="Il tuo cognome"
                            value="<?php echo isset($_POST['cognome']) ? htmlspecialchars($_POST['cognome']) : ''; ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="La tua email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Almeno 6 caratteri"
                        required
                        onkeyup="checkPasswordStrength()"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('password', 'toggleIcon1')">
                        <i class="bi bi-eye" id="toggleIcon1"></i>
                    </button>
                    <div id="strengthIndicator" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Conferma Password</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        placeholder="Ripeti la password"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('password_confirm', 'toggleIcon2')">
                        <i class="bi bi-eye" id="toggleIcon2"></i>
                    </button>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="bi bi-person-plus"></i> Registrati
                </button>
            </form>
            
            <div class="register-footer">
                Hai già un account? <a href="login.php">Accedi qui</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId, iconId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthIndicator = document.getElementById('strengthIndicator');
            
            if (password.length === 0) {
                strengthIndicator.classList.remove('weak', 'medium', 'strong');
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            strengthIndicator.classList.remove('weak', 'medium', 'strong');
            
            if (strength < 2) {
                strengthIndicator.classList.add('weak');
                strengthIndicator.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Password debole';
            } else if (strength < 4) {
                strengthIndicator.classList.add('medium');
                strengthIndicator.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Password media';
            } else {
                strengthIndicator.classList.add('strong');
                strengthIndicator.innerHTML = '<i class="bi bi-check-circle"></i> Password forte';
            }
        }
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const nome = document.getElementById('nome').value.trim();
            const cognome = document.getElementById('cognome').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (!nome || !cognome || !email || !password || !passwordConfirm) {
                e.preventDefault();
                alert('Per favore compila tutti i campi');
                return;
            }
            
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Le password non coincidono');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('La password deve essere almeno 6 caratteri');
                return;
            }
        });
    </script>
</body>
</html>
    <?php
}
?>
