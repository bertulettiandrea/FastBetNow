<?php

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../services/BetService.php';

function assertCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function fetchSaldo(PDO $pdo, string $email): float
{
    $stmt = $pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ?');
    $stmt->execute([$email]);
    $saldo = $stmt->fetchColumn();

    if ($saldo === false) {
        throw new RuntimeException('Conto non trovato per l\'utente di test');
    }

    return (float) $saldo;
}

function ensurePuntataTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS PUNTATA (
            id INT(11) NOT NULL AUTO_INCREMENT,
            id_schedina INT(11) NOT NULL,
            email_utente VARCHAR(254) NOT NULL,
            evento VARCHAR(100) NOT NULL,
            segno CHAR(1) NOT NULL,
            quota FLOAT NOT NULL,
            importo FLOAT NOT NULL,
            vincita_potenziale FLOAT NOT NULL,
            stato VARCHAR(20) NOT NULL DEFAULT 'APERTO',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY id_schedina (id_schedina),
            KEY email_utente (email_utente),
            CONSTRAINT PUNTATA_ibfk_1 FOREIGN KEY (id_schedina) REFERENCES SCHEDINA(id),
            CONSTRAINT PUNTATA_ibfk_2 FOREIGN KEY (email_utente) REFERENCES UTENTE(email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function fetchPuntataById(PDO $pdo, int $puntataId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM PUNTATA WHERE id = ?');
    $stmt->execute([$puntataId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function countPuntateByEvento(PDO $pdo, string $evento): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM PUNTATA WHERE evento = ?');
    $stmt->execute([$evento]);
    return (int) $stmt->fetchColumn();
}

function deleteByIds(PDO $pdo, string $tableName, array $ids): void
{
    if (empty($ids)) {
        return;
    }

    $ids = array_values(array_unique(array_map('intval', $ids)));
    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM $tableName WHERE id IN ($placeholders)");
    $stmt->execute($ids);
}

global $pdo;
$testEmail = getenv('FBN_TEST_EMAIL') ?: 'aa@gmail.com';

$createdPuntataIds = [];
$createdSchedinaIds = [];
$saldoOriginale = null;

echo "[INFO] Avvio test transazioni puntata\n";

try {
    ensurePuntataTable($pdo);
    $saldoOriginale = fetchSaldo($pdo, $testEmail);

    $saldoTarget = max($saldoOriginale, 50.00);
    $stmtSetSaldo = $pdo->prepare('UPDATE CONTO SET saldo = ? WHERE email_intestatario = ?');
    $stmtSetSaldo->execute([$saldoTarget, $testEmail]);

    $importoSuccess = 10.00;
    $quotaSuccess = 2.40;
    $eventoSuccess = 'TEST_TX_SUCCESS_' . time();

    $saldoPrima = fetchSaldo($pdo, $testEmail);
    $result = placeSchedinaBet($pdo, $testEmail, $importoSuccess, $quotaSuccess, $eventoSuccess, '1');

    $createdPuntataIds[] = (int) $result['puntata_id'];
    $createdSchedinaIds[] = (int) $result['schedina_id'];

    $saldoDopo = fetchSaldo($pdo, $testEmail);
    $atteso = round($saldoPrima - $importoSuccess, 2);
    assertCondition(abs($saldoDopo - $atteso) < 0.01, 'Saldo non scalato correttamente dopo puntata valida');

    $puntata = fetchPuntataById($pdo, (int) $result['puntata_id']);
    assertCondition($puntata !== null, 'Riga in PUNTATA non trovata dopo puntata valida');
    assertCondition(abs(((float) $puntata['importo']) - $importoSuccess) < 0.01, 'Importo PUNTATA non corretto');
    assertCondition((int) $puntata['id_schedina'] === (int) $result['schedina_id'], 'Link PUNTATA-SCHEDINA non coerente');

    echo "[OK] Caso successo: saldo scalato e puntata inserita\n";

    $eventoFail = 'TEST_TX_FAIL_' . time();
    $stmtSetSaldo->execute([1.00, $testEmail]);
    $countPrima = countPuntateByEvento($pdo, $eventoFail);

    $haLanciatoEccezione = false;
    try {
        placeSchedinaBet($pdo, $testEmail, 25.00, 2.00, $eventoFail, 'X');
    } catch (RuntimeException $e) {
        $haLanciatoEccezione = strpos($e->getMessage(), 'Saldo insufficiente') !== false;
    }

    $countDopo = countPuntateByEvento($pdo, $eventoFail);
    $saldoDopoErrore = fetchSaldo($pdo, $testEmail);

    assertCondition($haLanciatoEccezione, 'Il caso saldo insufficiente non ha generato l\'eccezione attesa');
    assertCondition($countDopo === $countPrima, 'E stata inserita una puntata nonostante saldo insufficiente');
    assertCondition(abs($saldoDopoErrore - 1.00) < 0.01, 'Il saldo e cambiato nel caso di rollback');

    echo "[OK] Caso rollback: nessuna puntata inserita con saldo insufficiente\n";
    echo "[OK] Test transazioni completato\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[FAIL] " . $e->getMessage() . "\n");
    exit(1);
} finally {
    if (!empty($createdPuntataIds)) {
        deleteByIds($pdo, 'PUNTATA', $createdPuntataIds);
    }

    if (!empty($createdSchedinaIds)) {
        deleteByIds($pdo, 'SCHEDINA', $createdSchedinaIds);
    }

    if ($saldoOriginale !== null) {
        $stmtRestore = $pdo->prepare('UPDATE CONTO SET saldo = ? WHERE email_intestatario = ?');
        $stmtRestore->execute([$saldoOriginale, $testEmail]);
    }
}
<?php

declare(strict_types=1);

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../services/BetService.php';

function assertTrue(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function cleanupTestUser(PDO $pdo, string $email): void {
    $pdo->prepare('DELETE FROM PUNTATA WHERE email_utente = ?')->execute([$email]);
    $pdo->prepare('DELETE FROM CONTO WHERE email_intestatario = ?')->execute([$email]);
    $pdo->prepare('DELETE FROM UTENTE_RUOLO WHERE email_utente = ?')->execute([$email]);
    $pdo->prepare('DELETE FROM UTENTE WHERE email = ?')->execute([$email]);
}

/** @var PDO $pdo */
$service = new BetService($pdo);
$email = 'test.puntata.' . bin2hex(random_bytes(5)) . '@fastbetnow.local';

try {
    cleanupTestUser($pdo, $email);

    $stmtUtente = $pdo->prepare('INSERT INTO UTENTE (email, nome, cognome, password, refresh_token) VALUES (?, ?, ?, ?, NULL)');
    $stmtUtente->execute([
        $email,
        'Test',
        'Puntata',
        password_hash('Password#123', PASSWORD_BCRYPT)
    ]);
