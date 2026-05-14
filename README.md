# FastBetNow

FastBetNow è un progetto PHP/MySQL per la gestione di utenti, partite sportive, schedine e puntate.

**Autore:** Andrea Bertuletti

## Descrizione

Il progetto gestisce registrazione e login con JWT, ruoli e permessi, creazione e chiusura delle partite, inserimento delle schedine singole e multiple, calcolo delle vincite e aggiornamento del saldo utenti.

Il database principale si trova in [materie/informatica/FBN database.sql](materie/informatica/FBN%20database.sql) e contiene le tabelle principali del sistema: `UTENTE`, `CONTO`, `RUOLO`, `PERMESSO`, `PARTITA`, `SCHEDINA`, `PUNTATA`, `RUOLO_PERMESSO`, `UTENTE_RUOLO`.

## Funzioni principali

- Registrazione e autenticazione con refresh token
- Gestione di ruoli e permessi
- Creazione, chiusura e aggiornamento delle partite
- Inserimento di schedine singole e multiple
- Controllo delle puntate e calcolo della vincita potenziale
- Aggiornamento del saldo e accredito delle vincite

## Altre funzionalita

- Login utente con JWT e assegnazione del ruolo in base ai permessi disponibili.
- Visualizzazione delle partite aperte e scelta del segno 1, X o 2.
- Inserimento di una schedina singola o multipla con controllo del saldo prima della conferma.
- Calcolo automatico della quota totale e della vincita potenziale.
- Aggiornamento del saldo dopo la conferma della schedina.
- Gestione transazionale della puntata, così in caso di errore il saldo non viene scalato.
- Recupero dei permessi utente per limitare le azioni disponibili nel sito.

## Struttura

- Codice applicativo: `FBN/`
- Database SQL: `materie/informatica/FBN database.sql`
- Documentazione: `materie/gpo/docs/`
- Test: `materie/gpo/tools/` e `FBN/tests/`

## Avvio in locale

Dal root del repository puoi avviare l'ambiente incluso con:

```bash
./phpMyAdmin/start.sh
```

Se preferisci eseguire i singoli componenti manualmente, importa lo schema SQL e avvia il server PHP:

```bash
mysql -u root -p < "materie/informatica/FBN database.sql"
php -S localhost:8000 -t .
```

## Documentazione

I file principali sono in `materie/gpo/docs/`:

- `manuale_utente.html`
- `diagram_er.html`
- `diagram_usecases.html`
- `diagram_classi.html`

## Test

I test del progetto si trovano in `materie/gpo/tools/` e in `FBN/tests/`.

```bash
vendor/bin/phpunit materie/gpo/tools/
php FBN/tests/puntata_transaction_test.php
```
 