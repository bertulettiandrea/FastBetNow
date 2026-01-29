<?php
if ($user) {
    $queryP = "SELECT P.codice, P.descrizione FROM UTENTE_RUOLO UR 
               JOIN RUOLO_PERMESSO RP ON UR.id_ruolo = RP.id_ruolo
               JOIN PERMESSO P ON RP.id_permesso = P.id
               WHERE UR.email_utente = ?";

    $payload = [
        'iat'  => time(),
        'exp'  => time() + ACCESS_TOKEN_EXPIRATION,
        'sub'  => $user['email'],
        'role' => $ruolo_nome, 
        'permissions' => $permessi,
        'perm_count' => count($permessi)
    ];

    $newAccessToken = JWT::encode($payload, JWT_SECRET, 'HS256');
}