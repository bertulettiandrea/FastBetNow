<?php
require_once 'vendor/autoload.php';
require_once 'JWT/config.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Funzione per decodificare e validare il JWT
function decodeJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return null;
    }
}

// Funzione per ottenere i dati utente dal JWT salvato in sessione
function getUserDataFromSession() {
    if (!isset($_SESSION['access_token'])) {
        return null;
    }
    
    $decoded = decodeJWT($_SESSION['access_token']);
    
    if (!$decoded) {
        return null;
    }
    
    // Verifica se il token è scaduto
    if (isset($decoded['exp']) && $decoded['exp'] < time()) {
        return null;
    }
    
    return [
        'email' => $decoded['sub'] ?? '',
        'nome' => $decoded['nome'] ?? '',
        'ruolo' => $decoded['ruolo'] ?? 'UTENTE',
        'permessi' => $decoded['permessi'] ?? []
    ];
}

// Funzione per verificare se l'utente è admin
function isAdmin() {
    $userData = getUserDataFromSession();
    return $userData && $userData['ruolo'] === 'ADMIN';
}

// Funzione per verificare se l'utente ha un permesso specifico
function hasPermission($permissionCode) {
    $userData = getUserDataFromSession();
    return $userData && in_array($permissionCode, $userData['permessi']);
}

// Funzione per ottenere tutti i permessi dell'utente
function getUserPermissions() {
    $userData = getUserDataFromSession();
    return $userData ? $userData['permessi'] : [];
}
?>