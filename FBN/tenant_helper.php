<?php
/**
 * Tenant Helper - Gestione multi-tenant
 * Fornisce funzioni per gestire i tenant nel sistema
 */

require_once 'database.php';

/**
 * Ottiene il tenant ID dall'utente autenticato
 * @return int|null Tenant ID
 */
function getTenantIdFromUser() {
    $userData = getUserDataFromSession() ?? getUserDataFromRequestJWT();
    
    if (!$userData) {
        return null;
    }
    
    return $userData['tenant_id'] ?? null;
}

/**
 * Ottiene il tenant ID dalla richiesta (header, GET, POST)
 * @return int|null Tenant ID
 */
function getTenantIdFromRequest() {
    // Prova prima dal header X-Tenant-ID
    if (!empty($_SERVER['HTTP_X_TENANT_ID'])) {
        return (int)$_SERVER['HTTP_X_TENANT_ID'];
    }
    
    // Prova da GET
    if (!empty($_GET['tenant_id'])) {
        return (int)$_GET['tenant_id'];
    }
    
    // Prova da POST
    if (!empty($_POST['tenant_id'])) {
        return (int)$_POST['tenant_id'];
    }
    
    // Altrimenti dall'utente autenticato
    return getTenantIdFromUser();
}

/**
 * Valida che l'utente abbia accesso al tenant specificato
 * @param int $tenantId Tenant ID da verificare
 * @return bool True se autorizzato, false altrimenti
 */
function validateTenantAccess($tenantId) {
    $userTenantId = getTenantIdFromUser();
    
    if ($userTenantId === null) {
        return false;
    }
    
    return (int)$userTenantId === (int)$tenantId;
}

/**
 * Ottiene informazioni del tenant
 * @param int $tenantId Tenant ID
 * @return array|null Dati tenant
 */
function getTenantInfo($tenantId) {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT * FROM TENANT WHERE id = ? AND attivo = 1');
        $stmt->execute([(int)$tenantId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error fetching tenant info: " . $e->getMessage());
        return null;
    }
}

/**
 * Ottiene il tenant ID dell'utente dal database
 * @param string $email Email utente
 * @return int|null Tenant ID
 */
function getUserTenantId($email) {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT tenant_id FROM UTENTE WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ? (int)$result['tenant_id'] : null;
    } catch (Exception $e) {
        error_log("Error fetching user tenant: " . $e->getMessage());
        return null;
    }
}

/**
 * Costruisce una clausola WHERE per filtrare per tenant
 * @param string $tableAlias Alias della tabella (opzionale)
 * @return string Clausola WHERE
 */
function getTenantWhereClause($tableAlias = '') {
    $prefix = $tableAlias ? $tableAlias . '.' : '';
    $tenantId = getTenantIdFromRequest();
    
    if ($tenantId === null) {
        $tenantId = 1; // Default a tenant 1
    }
    
    return $prefix . 'tenant_id = ' . (int)$tenantId;
}

/**
 * Aggiunge il tenant_id ai dati da inserire/aggiornare
 * @param array $data Dati
 * @param int|null $tenantId Tenant ID (opzionale, usa quello corrente)
 * @return array Dati con tenant_id
 */
function addTenantIdToData($data, $tenantId = null) {
    if ($tenantId === null) {
        $tenantId = getTenantIdFromRequest();
    }
    
    if ($tenantId === null) {
        $tenantId = 1;
    }
    
    $data['tenant_id'] = (int)$tenantId;
    return $data;
}

/**
 * Verifica se l'utente appartiene al tenant
 * @param string $email Email utente
 * @param int $tenantId Tenant ID
 * @return bool True se appartiene, false altrimenti
 */
function userBelongsToTenant($email, $tenantId) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM UTENTE WHERE email = ? AND tenant_id = ?');
        $stmt->execute([$email, (int)$tenantId]);
        $result = $stmt->fetch();
        return $result && (int)$result['cnt'] > 0;
    } catch (Exception $e) {
        error_log("Error checking user tenant: " . $e->getMessage());
        return false;
    }
}

/**
 * Middleware per verificare l'accesso al tenant
 * Termina lo script con errore 403 se non autorizzato
 */
function requireTenantAccess() {
    $userTenantId = getTenantIdFromUser();
    
    if ($userTenantId === null) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Unauthorized']);
        exit(1);
    }
    
    $requestTenantId = getTenantIdFromRequest();
    
    if ($requestTenantId !== null && (int)$userTenantId !== (int)$requestTenantId) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Forbidden']);
        exit(1);
    }
}

?>
