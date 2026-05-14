<?php
session_start();

include_once '../auth_helper.php';
include_once '../database.php';
require_once '../services/BetService.php';

function redirectToHome(string $status, string $message): void
{
    $target = 'index.php?bet_status=' . urlencode($status) . '&bet_message=' . urlencode($message);
    header('Location: ' . $target, true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}

$userData = getUserDataFromSession();
if (!$userData) {
    header('Location: ../login.php', true, 303);
    exit;
}

if (!in_array('PUNTA_SCHEDINA', $userData['permessi'] ?? [], true)) {
    redirectToHome('error', 'Permesso mancante per puntare una schedina');
}

$importoRaw = $_POST['importo'] ?? null;
$importo = is_numeric($importoRaw) ? (float) $importoRaw : 0.0;

$selezioniInput = $_POST['selezioni_json'] ?? '';
$selezioni = [];

global $pdo;

// Fallback: se PHP non ha popolato $_POST (es. body raw), proviamo a leggere il body grezzo
if (trim($selezioniInput) === '') {
    $raw = file_get_contents('php://input');
    if ($raw) {
        // Se il body è urlencoded, cerchiamo il parametro selezioni_json
        parse_str($raw, $parsed);
        if (isset($parsed['selezioni_json'])) {
            $selezioniInput = $parsed['selezioni_json'];
        } else {
            // Se il body sembra JSON puro, proviamo a decodificare
            $trim = trim($raw);
            if (strpos($trim, '{') === 0 || strpos($trim, '[') === 0) {
                // cerchiamo un campo selezioni_json all'interno del JSON
                $maybe = json_decode($trim, true);
                if (is_array($maybe) && isset($maybe['selezioni_json'])) {
                    $selezioniInput = is_string($maybe['selezioni_json']) ? $maybe['selezioni_json'] : json_encode($maybe['selezioni_json']);
                }
            }
        }
    }
}

if (is_string($selezioniInput) && trim($selezioniInput) !== '') {
    $decoded = json_decode($selezioniInput, true);
    if (!is_array($decoded)) {
        redirectToHome('error', 'Formato schedina non valido');
    }

    $seenPartiteIds = [];
    foreach ($decoded as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $idPartita = filter_var($entry['id_partita'] ?? null, FILTER_VALIDATE_INT);
        $segno = (string) ($entry['segno'] ?? '');

        if ($idPartita === false || $idPartita === null) {
            redirectToHome('error', 'Partita non valida nella schedina');
        }

        if (!in_array($segno, ['1', 'X', '2'], true)) {
            redirectToHome('error', 'Segno non valido nella schedina');
        }

        if (isset($seenPartiteIds[$idPartita])) {
            redirectToHome('error', 'Hai selezionato la stessa partita più volte');
        }

        $seenPartiteIds[$idPartita] = true;
        $selezioni[] = [
            'id_partita' => $idPartita,
            'segno' => $segno,
        ];
    }
}

if (empty($selezioni)) {
    // Log per debug: cosa è stato inviato
    $log = date('c') . " - Selezioni vuote. ";
    $log .= "POST_keys=" . json_encode(array_keys($_POST)) . "; ";
    $raw = file_get_contents('php://input');
    $log .= "raw_body=" . substr($raw,0,1000) . "\n";
    @file_put_contents('/tmp/puntata_debug.log', $log, FILE_APPEND);

    redirectToHome('error', 'Aggiungi almeno un evento alla schedina');
}

if ($importo <= 0) {
    redirectToHome('error', 'Inserisci un importo valido');
}

// Recupera le partite dal database e valida le selezioni
$stmtPartite = $pdo->prepare("SELECT id_partita, squadra_casa, squadra_trasferta, quota_casa, quota_pareggio, quota_trasferta FROM PARTITA WHERE id_partita = ?");
$partiteMap = [];
$selezioniValidate = [];

foreach ($selezioni as $selezione) {
    $stmtPartite->execute([$selezione['id_partita']]);
    $partita = $stmtPartite->fetch();

    if (!$partita) {
        redirectToHome('error', 'Partita #' . $selezione['id_partita'] . ' non trovata');
    }

    $partiteMap[$selezione['id_partita']] = $partita;
    $selezioniValidate[] = $selezione;
}

$selezioni = $selezioniValidate;

// Costruisci le selezioni per il servizio
$betSelections = [];
$quotaTotale = 1.0;

foreach ($selezioni as $selezione) {
    $partita = $partiteMap[$selezione['id_partita']];
    $segno = $selezione['segno'];

    // Determina la quota in base al segno
    if ($segno === '1') {
        $quota = (float) $partita['quota_casa'];
    } elseif ($segno === 'X') {
        $quota = (float) $partita['quota_pareggio'];
    } elseif ($segno === '2') {
        $quota = (float) $partita['quota_trasferta'];
    } else {
        redirectToHome('error', 'Segno non valido');
    }

    $betSelections[] = [
        'id_partita' => (int) $partita['id_partita'],
        'squadra_casa' => $partita['squadra_casa'],
        'squadra_trasferta' => $partita['squadra_trasferta'],
        'segno' => $segno,
        'quota' => round($quota, 2),
    ];

    $quotaTotale *= $quota;
}

$quotaTotale = round($quotaTotale, 2);

try {
    $result = placeSchedinaMultiplaBet(
        $pdo,
        $userData['email'],
        $importo,
        $betSelections
    );

    $message = sprintf(
        'Schedina #%d piazzata con %d eventi (quota totale %.2f): saldo attuale %.2f EUR',
        $result['schedina_id'],
        $result['numero_eventi'],
        $quotaTotale,
        $result['saldo_attuale']
    );
    redirectToHome('success', $message);
} catch (InvalidArgumentException | RuntimeException $e) {
    redirectToHome('error', $e->getMessage());
} catch (Throwable $e) {
    redirectToHome('error', 'Errore durante la puntata, riprova');
}
