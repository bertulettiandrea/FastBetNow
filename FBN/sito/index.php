<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../auth_helper.php';

$userData = getUserDataFromSession();
$isLoggedIn = ($userData !== null);
$userEmail = $userData['email'] ?? '';
$userName = $userData['nome'] ?? '';
$isAdmin = $userData && $userData['ruolo'] === 'ADMIN';
$userPermissions = $userData['permessi'] ?? [];

$partite = [
    [
        'squadra_casa' => 'Inter',
        'squadra_trasferta' => 'Milan',
        'campionato' => 'Serie A',
        'data' => '2026-02-05 20:45',
        'quota_casa' => 2.10,
        'quota_pareggio' => 3.40,
        'quota_trasferta' => 3.50
    ],
    [
        'squadra_casa' => 'Barcelona',
        'squadra_trasferta' => 'Real Madrid',
        'campionato' => 'La Liga',
        'data' => '2026-02-08 21:00',
        'quota_casa' => 2.65,
        'quota_pareggio' => 3.30,
        'quota_trasferta' => 2.70
    ],
    [
        'squadra_casa' => 'Man City',
        'squadra_trasferta' => 'Liverpool',
        'campionato' => 'Premier League',
        'data' => '2026-02-09 17:30',
        'quota_casa' => 2.20,
        'quota_pareggio' => 3.50,
        'quota_trasferta' => 3.30
    ],
    [
        'squadra_casa' => 'Bayern',
        'squadra_trasferta' => 'Dortmund',
        'campionato' => 'Bundesliga',
        'data' => '2026-02-09 18:30',
        'quota_casa' => 1.75,
        'quota_pareggio' => 3.80,
        'quota_trasferta' => 4.50
    ]
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastBetNow - Partite</title>
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
        
        .profile-btn, .dashboard-btn {
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
        
        .profile-btn:hover, .dashboard-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .profile-img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 18px;
        }
        
        .btn-login-nav {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 50px;
            padding: 8px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login-nav:hover {
            background: #667eea;
            color: white;
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

        .match-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .match-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.25);
        }

        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .championship-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .match-date {
            color: #666;
            font-size: 0.9rem;
        }

        .teams-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .team {
            text-align: center;
            flex: 1;
        }

        .team-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .vs-divider {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            padding: 0 20px;
        }

        .odds-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .odd-btn {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .odd-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            transform: scale(1.05);
        }

        .odd-btn:hover .odd-label,
        .odd-btn:hover .odd-value {
            color: white;
        }

        .odd-label {
            font-size: 0.85rem;
            color: #666;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .odd-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .page-title {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-weight: 700;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .no-matches {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-lightning-fill"></i> FastBetNow
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <a href="admin/dashboard.php" class="dashboard-btn">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    <?php else: ?>
                        <a href="profilo.php" class="profile-btn">
                            <div class="profile-img">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span><?= htmlspecialchars($userName) ?></span>
                        </a>
                    <?php endif; ?>
                    <a href="../logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i> Esci
                    </a>
                <?php else: ?>
                    <a href="../login.php" class="btn btn-login-nav">
                        <i class="bi bi-box-arrow-in-right"></i> Accedi
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-title">
            <h1><i class="bi bi-trophy-fill"></i> Partite Disponibili</h1>
            <p>Scegli la tua partita e piazza la tua scommessa</p>
        </div>

        <?php if (empty($partite)): ?>
            <div class="no-matches">
                <i class="bi bi-calendar-x" style="font-size: 4rem; color: #ccc;"></i>
                <h3 class="mt-3">Nessuna partita disponibile</h3>
                <p>Torna più tardi per vedere le prossime partite!</p>
            </div>
        <?php else: ?>
            <?php foreach ($partite as $partita): ?>
                <div class="match-card">
                    <div class="match-header">
                        <span class="championship-badge">
                            <i class="bi bi-trophy"></i> <?= htmlspecialchars($partita['campionato']) ?>
                        </span>
                        <span class="match-date">
                            <i class="bi bi-calendar-event"></i> 
                            <?= date('d/m/Y H:i', strtotime($partita['data'])) ?>
                        </span>
                    </div>

                    <div class="teams-container">
                        <div class="team">
                            <div class="team-name"><?= htmlspecialchars($partita['squadra_casa']) ?></div>
                        </div>
                        <div class="vs-divider">VS</div>
                        <div class="team">
                            <div class="team-name"><?= htmlspecialchars($partita['squadra_trasferta']) ?></div>
                        </div>
                    </div>

                    <div class="odds-container">
                        <div class="odd-btn">
                            <div class="odd-label">1 (Casa)</div>
                            <div class="odd-value"><?= number_format($partita['quota_casa'], 2) ?></div>
                        </div>
                        <div class="odd-btn">
                            <div class="odd-label">X (Pareggio)</div>
                            <div class="odd-value"><?= number_format($partita['quota_pareggio'], 2) ?></div>
                        </div>
                        <div class="odd-btn">
                            <div class="odd-label">2 (Trasferta)</div>
                            <div class="odd-value"><?= number_format($partita['quota_trasferta'], 2) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>