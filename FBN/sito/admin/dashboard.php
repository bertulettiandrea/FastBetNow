<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../auth_helper.php';

$userData = getUserDataFromSession();

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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>