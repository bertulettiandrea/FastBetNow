<?php
// Configurazione database con fallback per ambienti diversi
$host = getenv('DB_HOST') ?: '127.0.0.1';
$db = getenv('DB_NAME') ?: 'FBN';
$user = getenv('DB_USER') ?: 'bertu';
$pass = getenv('DB_PASS') ?: 'bertu';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Se fallisce con 127.0.0.1, prova localhost
    if (strpos($e->getMessage(), '127.0.0.1') !== false && $host === '127.0.0.1') {
        try {
            $dsn = "mysql:host=localhost;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e2) {
            http_response_code(500);
            die("Errore di connessione al database: " . htmlspecialchars($e2->getMessage()));
        }
    } else {
        http_response_code(500);
        die("Errore di connessione al database: " . htmlspecialchars($e->getMessage()));
    }
}
?>