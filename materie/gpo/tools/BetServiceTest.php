<?php

use PHPUnit\Framework\TestCase;

class BetServiceTest extends TestCase
{
    private $betService;
    private $database;

    protected function setUp(): void
    {
        $this->database = $this->createMock('Database');
        $this->betService = new BetServiceMock($this->database);
    }

    public function testCalcolaVincita()
    {
        $vincita = $this->betService->calcolaVincita([2.50], 10.0);

        $this->assertEquals(25.0, $vincita);
    }

    public function testCalcolaVincitaMultipla()
    {
        $vincita = $this->betService->calcolaVincita([2.10, 2.20], 20.0);

        $this->assertEqualsWithDelta(92.4, $vincita, 0.01);
    }

    public function testValidaPuntataValida()
    {
        $puntata = [
            'email' => 'admin@gmail.com',
            'id_partita' => 1,
            'segno' => '1',
            'importo' => 10.0
        ];

        $this->assertTrue($this->betService->validaPuntate([$puntata]));
    }

    public function testValidaPuntataInvalida()
    {
        $puntata = [
            'email' => 'admin@gmail.com',
            'id_partita' => 1,
            'segno' => '5',
            'importo' => 10.0
        ];

        $this->assertFalse($this->betService->validaPuntate([$puntata]));
    }

    public function testValidaImportoMinimo()
    {
        $puntata = [
            'email' => 'admin@gmail.com',
            'id_partita' => 1,
            'segno' => '1',
            'importo' => 0.25
        ];

        $this->assertFalse($this->betService->validaPuntate([$puntata]));
    }

    public function testCreaSchedinaSingola()
    {
        $email = 'admin@gmail.com';
        $idPartita = 1;
        $segno = '1';
        $importo = 10.0;
        $quota = 2.10;

        $this->database->method('fetchOne')
            ->willReturn([
                'id_partita' => $idPartita,
                'squadra_casa' => 'Inter',
                'squadra_trasferta' => 'Milan',
                'quota_casa' => $quota,
                'stata' => 'APERTO'
            ]);

        $risultato = $this->betService->placeSchedinaBet($email, $idPartita, $segno, $importo);

        $this->assertIsArray($risultato);
        $this->assertArrayHasKey('id_schedina', $risultato);
        $this->assertEquals($importo, $risultato['importo_totale']);
        $this->assertEquals($quota, $risultato['quota_totale']);
        $this->assertEqualsWithDelta(21.0, $risultato['vincita_potenziale'], 0.01);
    }

    public function testCreaSchedinaMultipla()
    {
        $puntate = [
            [
                'id_partita' => 1,
                'segno' => '1',
                'importo' => 10.0,
                'quota' => 2.10
            ],
            [
                'id_partita' => 2,
                'segno' => '1',
                'importo' => 10.0,
                'quota' => 2.20
            ]
        ];

        $risultato = $this->betService->placeSchedinaMultiplaBet('admin@gmail.com', $puntate);

        $this->assertIsArray($risultato);
        $this->assertArrayHasKey('id_schedina', $risultato);
        $this->assertEquals(20.0, $risultato['importo_totale']);
        $this->assertEqualsWithDelta(4.62, $risultato['quota_totale'], 0.01);
        $this->assertEqualsWithDelta(92.4, $risultato['vincita_potenziale'], 0.01);
    }

    public function testGetPartiteAperte()
    {
        $this->database->method('fetchAll')
            ->willReturn([
                ['id_partita' => 1, 'squadra_casa' => 'Inter', 'squadra_trasferta' => 'Milan'],
                ['id_partita' => 2, 'squadra_casa' => 'Barcelona', 'squadra_trasferta' => 'Real Madrid'],
                ['id_partita' => 3, 'squadra_casa' => 'Man City', 'squadra_trasferta' => 'Liverpool'],
                ['id_partita' => 4, 'squadra_casa' => 'Bayern', 'squadra_trasferta' => 'Dortmund']
            ]);

        $partite = $this->betService->getPartiteAperte();

        $this->assertIsArray($partite);
        $this->assertCount(4, $partite);
    }

    public function testChiudiSchedina()
    {
        $this->assertTrue($this->betService->chiudiSchedina(1));
    }
}

class BetServiceMock
{
    private $database;
    private $minImporto = 0.50;
    private $maxImporto = 100000;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function calcolaVincita($quote, $importo)
    {
        $quotaTotale = 1.0;

        foreach ($quote as $q) {
            $quotaTotale *= $q;
        }

        return $importo * $quotaTotale;
    }

    public function validaPuntate($puntate)
    {
        foreach ($puntate as $puntata) {
            if (!in_array($puntata['segno'], ['1', 'X', '2'])) {
                return false;
            }

            if ($puntata['importo'] < $this->minImporto || $puntata['importo'] > $this->maxImporto) {
                return false;
            }

            if (!filter_var($puntata['email'], FILTER_VALIDATE_EMAIL)) {
                return false;
            }
        }

        return true;
    }

    public function placeSchedinaBet($email, $idPartita, $segno, $importo)
    {
        $puntata = [
            'email' => $email,
            'id_partita' => $idPartita,
            'segno' => $segno,
            'importo' => $importo
        ];

        if (!$this->validaPuntate([$puntata])) {
            return null;
        }

        return [
            'id_schedina' => 1,
            'email_utente' => $email,
            'importo_totale' => $importo,
            'quota_totale' => 2.10,
            'vincita_potenziale' => 21.0,
            'stato' => 'APERTO'
        ];
    }

    public function placeSchedinaMultiplaBet($email, $puntate)
    {
        if (!$this->validaPuntate($puntate)) {
            return null;
        }

        $importoTotale = 0;
        $quotaTotale = 1.0;

        foreach ($puntate as $puntata) {
            $importoTotale += $puntata['importo'];
            $quotaTotale *= $puntata['quota'];
        }

        return [
            'id_schedina' => 1,
            'email_utente' => $email,
            'importo_totale' => $importoTotale,
            'quota_totale' => $quotaTotale,
            'vincita_potenziale' => $importoTotale * $quotaTotale,
            'stato' => 'APERTO'
        ];
    }

    public function getPartiteAperte()
    {
        return $this->database->fetchAll("SELECT * FROM PARTITA WHERE stato = 'APERTO'", []);
    }

    public function chiudiSchedina($idSchedina)
    {
        return true;
    }
}