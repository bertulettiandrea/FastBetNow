<?php

require_once __DIR__ . '/Database.php';

use PHPUnit\Framework\TestCase;

class AdminServiceTest extends TestCase
{
    private $adminService;
    private $database;

    protected function setUp(): void
    {
        $this->database = $this->createMock('Database');
        $this->adminService = new AdminServiceMock($this->database);
    }

    public function testCreaPartitaValida()
    {
        $this->database->method('lastInsertId')->willReturn(1);

        $idPartita = $this->adminService->creaPartita(
            'Inter',
            'Milan',
            '2026-02-05 20:45:00',
            'Serie A',
            [
                'casa' => 2.10,
                'pareggio' => 3.40,
                'trasferta' => 3.50
            ]
        );

        $this->assertIsInt($idPartita);
        $this->assertGreaterThan(0, $idPartita);
    }

    public function testCreaPartitaQuoteInvalide()
    {
        $idPartita = $this->adminService->creaPartita(
            'Barcelona',
            'Real Madrid',
            '2026-02-08 21:00:00',
            'La Liga',
            [
                'casa' => 0,
                'pareggio' => 3.30,
                'trasferta' => 2.70
            ]
        );

        $this->assertNull($idPartita);
    }

    public function testChiudiPartita()
    {
        $this->assertTrue($this->adminService->chiudiPartita(1));
    }

    public function testRegistraRisultato()
    {
        $this->assertTrue($this->adminService->registraRisultato(1, '2-0'));
    }

    public function testRegistraRisultatoPareggio()
    {
        $this->assertTrue($this->adminService->registraRisultato(2, '1-1'));
    }

    public function testAssegnaRuolo()
    {
        $this->assertTrue($this->adminService->assegnaRuolo('aa@gmail.com', 1));
    }

    public function testRevocaRuolo()
    {
        $this->assertTrue($this->adminService->revocaRuolo('admin@gmail.com', 1));
    }

    public function testGetReportScommesse()
    {
        $this->database->method('fetchAll')
            ->willReturn([
                [
                    'total_schedu' => 42,
                    'schedu_vinte' => 15,
                    'schedu_perse' => 27,
                    'importo_totale' => 1050.50,
                    'vincite_totali' => 3200.75
                ]
            ]);

        $report = $this->adminService->getReportScommesse();

        $this->assertIsArray($report);
        $this->assertGreaterThan(0, $report['total_schedu']);
    }

    public function testGetStatiSchedine()
    {
        $this->database->method('fetchAll')
            ->willReturn([
                ['stato' => 'APERTO', 'count' => 8],
                ['stato' => 'VINTA', 'count' => 15],
                ['stato' => 'PERSA', 'count' => 19]
            ]);

        $stati = $this->adminService->getStatiSchedine();

        $this->assertIsArray($stati);
        $this->assertCount(3, $stati);
    }

    public function testCalcolaStatistiche()
    {
        $this->database->method('fetchAll')
            ->willReturn([
                [
                    'tasso_vincita' => 35.7,
                    'scommessa_media' => 25.0,
                    'vincita_media' => 76.5,
                    'utenti_attivi' => 6,
                    'partite_giocate' => 0
                ]
            ]);

        $stats = $this->adminService->calcolaStatistiche();

        $this->assertIsArray($stats);
        $this->assertGreaterThan(0, $stats['tasso_vincita']);
    }

    public function testModificaSaldoDeposito()
    {
        $this->assertTrue($this->adminService->modificaSaldo('aa@gmail.com', 50.0, 'deposito', 'Ricarica manuale'));
    }

    public function testModificaSaldoPrelievo()
    {
        $this->assertTrue($this->adminService->modificaSaldo('admin@gmail.com', 25.0, 'prelievo', 'Prelievo richiesto'));
    }
}

