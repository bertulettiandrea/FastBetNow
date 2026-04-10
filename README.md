# FastBetNow

FastBetNow è una web app PHP/MySQL che simula un sito di scommesse sportive con valuta virtuale. Il progetto gestisce registrazione, login, saldo conto, visualizzazione delle partite e piazzamento di schedine singole o multiple, senza usare denaro reale.

## Stato attuale

Il flusso principale è già disponibile nel folder [FBN/](FBN/): autenticazione con sessione e JWT, visualizzazione del catalogo partite demo, inserimento della schedina e transazione sul conto utente. Il catalogo partite è attualmente hardcoded in [FBN/sito/matches.php](FBN/sito/matches.php).

## Funzionalità presenti

- Registrazione utente con creazione automatica di account, ruolo e conto iniziale.
- Login web con sessione PHP e login API JSON basata su JWT.
- Logout e gestione del token di accesso.
- Visualizzazione di partite, campionati e quote 1/X/2.
- Piazzamento di schedine con controllo saldo e rollback transazionale in caso di errore.
- Gestione permessi tramite ruoli e permessi nel database.
- Test transazionale dedicato per il flusso di puntata.

## Stack

- PHP
- MySQL / MariaDB
- Composer
- firebase/php-jwt
- Bootstrap 5 per l'interfaccia

## Struttura del repository

- [index.php](index.php): homepage del workspace, con accesso al progetto FastBetNow.
- [FBN/login.php](FBN/login.php): login web.
- [FBN/register.php](FBN/register.php): registrazione web.
- [FBN/logout.php](FBN/logout.php): logout della sessione.
- [FBN/auth_helper.php](FBN/auth_helper.php): lettura e normalizzazione dei dati utente dal JWT.
- [FBN/database.php](FBN/database.php): connessione PDO al database.
- [FBN/services/BetService.php](FBN/services/BetService.php): logica transazionale per le schedine.
- [FBN/sito/index.php](FBN/sito/index.php): pagina principale del catalogo partite.
- [FBN/sito/matches.php](FBN/sito/matches.php): catalogo demo delle partite e delle quote.
- [FBN/sito/punta_schedina.php](FBN/sito/punta_schedina.php): invio schedina multipla.
- [FBN/JWT/login_api.php](FBN/JWT/login_api.php): login API JSON con JWT.
- [FBN/JWT/refresh.php](FBN/JWT/refresh.php): refresh del token.
- [FBN/JWT/manage_permissions.php](FBN/JWT/manage_permissions.php): gestione permessi lato admin.
- [FBN/tests/puntata_transaction_test.php](FBN/tests/puntata_transaction_test.php): test del flusso di puntata e rollback.
- [materie/informatica/FBN database.sql](materie/informatica/FBN%20database.sql): dump SQL del database.

## Requisiti

- PHP 8.x
- Estensione PDO MySQL attiva
- MySQL o MariaDB
- Composer, se devi reinstallare le dipendenze

## Avvio locale

1. Importa il database da [materie/informatica/FBN database.sql](materie/informatica/FBN%20database.sql).
2. Verifica la configurazione in [FBN/database.php](FBN/database.php): per ora usa `127.0.0.1`, database `FBN`, utente `bertu`, password `bertu`.
3. Se devi reinstallare le dipendenze, esegui `composer install` dentro [FBN/](FBN/).
4. Avvia un server PHP oppure usa Apache/Nginx con document root puntata alla workspace.
5. Apri la home e poi entra in [FBN/login.php](FBN/login.php) oppure direttamente in [FBN/sito/index.php](FBN/sito/index.php) se la sessione è già attiva.

Esempio rapido con il server integrato di PHP:

```bash
php -S 127.0.0.1:8000
```

Poi visita `http://127.0.0.1:8000/FBN/login.php`.

## Flusso applicativo

1. L'utente si registra o accede da [FBN/login.php](FBN/login.php).
2. Dopo il login, il token JWT viene salvato in sessione.
3. La dashboard partite legge i dati utente con [FBN/auth_helper.php](FBN/auth_helper.php).
4. La schedina viene inviata a [FBN/sito/punta_schedina.php](FBN/sito/punta_schedina.php).
5. La logica di business scala il saldo e scrive le righe in `SCHEDINA` e `PUNTATA` tramite [FBN/services/BetService.php](FBN/services/BetService.php).

## API JWT

- [FBN/JWT/login_api.php](FBN/JWT/login_api.php): accetta credenziali in JSON e restituisce `access_token` e `refresh_token`.
- [FBN/JWT/refresh.php](FBN/JWT/refresh.php): rinnova l'access token.
- [FBN/JWT/manage_permissions.php](FBN/JWT/manage_permissions.php): richiede un token admin e consente di aggiungere o rimuovere permessi.

## Test

Il test principale attualmente disponibile verifica la transazione di puntata, il decremento del saldo e il rollback in caso di saldo insufficiente.

```bash
php FBN/tests/puntata_transaction_test.php
```

## Note operative

- Il catalogo partite è di esempio e non arriva da un provider esterno.
- [FBN/database.php](FBN/database.php) e [FBN/JWT/config.php](FBN/JWT/config.php) contengono valori di sviluppo hardcoded: vanno adattati prima di una messa online reale.
- Nel repository sono presenti anche cartelle didattiche e materiali accessori, ma il progetto applicativo principale è [FBN/](FBN/).

