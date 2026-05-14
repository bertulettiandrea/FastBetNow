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

    // Valida e normalizza le selezioni
    $selezioniNormalizzate = [];
    $quotaTotale = 1.0;

    foreach ($selezioni as $selezione) {
        $idPartita = (int) ($selezione['id_partita'] ?? 0);
        $squadraCasa = trim((string) ($selezione['squadra_casa'] ?? ''));
        $squadraTrasferta = trim((string) ($selezione['squadra_trasferta'] ?? ''));
        $segno = (string) ($selezione['segno'] ?? '');
        $quota = round((float) ($selezione['quota'] ?? 0), 2);

        if ($idPartita <= 0) {
            throw new InvalidArgumentException('ID partita non valido');
        }

        if ($squadraCasa === '' || $squadraTrasferta === '') {
            throw new InvalidArgumentException('Nome squadra non valido');
        }

        if (!in_array($segno, ['1', 'X', '2'], true)) {
            throw new InvalidArgumentException('Segno non valido');
        }

        if ($quota <= 0) {
            throw new InvalidArgumentException('Quota non valida');
        }

        $selezioniNormalizzate[] = [
            'id_partita' => $idPartita,
            'squadra_casa' => $squadraCasa,
            'squadra_trasferta' => $squadraTrasferta,
            'segno' => $segno,
            'quota' => $quota,
        ];

        $quotaTotale *= $quota;
    }

    $quotaTotale = round($quotaTotale, 2);
    $vincitaPotenziale = round($importo * $quotaTotale, 2);

    try {
        $pdo->beginTransaction();

        // Verifica saldo
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

        // Crea la schedina
        $stmtSchedina = $pdo->prepare(
            'INSERT INTO SCHEDINA (email_utente, importo_totale, quota_totale, vincita_potenziale, esito, stato) 
             VALUES (?, ?, ?, ?, NULL, ?)'
        );
        $stmtSchedina->execute([$emailUtente, $importo, $quotaTotale, $vincitaPotenziale, 'APERTO']);
        $schedinaId = (int) $pdo->lastInsertId();

        // Inserisci le puntate
        $stmtPuntata = $pdo->prepare(
            'INSERT INTO PUNTATA (id_schedina, id_partita, email_utente, squadra_casa, squadra_trasferta, segno, quota, importo, vincita_potenziale, stato) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $puntataIds = [];
        foreach ($selezioniNormalizzate as $selezione) {
            $vincitaEvento = round($importo * $selezione['quota'], 2);
            $stmtPuntata->execute([
                $schedinaId,
                $selezione['id_partita'],
                $emailUtente,
                $selezione['squadra_casa'],
                $selezione['squadra_trasferta'],
                $selezione['segno'],
                $selezione['quota'],
                $importo,
                $vincitaEvento,
                'APERTO',
            ]);
            $puntataIds[] = (int) $pdo->lastInsertId();
        }

        // Aggiorna il saldo
        $stmtAggiornaSaldo = $pdo->prepare('UPDATE CONTO SET saldo = saldo - ? WHERE email_intestatario = ?');
        $stmtAggiornaSaldo->execute([$importo, $emailUtente]);

        // Leggi il nuovo saldo
        $stmtNuovoSaldo = $pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ?');
        $stmtNuovoSaldo->execute([$emailUtente]);
        $nuovoSaldo = (float) $stmtNuovoSaldo->fetchColumn();

        $pdo->commit();

        return [
            'schedina_id' => $schedinaId,
            'puntata_ids' => $puntataIds,
            'numero_eventi' => count($selezioniNormalizzate),
            'quota_totale' => $quotaTotale,
            'vincita_potenziale_totale' => $vincitaPotenziale,
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
    private int $tenantId;

    public function __construct(PDO $pdo, int $tenantId = 1) {
        $this->pdo = $pdo;
        $this->tenantId = $tenantId;
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
            $stmtSaldo = $this->pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ? AND tenant_id = ? FOR UPDATE');
            $stmtSaldo->execute([$emailUtente, $this->tenantId]);
            $conto = $stmtSaldo->fetch();

            if (!$conto) {
                throw new RuntimeException('Conto utente non trovato');
            }

            $saldoAttuale = (float) $conto['saldo'];
            if ($saldoAttuale < $importo) {
                throw new RuntimeException('Saldo insufficiente');
            }

            $saldoNuovo = round($saldoAttuale - $importo, 2);

            $stmtUpdateSaldo = $this->pdo->prepare('UPDATE CONTO SET saldo = ? WHERE email_intestatario = ? AND tenant_id = ?');
            $stmtUpdateSaldo->execute([$saldoNuovo, $emailUtente, $this->tenantId]);

            $stmtInsertPuntata = $this->pdo->prepare(
                'INSERT INTO PUNTATA (email_utente, partita, esito_scelto, quota, importo, vincita_potenziale, stato, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtInsertPuntata->execute([
                $emailUtente,
                $partita,
                $esitoScelto,
                $quota,
                $importo,
                $vincitaPotenziale,
                'APERTA',
                $this->tenantId
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

    public function placeSchedinaMultiplaBet(
        string $emailUtente,
        array $selezioni,
        float $importo
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

        // Valida e normalizza le selezioni
        $selezioniNormalizzate = [];
        $quotaTotale = 1.0;

        foreach ($selezioni as $selezione) {
            $idPartita = (int) ($selezione['id_partita'] ?? 0);
            $squadraCasa = trim((string) ($selezione['squadra_casa'] ?? ''));
            $squadraTrasferta = trim((string) ($selezione['squadra_trasferta'] ?? ''));
            $segno = (string) ($selezione['segno'] ?? '');
            $quota = round((float) ($selezione['quota'] ?? 0), 2);

            if ($idPartita <= 0) {
                throw new InvalidArgumentException('ID partita non valido');
            }

            if ($squadraCasa === '' || $squadraTrasferta === '') {
                throw new InvalidArgumentException('Nome squadra non valido');
            }

            if (!in_array($segno, ['1', 'X', '2'], true)) {
                throw new InvalidArgumentException('Segno non valido');
            }

            if ($quota <= 0) {
                throw new InvalidArgumentException('Quota non valida');
            }

            $selezioniNormalizzate[] = [
                'id_partita' => $idPartita,
                'squadra_casa' => $squadraCasa,
                'squadra_trasferta' => $squadraTrasferta,
                'segno' => $segno,
                'quota' => $quota,
            ];

            $quotaTotale *= $quota;
        }

        $quotaTotale = round($quotaTotale, 2);
        $vincitaPotenziale = round($importo * $quotaTotale, 2);

        try {
            $this->pdo->beginTransaction();

            // Verifica saldo
            $stmtSaldo = $this->pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ? AND tenant_id = ? FOR UPDATE');
            $stmtSaldo->execute([$emailUtente, $this->tenantId]);
            $conto = $stmtSaldo->fetch();

            if (!$conto) {
                throw new RuntimeException('Conto utente non trovato');
            }

            $saldoAttuale = (float) $conto['saldo'];
            if ($saldoAttuale < $importo) {
                throw new RuntimeException('Saldo insufficiente');
            }

            // Crea la schedina
            $stmtSchedina = $this->pdo->prepare(
                'INSERT INTO SCHEDINA (email_utente, importo_totale, quota_totale, vincita_potenziale, esito, stato, tenant_id) 
                 VALUES (?, ?, ?, ?, NULL, ?, ?)'
            );
            $stmtSchedina->execute([$emailUtente, $importo, $quotaTotale, $vincitaPotenziale, 'APERTO', $this->tenantId]);
            $schedinaId = (int) $this->pdo->lastInsertId();

            // Inserisci le puntate
            $stmtPuntata = $this->pdo->prepare(
                'INSERT INTO PUNTATA (id_schedina, id_partita, email_utente, squadra_casa, squadra_trasferta, segno, quota, importo, vincita_potenziale, stato, tenant_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $puntataIds = [];
            foreach ($selezioniNormalizzate as $selezione) {
                $vincitaEvento = round($importo * $selezione['quota'], 2);
                $stmtPuntata->execute([
                    $schedinaId,
                    $selezione['id_partita'],
                    $emailUtente,
                    $selezione['squadra_casa'],
                    $selezione['squadra_trasferta'],
                    $selezione['segno'],
                    $selezione['quota'],
                    $importo,
                    $vincitaEvento,
                    'APERTO',
                    $this->tenantId,
                ]);
                $puntataIds[] = (int) $this->pdo->lastInsertId();
            }

            // Aggiorna il saldo
            $stmtAggiornaSaldo = $this->pdo->prepare('UPDATE CONTO SET saldo = saldo - ? WHERE email_intestatario = ? AND tenant_id = ?');
            $stmtAggiornaSaldo->execute([$importo, $emailUtente, $this->tenantId]);

            // Leggi il nuovo saldo
            $stmtNuovoSaldo = $this->pdo->prepare('SELECT saldo FROM CONTO WHERE email_intestatario = ? AND tenant_id = ?');
            $stmtNuovoSaldo->execute([$emailUtente, $this->tenantId]);
            $nuovoSaldo = (float) $stmtNuovoSaldo->fetchColumn();

            $this->pdo->commit();

            return [
                'id_schedina' => $schedinaId,
                'puntata_ids' => $puntataIds,
                'numero_eventi' => count($selezioniNormalizzate),
                'quota_totale' => $quotaTotale,
                'vincita_potenziale' => $vincitaPotenziale,
                'importo' => $importo,
                'saldo_attuale' => round($nuovoSaldo, 2),
                'created_at' => date('Y-m-d H:i:s'),
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
