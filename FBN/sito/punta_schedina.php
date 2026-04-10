<?php
session_start();

include_once '../auth_helper.php';
include_once '../database.php';
require_once '../services/BetService.php';
require_once 'matches.php';

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

$partite = getPartiteCatalog();

if (is_string($selezioniInput) && trim($selezioniInput) !== '') {
    $decoded = json_decode($selezioniInput, true);
    if (!is_array($decoded)) {
        redirectToHome('error', 'Formato schedina non valido');
    }

    $seenMatchIndexes = [];
    foreach ($decoded as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $matchIndex = filter_var($entry['match_index'] ?? null, FILTER_VALIDATE_INT);
        $segno = (string) ($entry['segno'] ?? '');

        if ($matchIndex === false || $matchIndex === null || !isset($partite[$matchIndex])) {
            redirectToHome('error', 'Partita non valida nella schedina');
        }

        if (!in_array($segno, ['1', 'X', '2'], true)) {
            redirectToHome('error', 'Segno non valido nella schedina');
        }

        if (isset($seenMatchIndexes[$matchIndex])) {
            redirectToHome('error', 'Hai selezionato la stessa partita piu volte');
        }

        $seenMatchIndexes[$matchIndex] = true;
        $selezioni[] = [
            'match_index' => $matchIndex,
            'segno' => $segno,
        ];
    }
}

if (empty($selezioni)) {
    $matchIndex = filter_input(INPUT_POST, 'match_index', FILTER_VALIDATE_INT);
    $segno = $_POST['segno'] ?? '';

    if ($matchIndex === null || $matchIndex === false || !isset($partite[$matchIndex])) {
        redirectToHome('error', 'Partita non valida');
    }

    if (!in_array($segno, ['1', 'X', '2'], true)) {
        redirectToHome('error', 'Segno non valido');
    }

    $selezioni[] = [
        'match_index' => $matchIndex,
        'segno' => $segno,
    ];
}

if (empty($selezioni)) {
    redirectToHome('error', 'Aggiungi almeno un evento alla schedina');
}

if ($importo <= 0) {
    redirectToHome('error', 'Inserisci un importo valido');
}

$betSelections = [];
$quotaTotale = 1.0;
foreach ($selezioni as $selezione) {
    $partita = $partite[$selezione['match_index']];
    $evento = $partita['squadra_casa'] . ' - ' . $partita['squadra_trasferta'];
    $quota = getQuotaBySegno($partita, $selezione['segno']);

    $betSelections[] = [
        'evento' => $evento,
        'segno' => $selezione['segno'],
        'quota' => $quota,
    ];

    $quotaTotale *= $quota;
}

$quotaTotale = round($quotaTotale, 2);

global $pdo;

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
