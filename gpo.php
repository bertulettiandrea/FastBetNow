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
    <title>GPO - 5IE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h1>GPO - Attività</h1>
    <ul>
        <li><a href="gpo/chart/index.php">Esercizio 1 - chart</a></li>
        <li><a href="gpo/diagrammi/index.php">Esercizio 2 - Diagramma dei casi d'uso</a></li>
        <li><a href="gpo/diagrammi/index2.php">Esercizio 3 - Diagramma delle classi</a></li>
        <li><a href="gpo/GANT/index.php">Esercizio 4 - Diagramma di GANTT</a></li>
        <li><a href="gpo/ER/index.php">Esercizio 5 - Diagramma ER</a></li>
        <!-- Aggiungerai qui altri link durante l’anno -->
    </ul>
    <a href="index.php" class="btn btn-secondary mt-4">Torna alla Home</a>
</div>
</body>
</html>