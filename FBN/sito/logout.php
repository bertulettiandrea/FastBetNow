<?php
session_start();

// Distruggi tutte le variabili di sessione
$_SESSION = array();

// Distruggi il cookie di sessione
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distruggi la sessione
session_destroy();

// Redirect al login
header('Location: login.php');
exit();
?>