<?php
require_once 'vendor/autoload.php';
require_once 'JWT/config.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function normalizeUserDataFromDecoded(array $decoded) {
    $ruolo = $decoded['ruolo'] ?? ($decoded['role'] ?? 'UTENTE');

    $permessiRaw = $decoded['permessi'] ?? ($decoded['permissions'] ?? []);
    $permessi = [];

    if (is_array($permessiRaw)) {
        foreach ($permessiRaw as $permesso) {
            if (is_string($permesso)) {
                $permessi[] = $permesso;
            } elseif (is_array($permesso)) {
                if (isset($permesso['cod'])) {
                    $permessi[] = $permesso['cod'];
                } elseif (isset($permesso['codice'])) {
                    $permessi[] = $permesso['codice'];
                }
            } elseif (is_object($permesso)) {
                if (isset($permesso->cod)) {
                    $permessi[] = $permesso->cod;
                } elseif (isset($permesso->codice)) {
                    $permessi[] = $permesso->codice;
                }
            }
        }
    }

    return [
        'email' => $decoded['sub'] ?? '',
        'nome' => $decoded['nome'] ?? '',
        'ruolo' => $ruolo,
        'permessi' => $permessi
    ];
}

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
    
    return normalizeUserDataFromDecoded($decoded);
}

function getJWTFromRequest() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!$authHeader && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!$authHeader && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }

    return $_GET['access_token'] ?? $_GET['token'] ?? null;
}

function getUserDataFromRequestJWT() {
    $jwt = getJWTFromRequest();
    if (!$jwt) {
        return null;
    }

    $decoded = decodeJWT($jwt);
    if (!$decoded) {
        return null;
    }

    if (isset($decoded['exp']) && $decoded['exp'] < time()) {
        return null;
    }

    return normalizeUserDataFromDecoded($decoded);
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

function verifyJWT($token) {
    if (!$token) {
        return false;
    }
    $decoded = decodeJWT($token);
    if (!$decoded) {
        return false;
    }
    if (isset($decoded['exp']) && $decoded['exp'] < time()) {
        return false;
    }
    return true;
}

function hasPermissionJWT($pdo, $email, $permissionCode) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM UTENTE_RUOLO ur
        JOIN RUOLO_PERMESSO rp ON ur.id_ruolo = rp.id_ruolo
        JOIN PERMESSO p ON rp.id_permesso = p.id
        WHERE ur.email_utente = ? AND p.codice = ?
    ");
    $stmt->execute([$email, $permissionCode]);
    return $stmt->fetch() !== false;
}

function getBearerToken() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!$authHeader && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!$authHeader && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        return $matches[1];
    }

    return null;
}
?>