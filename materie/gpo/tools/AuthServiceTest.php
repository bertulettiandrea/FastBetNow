<?php

require_once __DIR__ . '/Database.php';

use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private $authService;
    private $database;

    protected function setUp(): void
    {
        $this->database = $this->createMock('Database');
        $this->authService = new AuthServiceMock($this->database);
    }

    public function testHashPassword()
    {
        $password = 'MySecurePassword123';
        $hash = $this->authService->hashPassword($password);

        $this->assertNotEquals($password, $hash);
        $this->assertStringStartsWith('$2y$10$', $hash);
    }

    public function testVerifyPasswordCorretta()
    {
        $hash = password_hash('password123', PASSWORD_BCRYPT);

        $this->assertTrue($this->authService->verifyPassword('password123', $hash));
    }

    public function testVerifyPasswordErrata()
    {
        $hash = '$2y$10$fjGSHipCTjv6kO6BY70G4e7MmRNn3/invfDwrBgdK/OTReenZy1i2';

        $this->assertFalse($this->authService->verifyPassword('PasswordSbagliata123', $hash));
    }

    public function testRegistrazioneValida()
    {
        $this->assertTrue($this->authService->register('nuovo_utente@gmail.com', 'Marco', 'Rossi', 'SecurePass123'));
    }

    public function testRegistrazioneDuplicata()
    {
        $email = 'admin@gmail.com';

        $this->database->method('fetchOne')->willReturn(['email' => $email]);

        $this->assertFalse($this->authService->register($email, 'Admin', 'User', 'SecurePass123'));
    }

    public function testLoginCorretto()
    {
        $email = 'admin@gmail.com';
        $hash = password_hash('password123', PASSWORD_BCRYPT);

        $this->database->method('fetchOne')
            ->willReturn([
                'email' => $email,
                'password' => $hash
            ]);

        $token = $this->authService->login($email, 'password123');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testLoginUtenteNonTrovato()
    {
        $this->database->method('fetchOne')->willReturn(null);

        $this->assertNull($this->authService->login('non_esiste@gmail.com', 'password123'));
    }

    public function testVerificaPermessoAdmin()
    {
        $this->database->method('fetchOne')->willReturn(['id' => 1]);

        $this->assertTrue($this->authService->verificaPermesso('admin@gmail.com', 'CREA_PARTITA'));
    }

    public function testVerificaPermessoUtente()
    {
        $this->database->method('fetchOne')->willReturn(null);

        $this->assertFalse($this->authService->verificaPermesso('aa@gmail.com', 'CREA_PARTITA'));
    }

    public function testVerificaRuoloAdmin()
    {
        $this->database->method('fetchOne')->willReturn(['id' => 1, 'nome' => 'ADMIN']);

        $this->assertTrue($this->authService->verificaRuolo('admin@gmail.com', 'ADMIN'));
    }

    public function testVerificaRuoloUtenteSuAdmin()
    {
        $this->database->method('fetchOne')->willReturn(null);

        $this->assertFalse($this->authService->verificaRuolo('admin@gmail.com', 'UTENTE'));
    }

    public function testLogout()
    {
        $this->assertTrue($this->authService->logout('admin@gmail.com'));
    }
}

class AuthServiceMock
{
    private $database;
    private $jwtSecret = 'secret_key_for_testing';

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public function register($email, $nome, $cognome, $password)
    {
        $utente = $this->database->fetchOne(
            'SELECT * FROM UTENTE WHERE email = ?',
            [$email]
        );

        if ($utente !== null) {
            return false;
        }

        $this->hashPassword($password);

        return true;
    }

    public function login($email, $password)
    {
        $utente = $this->database->fetchOne(
            'SELECT * FROM UTENTE WHERE email = ?',
            [$email]
        );

        if ($utente === null) {
            return null;
        }

        if (!$this->verifyPassword($password, $utente['password'])) {
            return null;
        }

        return $this->generateJWT($email);
    }

    public function logout($email)
    {
        return true;
    }

    public function verificaPermesso($email, $codicePermesso)
    {
        $result = $this->database->fetchOne(
            "SELECT p.* FROM PERMESSO p
             JOIN RUOLO_PERMESSO rp ON p.id = rp.id_permesso
             JOIN RUOLO r ON rp.id_ruolo = r.id
             JOIN UTENTE_RUOLO ur ON r.id = ur.id_ruolo
             WHERE ur.email_utente = ? AND p.codice = ?",
            [$email, $codicePermesso]
        );

        return $result !== null;
    }

    public function verificaRuolo($email, $ruolo)
    {
        $result = $this->database->fetchOne(
            "SELECT r.* FROM RUOLO r
             JOIN UTENTE_RUOLO ur ON r.id = ur.id_ruolo
             WHERE ur.email_utente = ? AND r.nome = ?",
            [$email, $ruolo]
        );

        return $result !== null;
    }

    private function generateJWT($email)
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'email' => $email,
            'iat' => time(),
            'exp' => time() + 3600
        ]));
        $signature = hash_hmac('sha256', "$header.$payload", $this->jwtSecret, true);
        $signature = base64_encode($signature);

        return "$header.$payload.$signature";
    }
}