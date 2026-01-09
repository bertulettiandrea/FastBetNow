<?php
if (isset($_POST['username'])) {
    $_SESSION['username'] = $_POST['username'];
    header('Location: informatica.php'); // Reindirizza alla pagina Informatica dopo il login
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Informatica - 5IE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h1>Informatica - Attività</h1>
    <ul>
        <li><a href="informatica/Esercizio1/index.php">Esercizio 1 - Articoli</a></li>
        <li><a href="informatica/Esercizio2/index.php">Esercizio 2 - Login</a></li>
        <!-- Aggiungerai qui altri link durante l’anno -->
    </ul>
    <a href="index.php" class="btn btn-secondary mt-4">Torna alla Home</a>
</div>
</body>
</html>