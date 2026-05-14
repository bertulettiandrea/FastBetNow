<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../auth_helper.php';
include_once '../database.php';
require_once 'matches.php';

// Check database connectivity
if (!isDatabaseConnected()) {
    http_response_code(503);
    die("Servizio temporaneamente non disponibile. Prova più tardi.");
}

$userData = getUserDataFromSession();
$isLoggedIn = ($userData !== null);
$userEmail = $userData['email'] ?? '';
$userName = $userData['nome'] ?? '';
$userPermissions = $userData['permessi'] ?? [];
$canPuntaSchedina = $isLoggedIn && in_array('PUNTA_SCHEDINA', $userPermissions, true);

// Redireziona admin al dashboard
if ($isLoggedIn && isset($_SESSION['user_ruolo']) && $_SESSION['user_ruolo'] === 'ADMIN') {
    header('Location: admin/dashboard.php');
    exit();
}

$userSaldo = null;
if ($isLoggedIn) {
    global $pdo;
    $stmtSaldo = $pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ?');
    $stmtSaldo->execute([$userEmail]);
    $saldoFromDb = $stmtSaldo->fetchColumn();
    $userSaldo = $saldoFromDb !== false ? (float) $saldoFromDb : 0.0;
}

$betStatus = $_GET['bet_status'] ?? '';
$betMessage = trim($_GET['bet_message'] ?? '');

