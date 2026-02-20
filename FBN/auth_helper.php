<?php
require_once 'vendor/autoload.php';
require_once 'JWT/config.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function decodeJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return null;
    }
}

function getUserDataFromSession() {
    if (!isset($_SESSION['access_token'])) {
        return null;
    }
    
    $decoded = decodeJWT($_SESSION['access_token']);
    
    if (!$decoded) {
        return null;
    }

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

function isAdmin() {
    $userData = getUserDataFromSession();
    return $userData && $userData['ruolo'] === 'ADMIN';
}

function hasPermission($permissionCode) {
    $userData = getUserDataFromSession();
    return $userData && in_array($permissionCode, $userData['permessi']);
}

function getUserPermissions() {
    $userData = getUserDataFromSession();
    return $userData ? $userData['permessi'] : [];
}
?>