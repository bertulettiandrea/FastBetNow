<?php

function placeSchedinaMultiplaBet(
    PDO $pdo,
    string $emailUtente,
    float $importo,
    array $selezioni
): array {
    $emailUtente = trim($emailUtente);
    $importo = round($importo, 2);

    if ($emailUtente === '') {
        throw new InvalidArgumentException('Utente non valido');
    }

    if ($importo <= 0) {
        throw new InvalidArgumentException('Importo non valido');
    }

    if (empty($selezioni)) {
        throw new InvalidArgumentException('Aggiungi almeno un evento alla schedina');
    }

    $selezioniNormalizzate = [];
    $quotaTotale = 1.0;

    foreach ($selezioni as $selezione) {
        $evento = trim((string) ($selezione['evento'] ?? ''));
        $segno = (string) ($selezione['segno'] ?? '');
        $quota = round((float) ($selezione['quota'] ?? 0), 2);

        if ($evento === '') {
            throw new InvalidArgumentException('Evento non valido');
        }

        if (!in_array($segno, ['1', 'X', '2'], true)) {
            throw new InvalidArgumentException('Segno non valido');
        }

        if ($quota <= 0) {
            throw new InvalidArgumentException('Quota non valida');
        }

        $selezioniNormalizzate[] = [
            'evento' => $evento,
            'segno' => $segno,
            'quota' => $quota,
        ];

        $quotaTotale *= $quota;
    }

    $quotaTotale = round($quotaTotale, 2);
    $vincitaTotale = round($importo * $quotaTotale, 2);

    try {
        $pdo->beginTransaction();

        $stmtSaldo = $pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ? FOR UPDATE');
        $stmtSaldo->execute([$emailUtente]);
        $conto = $stmtSaldo->fetch();

        if (!$conto) {
            throw new RuntimeException('Conto utente non trovato');
        }

        $saldoAttuale = (float) $conto['saldo'];
        if ($saldoAttuale < $importo) {
            throw new RuntimeException('Saldo insufficiente');
        }

        $stmtSchedina = $pdo->prepare('INSERT INTO SCHEDINA (esito, puntata) VALUES (NULL, ?)');
        $stmtSchedina->execute([$importo]);
        $schedinaId = (int) $pdo->lastInsertId();

        $stmtPuntata = $pdo->prepare(
            'INSERT INTO PUNTATA (id_schedina, email_utente, evento, segno, quota, importo, vincita_potenziale, stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $puntataIds = [];
        foreach ($selezioniNormalizzate as $selezione) {
            $vincitaEvento = round($importo * (float) $selezione['quota'], 2);
            $stmtPuntata->execute([
                $schedinaId,
                $emailUtente,
                $selezione['evento'],
                $selezione['segno'],
                $selezione['quota'],
                $importo,
                $vincitaEvento,
                'APERTO',
            ]);
            $puntataIds[] = (int) $pdo->lastInsertId();
        }

        $stmtAggiornaSaldo = $pdo->prepare('UPDATE CONTO SET saldo = saldo - ? WHERE email_intestatario = ?');
        $stmtAggiornaSaldo->execute([$importo, $emailUtente]);

        $stmtNuovoSaldo = $pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ?');
        $stmtNuovoSaldo->execute([$emailUtente]);
        $nuovoSaldo = (float) $stmtNuovoSaldo->fetchColumn();

        $pdo->commit();

        return [
            'schedina_id' => $schedinaId,
            'puntata_ids' => $puntataIds,
            'numero_eventi' => count($selezioniNormalizzate),
            'quota_totale' => $quotaTotale,
            'vincita_potenziale_totale' => $vincitaTotale,
            'saldo_attuale' => round($nuovoSaldo, 2),
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function placeSchedinaBet(
    PDO $pdo,
    string $emailUtente,
    float $importo,
    float $quota,
    string $evento,
    string $segno
): array {
    $result = placeSchedinaMultiplaBet($pdo, $emailUtente, $importo, [[
        'evento' => $evento,
        'segno' => $segno,
        'quota' => $quota,
    ]]);

    return [
        'puntata_id' => $result['puntata_ids'][0] ?? 0,
        'schedina_id' => $result['schedina_id'],
        'saldo_attuale' => $result['saldo_attuale'],
        'vincita_potenziale' => $result['vincita_potenziale_totale'],
    ];
}

class BetService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ensurePuntataTable();
    }

    public function placeSchedina(string $emailUtente, string $partita, string $esitoScelto, float $quota, float $importo): array {
        if ($emailUtente === '') {
            throw new InvalidArgumentException('Email utente mancante');
        }

        if ($partita === '') {
            throw new InvalidArgumentException('Partita non valida');
        }

        if (!in_array($esitoScelto, ['1', 'X', '2'], true)) {
            throw new InvalidArgumentException('Esito non valido');
        }

        if ($quota < 1.01) {
            throw new InvalidArgumentException('Quota non valida');
        }

        if ($importo <= 0) {
            throw new InvalidArgumentException('Importo puntata non valido');
        }

        $importo = round($importo, 2);
        $quota = round($quota, 2);
        $vincitaPotenziale = round($importo * $quota, 2);

        $this->pdo->beginTransaction();

        try {
            $stmtSaldo = $this->pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ? FOR UPDATE');
            $stmtSaldo->execute([$emailUtente]);
            $conto = $stmtSaldo->fetch();

            if (!$conto) {
                throw new RuntimeException('Conto utente non trovato');
            }

            $saldoAttuale = (float) $conto['saldo'];
            if ($saldoAttuale < $importo) {
                throw new RuntimeException('Saldo insufficiente');
            }

            $saldoNuovo = round($saldoAttuale - $importo, 2);

            $stmtUpdateSaldo = $this->pdo->prepare('UPDATE CONTO SET saldo = ? WHERE email_intestatario = ?');
            $stmtUpdateSaldo->execute([$saldoNuovo, $emailUtente]);

            $stmtInsertPuntata = $this->pdo->prepare(
                'INSERT INTO PUNTATA (email_utente, partita, esito_scelto, quota, importo, vincita_potenziale, stato) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtInsertPuntata->execute([
                $emailUtente,
                $partita,
                $esitoScelto,
                $quota,
                $importo,
                $vincitaPotenziale,
                'APERTA'
            ]);

            $idPuntata = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return [
                'id_puntata' => $idPuntata,
                'saldo_precedente' => $saldoAttuale,
                'saldo_attuale' => $saldoNuovo,
                'vincita_potenziale' => $vincitaPotenziale
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function ensurePuntataTable(): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS PUNTATA (
                id INT NOT NULL AUTO_INCREMENT,
                email_utente VARCHAR(254) NOT NULL,
                partita VARCHAR(80) NOT NULL,
                esito_scelto VARCHAR(5) NOT NULL,
                quota DECIMAL(6,2) NOT NULL,
                importo DECIMAL(10,2) NOT NULL,
                vincita_potenziale DECIMAL(10,2) NOT NULL,
                stato VARCHAR(20) NOT NULL DEFAULT 'APERTA',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_puntata_email (email_utente),
                CONSTRAINT FK_PUNTATA_UTENTE FOREIGN KEY (email_utente) REFERENCES UTENTE(email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->pdo->exec($sql);
    }
}
