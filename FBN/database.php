<?php
// Configurazione database con fallback per ambienti diversi
$host = getenv('DB_HOST') ?: '127.0.0.1';
$db = getenv('DB_NAME') ?: 'FBN';
$user = getenv('DB_USER') ?: 'bertu';
$pass = getenv('DB_PASS') ?: 'bertu';
$charset = 'utf8mb4';

$pdo = null;
$db_error = null;

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
            $db_error = htmlspecialchars($e2->getMessage());
            error_log("Database connection failed: " . $e2->getMessage());
        }
    } else {
        $db_error = htmlspecialchars($e->getMessage());
        error_log("Database connection failed: " . $e->getMessage());
    }
}

// Funzione helper per controllare connessione DB
function isDatabaseConnected() {
    global $pdo;
    return $pdo !== null;
}
?>