<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' || $method === 'GET') {
    $token = getBearerToken();
    
    if (!$token || !verifyJWT($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autorizzato']);
        exit;
    }

    $payload = decodeJWT($token);
    $email = $payload['sub'];

    if (!hasPermissionJWT($pdo, $email, 'CREA_PARTITA')) {
        http_response_code(403);
        echo json_encode(['error' => 'Non hai permessi per sincronizzare partite']);
        exit;
    }

    syncMatches();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
}

function syncMatches() {
    global $pdo;
    
    $cacheFile = __DIR__ . '/../../.cache/matches_sync.json';
    $cacheDir = dirname($cacheFile);
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    $now = time();
    $lastSync = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true)['timestamp'] ?? 0 : 0;
    $syncInterval = 3600;

    if ($now - $lastSync < $syncInterval) {
        http_response_code(429);
        echo json_encode([
            'status' => 'skipped',
            'message' => 'Sync già eseguito di recente. Prossimo in ' . ($syncInterval - ($now - $lastSync)) . 's',
            'next_sync' => date('Y-m-d H:i:s', $lastSync + $syncInterval)
        ]);
        return;
    }

    $apiKey = getenv('FOOTBALL_DATA_API_KEY') ?: 'YOUR_FOOTBALL_DATA_API_KEY';
    $apiUrl = 'https://api.football-data.org/v4/competitions/SA/matches?status=SCHEDULED';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-Auth-Token: ' . $apiKey],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(502);
        echo json_encode([
            'status' => 'error',
            'message' => 'Errore chiamata API football-data.org',
            'http_code' => $httpCode,
            'hint' => 'Verifica che FOOTBALL_DATA_API_KEY sia impostata'
        ]);
        return;
    }

    $data = json_decode($response, true);
    
    if (!isset($data['matches'])) {
        http_response_code(502);
        echo json_encode([
            'status' => 'error',
            'message' => 'Formato risposta API inatteso'
        ]);
        return;
    }

    $inserted = 0;
    $updated = 0;
    $errors = [];

    try {
        $pdo->beginTransaction();

        foreach ($data['matches'] as $match) {
            $homeTeam = $match['homeTeam']['name'] ?? null;
            $awayTeam = $match['awayTeam']['name'] ?? null;
            $dataInizio = $match['utcDate'] ?? null;

            if (!$homeTeam || !$awayTeam || !$dataInizio) {
                $errors[] = "Match incompleto";
                continue;
            }

            $dataInizio = str_replace('Z', '', $dataInizio);

            $quotes = generateQuotes($homeTeam, $awayTeam);

            $stmt = $pdo->prepare("
                SELECT id_partita FROM PARTITA 
                WHERE squadra_casa = ? AND squadra_trasferta = ? AND data_inizio = ?
            ");
            $stmt->execute([$homeTeam, $awayTeam, $dataInizio]);
            $existing = $stmt->fetch();

            if ($existing) {
                $updateStmt = $pdo->prepare("
                    UPDATE PARTITA 
                    SET quota_casa = ?, quota_pareggio = ?, quota_trasferta = ?, updated_at = NOW()
                    WHERE id_partita = ?
                ");
                $updateStmt->execute([
                    $quotes['casa'],
                    $quotes['pareggio'],
                    $quotes['trasferta'],
                    $existing['id_partita']
                ]);
                $updated++;
            } else {
                $insertStmt = $pdo->prepare("
                    INSERT INTO PARTITA (squadra_casa, squadra_trasferta, data_inizio, campionato, 
                                         quota_casa, quota_pareggio, quota_trasferta, stato)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'APERTO')
                ");
                $insertStmt->execute([
                    $homeTeam,
                    $awayTeam,
                    $dataInizio,
                    'Serie A',
                    $quotes['casa'],
                    $quotes['pareggio'],
                    $quotes['trasferta']
                ]);
                $inserted++;
            }
        }

        $pdo->commit();

        file_put_contents($cacheFile, json_encode([
            'timestamp' => $now,
            'inserted' => $inserted,
            'updated' => $updated,
            'synced_at' => date('Y-m-d H:i:s')
        ]));

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => $errors,
            'synced_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

function generateQuotes($homeTeam, $awayTeam) {
    $seed = crc32($homeTeam . $awayTeam) % 100;

    $homeWinProb = 0.45 + ($seed % 15) / 100;
    $drawProb = 0.25 + ($seed % 10) / 100;
    $awayWinProb = 1 - $homeWinProb - $drawProb;

    return [
        'casa' => round(1 / max($homeWinProb, 0.01), 2),
        'pareggio' => round(1 / max($drawProb, 0.01), 2),
        'trasferta' => round(1 / max($awayWinProb, 0.01), 2),
    ];
}
?>
