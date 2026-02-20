<?php
$host = '127.0.0.1';
$db = 'FBN';
$user = 'bertu';
$pass = 'bertu';
$charset = 'utf8mb4';
    
$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Errore di connessione: " . $mysqli->connect_error);
}

$mysqli->set_charset($charset);
?>