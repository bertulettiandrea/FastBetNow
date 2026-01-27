<?php 

session_start();

//import database (creates $mysqli variable)
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
    
    //check if email and password are set
    if(isset($data->email) && isset($data->password)){
        $email = $data->email;
        $password = $data->password;    
        try {
            //prepare the sql statement
            $stmt = $mysqli->prepare("SELECT email, password, nome FROM UTENTE WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row && password_verify($password, $row['password'])) {
                header('Content-Type: application/json');
                echo json_encode(["status" => "success", "user" => $row['nome'], "email" => $row['email']]);
                exit;
            } else {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Credenziali errate"]);
                exit;
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
        echo json_encode(["status" => "error", "message" => "Email e password obbligatori"]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // HTML Form submission
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $mysqli->prepare("SELECT email, password, nome FROM UTENTE WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row && password_verify($password, $row['password'])) {
                $success = 'Login effettuato! Reindirizzamento...';
                // Set session and redirect
                $_SESSION['user_email'] = $email;
                $_SESSION['user_nome'] = $row['nome'];
                header('Location: dashboard.php', true, 303);
                exit();
            } else {
                $error = 'Email o password non validi';
            }
        } catch (Exception $e) {
            $error = 'Errore nel database: ' . $e->getMessage();
        }
    } else {
        $error = 'Email e password sono obbligatori';
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
    <title>Login - FastBetNow</title>
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
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-header h1 {
            color: #667eea;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #999;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
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
        
        .btn-login {
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
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .checkbox-group input {
            margin-right: 8px;
            width: auto;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="bi bi-lightning-fill"></i> FastBetNow</h1>
                <p>Accedi al tuo account</p>
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
            
            <form method="POST" action="login.php" id="loginForm">
                <input type="hidden" name="login" value="1">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Inserisci la tua email"
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
                        placeholder="Inserisci la tua password"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Ricordami</label>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Accedi
                </button>
            </form>
            
            <div class="login-footer">
                Non hai un account? <a href="register.php">Registrati qui</a><br>
                <a href="forgot-password.php">Password dimenticata?</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Per favore compila tutti i campi');
            }
        });
        
        // Auto-redirect on success
        <?php if (!empty($success)): ?>
            setTimeout(function() {
                window.location.href = 'dashboard.php';
            }, 1500);
        <?php endif; ?>
    </script>
</body>
</html>
    <?php
}