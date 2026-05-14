<?php

function getPartiteCatalog(PDO $pdo = null): array
{
    // Se PDO non è fornito, ritorna array vuoto
    // (in caso di errore di connessione)
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("
            SELECT 
                id_partita,
                squadra_casa,
                squadra_trasferta,
                data_inizio,
                campionato,
                quota_casa,
                quota_pareggio,
                quota_trasferta,
                stato,
                risultato
            FROM PARTITA
            WHERE stato = 'APERTO'
            ORDER BY data_inizio ASC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        error_log("Errore nel recupero partite: " . $e->getMessage());
        return [];
    }
}

function getQuotaBySegno(array $partita, string $segno): float
{
    if ($segno === '1') {
        return (float) $partita['quota_casa'];
    }

    if ($segno === 'X') {
        return (float) $partita['quota_pareggio'];
    }

    if ($segno === '2') {
        return (float) $partita['quota_trasferta'];
    }

    throw new InvalidArgumentException('Segno non valido');
}
