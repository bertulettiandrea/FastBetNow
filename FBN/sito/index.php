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
    // Query corretta basata sul tuo dump SQL: la colonna si chiama 'nome'
    $query = "SELECT R.nome 
              FROM UTENTE_RUOLO UR 
              JOIN RUOLO R ON UR.id_ruolo = R.id 
              WHERE UR.email_utente = ?";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $ruolo_nome = $row['nome']; // 'ADMIN' o 'UTENTE'
    }
} catch (Exception $e) {
    $ruolo_nome = "Errore: " . $e->getMessage();
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
    <div class="card p-4 text-center">
        <h1>Ciao, <?php echo htmlspecialchars($nome_utente); ?>!</h1>
        <p class="text-muted"><?php echo htmlspecialchars($email); ?></p>
        <hr>
        <div class="py-3">
            <span class="text-secondary d-block mb-2">IL TUO RUOLO:</span>
            <span class="badge <?php echo ($ruolo_nome == 'ADMIN') ? 'bg-danger' : 'bg-success'; ?> p-2 px-4">
                <?php echo $ruolo_nome; ?>
            </span>
        </div>
    </div>
</div>

</body>
</html>