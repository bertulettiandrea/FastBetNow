<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../auth_helper.php';

$userData = getUserDataFromSession();

if (!$userData) {
    $userData = getUserDataFromRequestJWT();
}

if (!$userData || $userData['ruolo'] !== 'ADMIN') {
    header('Location: ../index.php');
    exit();
}

$userName = $userData['nome'];
$userEmail = $userData['email'];
$userPermissions = $userData['permessi'];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - FastBetNow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            padding-top: 80px;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            color: #667eea;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .dashboard-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
        }
        
        .logout-btn {
            background: transparent;
            color: #dc3545;
            border: 2px solid #dc3545;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            margin-left: 10px;
        }
        
        .logout-btn:hover {
            background: #dc3545;
            color: white;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .welcome-card h2 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .permissions-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .permission-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-lightning-fill"></i> FastBetNow
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <a href="dashboard.php" class="dashboard-btn">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../../logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Esci
                </a>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <div class="welcome-card">
            <h2><i class="bi bi-person-badge"></i> Dashboard Amministratore</h2>
            <p class="mb-0">Benvenuto, <strong><?= htmlspecialchars($userName) ?></strong></p>
            <p class="text-muted mb-0"><?= htmlspecialchars($userEmail) ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-value">1,234</div>
                <div class="stat-label">Utenti Totali</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="stat-value">45</div>
                <div class="stat-label">Partite Attive</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-card-checklist"></i>
                </div>
                <div class="stat-value">789</div>
                <div class="stat-label">Schedine Oggi</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-value">€45,678</div>
                <div class="stat-label">Volume Scommesse</div>
            </div>
        </div>

        <div class="permissions-section">
            <h4><i class="bi bi-shield-check"></i> I Tuoi Permessi</h4>
            <p class="text-muted mb-3">Ruolo: <strong>ADMIN</strong></p>
            <div>
                <?php foreach ($userPermissions as $permission): ?>
                    <span class="permission-badge">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($permission) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="permissions-section" style="margin-top: 30px;">
            <h4><i class="bi bi-lightning-charge"></i> Azioni Rapide</h4>
            <p class="text-muted mb-3">Sincronizza le partite dall'API esterna e aggiorna le quote</p>
            <button class="btn btn-lg" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; font-weight: 600;" onclick="openSyncModal()">
                <i class="bi bi-arrow-clockwise"></i> Sincronizza Partite
            </button>
        </div>
    </div>

    <!-- SYNC MODAL -->
    <div class="modal fade" id="syncModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: white;">
                <div class="modal-header" style="border-bottom: 2px solid #667eea;">
                    <h5 class="modal-title" style="color: #667eea;">
                        <i class="bi bi-arrow-clockwise"></i> Sincronizza Partite
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="syncContent">
                        <p class="text-muted mb-4">Questo sincronizzerà le partite da <strong>football-data.org</strong> e aggiornerà le quote nel database.</p>
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle"></i> <strong>Nota:</strong> Il sync può essere eseguito una volta ogni ora per evitare troppe richieste all'API.
                        </div>
                        <button type="button" class="btn w-100" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; font-weight: 600;" onclick="executeSyncMatches()">
                            <i class="bi bi-play-circle"></i> Inizia Sincronizzazione
                        </button>
                    </div>
                    <div id="syncLoading" style="display: none; text-align: center;">
                        <div class="spinner-border text-success" role="status" style="margin: 20px 0;">
                            <span class="visually-hidden">Caricamento...</span>
                        </div>
                        <p class="text-muted">Sincronizzazione in corso...</p>
                    </div>
                    <div id="syncResult" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const JWT_TOKEN = '<?= htmlspecialchars($_SESSION['access_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';

        function openSyncModal() {
            const modal = new bootstrap.Modal(document.getElementById('syncModal'));
            modal.show();
            // Reset content
            document.getElementById('syncContent').style.display = 'block';
            document.getElementById('syncLoading').style.display = 'none';
            document.getElementById('syncResult').style.display = 'none';
        }

        async function executeSyncMatches() {
            const syncLoading = document.getElementById('syncLoading');
            const syncContent = document.getElementById('syncContent');
            const syncResult = document.getElementById('syncResult');

            syncContent.style.display = 'none';
            syncLoading.style.display = 'block';
            syncResult.style.display = 'none';

            try {
                const response = await fetch('../../api/sync-matches.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + JWT_TOKEN
                    }
                });

                const data = await response.json();

                syncLoading.style.display = 'none';

                let resultHtml = '';

                if (response.ok) {
                    if (data.status === 'success') {
                        resultHtml = `
                            <div class="alert alert-success mb-3">
                                <i class="bi bi-check-circle"></i> <strong>Sincronizzazione Riuscita!</strong>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <p class="mb-2"><strong>Partite Inserite:</strong> <span style="color: #28a745; font-weight: 700;">${data.inserted}</span></p>
                                <p class="mb-2"><strong>Partite Aggiornate:</strong> <span style="color: #0dcaf0; font-weight: 700;">${data.updated}</span></p>
                                <p class="mb-0"><strong>Data Sincronizzazione:</strong> <span style="color: #667eea;">${data.synced_at}</span></p>
                            </div>
                            ${data.errors && data.errors.length > 0 ? `
                                <div class="alert alert-warning mb-0">
                                    <strong>Avvertimenti (${data.errors.length}):</strong>
                                    <ul class="mb-0 mt-2">
                                        ${data.errors.map(e => '<li>' + e + '</li>').join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        `;
                    } else if (data.status === 'skipped') {
                        resultHtml = `
                            <div class="alert alert-warning">
                                <i class="bi bi-clock-history"></i> <strong>Sync Saltato</strong><br>
                                <small>${data.message}</small><br>
                                <small>Prossima sincronizzazione: ${data.next_sync}</small>
                            </div>
                        `;
                    }
                } else {
                    resultHtml = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Errore</strong><br>
                            <small>${data.message || data.error || 'Errore sconosciuto'}</small>
                            ${data.hint ? '<br><small style="margin-top: 10px; display: block;"><strong>Suggerimento:</strong> ' + data.hint + '</small>' : ''}
                        </div>
                    `;
                }

                syncResult.innerHTML = resultHtml;
                syncResult.style.display = 'block';

            } catch (error) {
                syncLoading.style.display = 'none';
                syncResult.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Errore di Connessione</strong><br>
                        <small>${error.message}</small>
                    </div>
                `;
                syncResult.style.display = 'block';
                console.error('Sync error:', error);
            }
        }
    </script>