global $pdo;
$partite = getPartiteCatalog($pdo);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastBetNow - Scommesse Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #1a1a2e;
            color: #ccc;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .navbar-custom {
            background: #16213e;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
            border-bottom: 3px solid #00d4ff;
        }

        .navbar-brand {
            color: #00d4ff !important;
            font-weight: 700;
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .container-main {
            display: flex;
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .matches-section {
            flex: 1;
            min-width: 0;
        }

        .schedina-section {
            width: 380px;
        }

        @media (max-width: 768px) {
            .container-main {
                flex-direction: column;
            }
            .schedina-section {
                width: 100%;
            }
        }

        .match-item {
            background: #0f3460;
            border: 1px solid #00d4ff;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            transition: all 0.3s ease;
        }

        .match-item:hover {
            background: #1a4d7f;
            box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
        }

        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 212, 255, 0.3);
        }

        .championship {
            font-size: 0.8rem;
            color: #00d4ff;
            font-weight: 600;
            text-transform: uppercase;
        }

        .match-time {
            font-size: 0.9rem;
            color: #888;
        }

        .teams {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            gap: 10px;
        }

        .team-name {
            flex: 1;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .vs {
            color: #888;
            font-size: 0.8rem;
            min-width: 30px;
            text-align: center;
        }

        .odds-container {
            display: flex;
            gap: 8px;
        }

        .odd-btn {
            flex: 1;
            background: rgba(0, 212, 255, 0.1);
            border: 2px solid #00d4ff;
            color: #00d4ff;
            padding: 10px 8px;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .odd-btn:hover {
            background: #00d4ff;
            color: #1a1a2e;
            transform: scale(1.05);
        }

        .odd-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .odd-label {
            font-size: 0.75rem;
            opacity: 0.8;
            display: block;
            margin-bottom: 3px;
        }

        .odd-value {
            font-size: 1.2rem;
            display: block;
        }

        /* SCHEDINA LATERALE */
        .schedina-sticky {
            position: sticky;
            top: 20px;
            background: #0f3460;
            border: 2px solid #00d4ff;
            border-radius: 8px;
            padding: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .schedina-title {
            color: #00d4ff;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            padding-bottom: 10px;
            border-bottom: 2px solid #00d4ff;
        }

        .schedina-selection {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid #00d4ff;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }

        .selection-event {
            flex: 1;
            color: #ddd;
        }

        .selection-sign-quota {
            display: flex;
            gap: 8px;
            align-items: center;
            color: #00d4ff;
            font-weight: 600;
        }

        .remove-selection {
            background: #dc3545;
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 0;
            margin-left: 8px;
            transition: all 0.2s ease;
        }

        .remove-selection:hover {
            background: #ff0000;
            transform: scale(1.1);
        }

        .empty-schedina {
            text-align: center;
            color: #666;
            padding: 30px 10px;
        }

        .empty-schedina i {
            font-size: 3rem;
            color: #444;
            display: block;
            margin-bottom: 10px;
        }

        .schedina-stats {
            background: rgba(0, 212, 255, 0.05);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 6px;
            padding: 12px;
            margin: 15px 0;
            font-size: 0.9rem;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #ddd;
        }

        .stat-row:last-child {
            margin-bottom: 0;
        }

        .stat-label {
            color: #888;
        }

        .stat-value {
            color: #00d4ff;
            font-weight: 600;
        }

        .bet-input-group {
            margin: 15px 0;
        }

        .bet-input-group label {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 5px;
            display: block;
        }

        .input-importo {
            width: 100%;
            padding: 10px;
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid #00d4ff;
            color: #00d4ff;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .input-importo:focus {
            outline: none;
            background: rgba(0, 212, 255, 0.2);
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
        }

        .bet-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: #1a1a2e;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .bet-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 255, 0.5);
        }

        .bet-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .login-btn {
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: #1a1a2e;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.4);
        }

        .user-info {
            color: #00d4ff;
            font-weight: 600;
        }

        .saldo-display {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid #00d4ff;
            padding: 8px 12px;
            border-radius: 4px;
            color: #00d4ff;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .alert-message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid;
            grid-column: 1 / -1;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border-left-color: #28a745;
            color: #28a745;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border-left-color: #dc3545;
            color: #dc3545;
        }

        .alert-info {
            background: rgba(0, 212, 255, 0.1);
            border-left-color: #00d4ff;
            color: #00d4ff;
        }

        .no-matches {
            text-align: center;
            padding: 40px 20px;
            background: #0f3460;
            border: 1px solid #00d4ff;
            border-radius: 8px;
            color: #888;
        }

        .no-matches i {
            font-size: 3rem;
            color: #444;
            display: block;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-custom">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-lightning-fill"></i> FastBetNow
            </a>
            <div class="ms-auto d-flex gap-2 align-items-center">
                <?php if ($isLoggedIn): ?>
                    <a href="profilo.php" class="login-btn" style="background: #00d4ff; color: #16213e;">
                        <i class="bi bi-person-circle"></i> Profilo
                    </a>
                    <span class="saldo-display">
                        <i class="bi bi-wallet2"></i> €<?= number_format($userSaldo, 2) ?>
                    </span>
                    <button class="login-btn" style="background: #28a745; color: white;" onclick="openTopupModal()">
                        <i class="bi bi-plus-circle"></i> Ricarica
                    </button>
                    <a href="../logout.php" class="login-btn" style="background: #dc3545; color: white;">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="../login.php" class="login-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container-main">
        <?php if ($betStatus === 'success'): ?>
            <div class="alert-message alert-success">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($betMessage) ?>
            </div>
        <?php elseif ($betStatus === 'error'): ?>
            <div class="alert-message alert-error">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($betMessage) ?>
            </div>
        <?php endif; ?>

        <!-- SEZIONE PARTITE -->
        <div class="matches-section">
            <?php if (empty($partite)): ?>
                <div class="no-matches">
                    <i class="bi bi-calendar-x"></i>
                    <h3>Nessuna partita disponibile</h3>
                    <p>Torna più tardi per le prossime scommesse!</p>
                </div>
            <?php else: ?>
                <?php foreach ($partite as $partita): ?>
                    <div class="match-item">
                        <div class="match-header">
                            <span class="championship">
                                <i class="bi bi-trophy"></i> <?= htmlspecialchars($partita['campionato']) ?>
                            </span>
                            <span class="match-time">
                                <i class="bi bi-clock"></i> <?= date('d/m H:i', strtotime($partita['data_inizio'])) ?>
                            </span>
                        </div>

                        <div class="teams">
                            <span class="team-name"><?= htmlspecialchars($partita['squadra_casa']) ?></span>
                            <span class="vs">vs</span>
                            <span class="team-name"><?= htmlspecialchars($partita['squadra_trasferta']) ?></span>
                        </div>

                        <div class="odds-container">
                            <button class="odd-btn" onclick="addSelection(<?= $partita['id_partita'] ?>, '1', '<?= htmlspecialchars($partita['squadra_casa'] . ' - ' . $partita['squadra_trasferta']) ?>', <?= $partita['quota_casa'] ?>)" <?= !$canPuntaSchedina ? 'disabled' : '' ?>>
                                <span class="odd-label">1</span>
                                <span class="odd-value"><?= number_format($partita['quota_casa'], 2) ?></span>
                            </button>
                            <button class="odd-btn" onclick="addSelection(<?= $partita['id_partita'] ?>, 'X', '<?= htmlspecialchars($partita['squadra_casa'] . ' - ' . $partita['squadra_trasferta']) ?>', <?= $partita['quota_pareggio'] ?>)" <?= !$canPuntaSchedina ? 'disabled' : '' ?>>
                                <span class="odd-label">X</span>
                                <span class="odd-value"><?= number_format($partita['quota_pareggio'], 2) ?></span>
                            </button>
                            <button class="odd-btn" onclick="addSelection(<?= $partita['id_partita'] ?>, '2', '<?= htmlspecialchars($partita['squadra_casa'] . ' - ' . $partita['squadra_trasferta']) ?>', <?= $partita['quota_trasferta'] ?>)" <?= !$canPuntaSchedina ? 'disabled' : '' ?>>
                                <span class="odd-label">2</span>
                                <span class="odd-value"><?= number_format($partita['quota_trasferta'], 2) ?></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- SEZIONE SCHEDINA -->
        <div class="schedina-section">
            <form id="schedinForm" method="POST" action="punta_schedina.php" class="schedina-sticky">
                <div class="schedina-title">
                    <i class="bi bi-card-list"></i> La mia Schedina
                </div>

                <div id="schedinaItems">
                    <div class="empty-schedina">
                        <i class="bi bi-bookmark"></i>
                        <p>Nessuna scommessa</p>
                        <small>Seleziona una quota per iniziare</small>
                    </div>
                </div>

                <div class="schedina-stats">
                    <div class="stat-row">
                        <span class="stat-label">Eventi:</span>
                        <span class="stat-value" id="eventCount">0</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Quota Totale:</span>
                        <span class="stat-value" id="totalQuota">-</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Vincita Potenziale:</span>
                        <span class="stat-value" id="potentialWin">-</span>
                    </div>
                </div>

                <?php if ($isLoggedIn): ?>
                    <div class="bet-input-group">
                        <label for="importo">Importo da puntare (€)</label>
                        <input type="number" id="importo" name="importo" class="input-importo" min="1" step="0.01" value="5.00" placeholder="0.00">
                    </div>
                    <button type="submit" class="bet-submit" id="submitBtn" disabled>
                        <i class="bi bi-lightning-charge"></i> Piazza Scommessa
                    </button>
                <?php else: ?>
                    <div class="alert-message alert-info">
                        <a href="../login.php" style="color: inherit; text-decoration: underline;">Accedi</a> per piazzare scommesse
                    </div>
                <?php endif; ?>

                <input type="hidden" name="selezioni_json" id="selezioniJsonInput" value="[]">
            </form>
        </div>
    </div>

    <script>
        const JWT_TOKEN = '<?= htmlspecialchars($_SESSION['access_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
        const selections = [];
        const schedinaItems = document.getElementById('schedinaItems');
        const eventCount = document.getElementById('eventCount');
        const totalQuota = document.getElementById('totalQuota');
        const potentialWin = document.getElementById('potentialWin');
        const importoInput = document.getElementById('importo');
        const submitBtn = document.getElementById('submitBtn');
        const selezioniJsonInput = document.getElementById('selezioniJsonInput');

        function addSelection(idPartita, segno, evento, quota) {
            // Controlla se la partita è già selezionata
            const existing = selections.findIndex(s => s.id_partita === idPartita);
            
            if (existing >= 0) {
                // Sostituisci
                selections[existing] = { id_partita: idPartita, segno, evento, quota: parseFloat(quota) };
            } else {
                // Aggiungi
                selections.push({ id_partita: idPartita, segno, evento, quota: parseFloat(quota) });
            }

            updateSchedina();
        }

        function removeSelection(index) {
            selections.splice(index, 1);
            updateSchedina();
        }

        function updateSchedina() {
            if (selections.length === 0) {
                schedinaItems.innerHTML = `
                    <div class="empty-schedina">
                        <i class="bi bi-bookmark"></i>
                        <p>Nessuna scommessa</p>
                        <small>Seleziona una quota per iniziare</small>
                    </div>
                `;
                eventCount.textContent = '0';
                totalQuota.textContent = '-';
                potentialWin.textContent = '-';
                submitBtn.disabled = true;
            } else {
                // Mostra le selezioni
                let html = '';
                for (let i = 0; i < selections.length; i++) {
                    const s = selections[i];
                    html += `
                        <div class="schedina-selection">
                            <div class="selection-event">${s.evento}</div>
                            <div class="selection-sign-quota">${s.segno} @${s.quota.toFixed(2)}</div>
                            <button type="button" class="remove-selection" onclick="removeSelection(${i})">×</button>
                        </div>
                    `;
                }
                schedinaItems.innerHTML = html;

                // Calcola statistiche
                const quota = selections.reduce((acc, s) => acc * s.quota, 1);
                const importo = parseFloat(importoInput.value) || 0;
                const vincita = importo * quota;

                eventCount.textContent = selections.length;
                totalQuota.textContent = quota.toFixed(2);
                potentialWin.textContent = importo > 0 ? '€' + vincita.toFixed(2) : '-';

                // Aggiorna il JSON nascosto
                selezioniJsonInput.value = JSON.stringify(selections.map(s => ({
                    id_partita: s.id_partita,
                    segno: s.segno
                })));

                submitBtn.disabled = false;
            }
        }

        if (importoInput) {
            importoInput.addEventListener('input', updateSchedina);
        }

        // Inizializza
        updateSchedina();

        // Assicura che il campo nascosto sia sempre sincronizzato prima del submit
        const schedinForm = document.getElementById('schedinForm');
        if (schedinForm) {
            schedinForm.addEventListener('submit', function (e) {
                // aggiorna il campo nascosto
                selezioniJsonInput.value = JSON.stringify(selections.map(s => ({ id_partita: s.id_partita, segno: s.segno })));

                if (selections.length === 0) {
                    e.preventDefault();
                    alert('Aggiungi almeno un evento alla schedina');
                    return false;
                }

                // opzionale: valida importo
                const importo = parseFloat(importoInput ? importoInput.value : '0') || 0;
                if (importo <= 0) {
                    e.preventDefault();
                    alert('Inserisci un importo valido');
                    return false;
                }

                return true;
            });
        }
    </script>

    <!-- TOPUP MODAL -->
    <?php if ($isLoggedIn): ?>
    <div class="modal fade" id="topupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #0f3460; border: 2px solid #00d4ff;">
                <div class="modal-header" style="border-bottom: 2px solid #00d4ff;">
                    <h5 class="modal-title" style="color: #00d4ff;">
                        <i class="bi bi-plus-circle"></i> Guarda un Annuncio e Ricarica
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(2);"></button>
                </div>
                <div class="modal-body" style="text-align: center;">
                    <div id="adContainer" style="background: #16213e; border-radius: 8px; padding: 30px; margin-bottom: 20px; border: 2px solid #00d4ff;">
                        <div style="font-size: 3rem; color: #00d4ff; margin-bottom: 15px;">
                            <i class="bi bi-camera-video"></i>
                        </div>
                        <p style="color: #ddd; font-size: 1.1rem; margin-bottom: 20px;">
                            <strong>Guarda questo annuncio pubblicitario</strong>
                        </p>
                        <div id="adTimer" style="font-size: 2.5rem; color: #28a745; font-weight: 700; margin-bottom: 15px;">
                            15
                        </div>
                        <p style="color: #888; font-size: 0.9rem;">
                            Secondi rimanenti...
                        </p>
                    </div>
                    
                    <div style="background: rgba(0, 212, 255, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="color: #00d4ff; margin-bottom: 10px; font-weight: 600;">
                            <i class="bi bi-gift"></i> Ricompensa
                        </p>
                        <p style="color: #28a745; font-size: 2rem; font-weight: 700; margin: 0;">
                            +€5.00
                        </p>
                    </div>

                    <button type="button" class="btn w-100" id="claimButton" style="background: #28a745; color: white; font-weight: 600; padding: 12px; font-size: 1.1rem;" onclick="claimReward()" disabled>
                        <i class="bi bi-check-circle"></i> RICARICA ORA
                    </button>
                    
                    <div id="topupMessage" style="display: none; margin-top: 15px;"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let adTimerInterval = null;
        let adTimeRemaining = 15;

        function openTopupModal() {
            if (!JWT_TOKEN || JWT_TOKEN.trim() === '') {
                alert('Errore: sessione non valida. Ricarica la pagina.');
                location.href = '../login.php';
                return;
            }

            // Reset timer
            adTimeRemaining = 15;
            document.getElementById('adTimer').textContent = adTimeRemaining;
            document.getElementById('claimButton').disabled = true;
            document.getElementById('claimButton').style.background = '#999';
            document.getElementById('claimButton').style.cursor = 'not-allowed';
            document.getElementById('topupMessage').style.display = 'none';
            document.getElementById('adContainer').style.display = 'block';

            const modal = new bootstrap.Modal(document.getElementById('topupModal'));
            modal.show();

            // Avvia il timer
            if (adTimerInterval) clearInterval(adTimerInterval);
            
            adTimerInterval = setInterval(() => {
                adTimeRemaining--;
                document.getElementById('adTimer').textContent = adTimeRemaining;

                if (adTimeRemaining <= 0) {
                    clearInterval(adTimerInterval);
                    // Abilita il bottone
                    document.getElementById('claimButton').disabled = false;
                    document.getElementById('claimButton').style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                    document.getElementById('claimButton').style.cursor = 'pointer';
                    document.getElementById('adTimer').textContent = '✓';
                    document.getElementById('adTimer').style.color = '#28a745';
                }
            }, 1000);
        }

        async function claimReward() {
            const claimButton = document.getElementById('claimButton');
            const messageDiv = document.getElementById('topupMessage');
            const adContainer = document.getElementById('adContainer');

            claimButton.disabled = true;
            adContainer.style.display = 'none';

            try {
                const response = await fetch('../api/wallet/topup-ad.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + JWT_TOKEN
                    },
                    body: JSON.stringify({ amount: 5.0 })
                });

                const data = await response.json();

                if (response.ok) {
                    messageDiv.style.display = 'block';
                    messageDiv.innerHTML = `
                        <div class="alert alert-success" style="background: rgba(40, 167, 69, 0.2); border-color: #28a745; color: #28a745;">
                            <i class="bi bi-check-circle"></i> <strong>Ricarica Completata!</strong><br>
                            <small>Hai ricevuto €5.00</small><br>
                            <small>Nuovo saldo: €${data.new_saldo.toFixed(2)}</small>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    messageDiv.style.display = 'block';
                    messageDiv.innerHTML = `
                        <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border-color: #dc3545; color: #dc3545;">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Errore:</strong> ${data.error || 'Sconosciuto'}
                        </div>
                    `;
                }
            } catch (error) {
                messageDiv.style.display = 'block';
                messageDiv.innerHTML = `
                    <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border-color: #dc3545; color: #dc3545;">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Errore di Connessione</strong>
                    </div>
                `;
                console.error('Claim error:', error);
            }
        }
    </script>
</body>