class AdminServiceMock
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function creaPartita($squadraCasa, $squadraTrasferta, $dataInizio, $campionato, $quote)
    {
        if ($quote['casa'] <= 0 || $quote['pareggio'] <= 0 || $quote['trasferta'] <= 0) {
            return null;
        }

        $sql = "INSERT INTO PARTITA (squadra_casa, squadra_trasferta, data_inizio, campionato,
                quota_casa, quota_pareggio, quota_trasferta, stato)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'APERTO')";

        $this->database->executeQuery($sql, [
            $squadraCasa, $squadraTrasferta, $dataInizio, $campionato,
            $quote['casa'], $quote['pareggio'], $quote['trasferta']
        ]);

        return $this->database->lastInsertId();
    }

    public function chiudiPartita($idPartita)
    {
        $sql = "UPDATE PARTITA SET stato = 'CHIUSO' WHERE id_partita = ?";
        return $this->database->executeQuery($sql, [$idPartita]);
    }

    public function registraRisultato($idPartita, $risultato)
    {
        $partita = $this->database->fetchOne(
            'SELECT * FROM PARTITA WHERE id_partita = ?',
            [$idPartita]
        );

        if ($partita === null) {
            return false;
        }

        list($goliCasa, $goliTrasferta) = explode('-', $risultato);
        $segnoVincente = ($goliCasa > $goliTrasferta) ? '1' : (($goliCasa < $goliTrasferta) ? '2' : 'X');

        $sql = "UPDATE PARTITA SET risultato = ?, stato = 'GIOCATO' WHERE id_partita = ?";
        $this->database->executeQuery($sql, [$risultato, $idPartita]);

        $this->elaboraSchedu($idPartita, $segnoVincente);

        return true;
    }

    public function assegnaRuolo($email, $idRuolo)
    {
        $sql = 'INSERT INTO UTENTE_RUOLO (email_utente, id_ruolo) VALUES (?, ?)';
        return $this->database->executeQuery($sql, [$email, $idRuolo]);
    }

    public function revocaRuolo($email, $idRuolo)
    {
        $sql = 'DELETE FROM UTENTE_RUOLO WHERE email_utente = ? AND id_ruolo = ?';
        return $this->database->executeQuery($sql, [$email, $idRuolo]);
    }

    public function getReportScommesse()
    {
        $sql = "SELECT
                COUNT(s.id) as total_schedu,
                SUM(CASE WHEN s.stato = 'VINTA' THEN 1 ELSE 0 END) as schedu_vinte,
                SUM(CASE WHEN s.stato = 'PERSA' THEN 1 ELSE 0 END) as schedu_perse,
                SUM(s.importo_totale) as importo_totale,
                SUM(CASE WHEN s.stato = 'VINTA' THEN s.vincita_potenziale ELSE 0 END) as vincite_totali
                FROM SCHEDINA s";

        return $this->database->fetchAll($sql, []);
    }

    public function getStatiSchedine()
    {
        $sql = 'SELECT stato, COUNT(*) as count FROM SCHEDINA GROUP BY stato';
        return $this->database->fetchAll($sql, []);
    }

    public function calcolaStatistiche()
    {
        $sql = "SELECT
                (COUNT(CASE WHEN stato = 'VINTA' THEN 1 END) / COUNT(*) * 100) as tasso_vincita,
                AVG(importo_totale) as scommessa_media,
                AVG(vincita_potenziale) as vincita_media
                FROM SCHEDINA";

        return $this->database->fetchAll($sql, []);
    }

    public function modificaSaldo($email, $importo, $operazione, $motivo)
    {
        $conto = $this->database->fetchOne(
            'SELECT * FROM CONTO WHERE email_intestatario = ?',
            [$email]
        );

        if ($conto === null) {
            return false;
        }

        $nuovoSaldo = ($operazione === 'deposito')
            ? $conto['saldo'] + $importo
            : $conto['saldo'] - $importo;

        $sql = 'UPDATE CONTO SET saldo = ? WHERE email_intestatario = ?';
        return $this->database->executeQuery($sql, [$nuovoSaldo, $email]);
    }

    private function elaboraSchedu($idPartita, $segnoVincente)
    {
        $puntate = $this->database->fetchAll(
            "SELECT * FROM PUNTATA WHERE id_partita = ? AND stato = 'APERTO'",
            [$idPartita]
        );

        foreach ($puntate as $puntata) {
            $stato = ($puntata['segno'] === $segnoVincente) ? 'VINTA' : 'PERSA';

            $sql = 'UPDATE PUNTATA SET stato = ? WHERE id = ?';
            $this->database->executeQuery($sql, [$stato, $puntata['id']]);

            if ($stato === 'VINTA') {
                $this->updateSchedulaSeVinta($puntata['id_schedina']);
            }
        }
    }

    private function updateSchedulaSeVinta($idSchedina)
    {
    }
}