<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>TEP - 5IE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h1>TEP - Attività</h1>
    <ul>
        <li><a href="consume.html">Esercizio 1 - consumo</a></li>
        <!-- Aggiungerai qui altri link durante l’anno -->
    </ul>
    <a href="index.php" class="btn btn-secondary mt-4">Torna alla Home</a>
</div>
</body>
</html>
