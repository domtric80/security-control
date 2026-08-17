# Requisiti SEC - Webapp PHP MVP

Questa cartella contiene un primo MVP PHP per sostituire il flusso Excel:

- compilazione questionari;
- salvataggio risposte;
- calcolo requisiti applicabili;
- calcolo servizi suggeriti;
- pannello admin minimo per domande, requisiti, servizi e regole.

## Requisiti runtime

- PHP 8.1+ con estensione `pdo_mysql` oppure `pdo_pgsql`;
- MySQL 8+ consigliato per lo schema `database/schema.sql`;
- web server locale, ad esempio Apache, Nginx o il server integrato PHP.

Nel computer corrente non risultano installati `php`, `composer`, `mysql` o `psql`, quindi il codice è stato predisposto ma non eseguito localmente.

## Avvio con Docker

Dalla cartella padre del progetto:

```bash
docker compose up -d --build
```

Aprire:

```text
http://127.0.0.1:8089
```

Adminer per consultare il database:

```text
http://127.0.0.1:8090
```

Credenziali Adminer:

- sistema: `MySQL`
- server: `db`
- utente: `requisiti`
- password: `requisiti`
- database: `requisiti`

Credenziali applicative admin:

- creare/gestire gli utenti dal database o tramite LDAP/OIDC;
- il fallback admin legacy è disabilitato di default.

### Comandi utili Docker

```bash
docker compose logs -f app
docker compose logs -f db
docker compose exec app php tools/smoke_test.php
docker compose exec app php tools/import_json.php
docker compose down
```

Per ricreare completamente il database e rieseguire tutti i seed:

```bash
docker compose down -v
docker compose up -d --build
```

Nota: gli script in `docker-entrypoint-initdb.d` vengono eseguiti solo quando il volume MySQL è vuoto.

## Configurazione

Modificare `config/config.php` oppure usare variabili ambiente:

- `REQ_DB_DRIVER`: `mysql` o `pgsql`;
- `REQ_DB_HOST`;
- `REQ_DB_PORT`;
- `REQ_DB_NAME`;
- `REQ_DB_USER`;
- `REQ_DB_PASS`;
- `REQ_APP_BASE_URL`;
- `REQ_LEGACY_ADMIN_ENABLED` solo per emergenza locale;
- `REQ_ADMIN_USER` e `REQ_ADMIN_PASS` solo se il fallback legacy è esplicitamente abilitato.

Default admin MVP:

- non più attivo per impostazione predefinita;
- usare utenti DB/LDAP/OIDC e ruoli RBAC.

## Installazione database MySQL

```sql
CREATE DATABASE requisiti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Poi importare in ordine:

```bash
mysql -u root requisiti < database/schema.sql
mysql -u root requisiti < database/seed_domande.sql
mysql -u root requisiti < database/seed_business_lines.sql
mysql -u root requisiti < database/seed_requisiti.sql
mysql -u root requisiti < database/seed_servizi.sql
mysql -u root requisiti < database/seed_regole.sql
```

## Avvio locale

Da dentro la cartella `webapp`:

```bash
php -S 127.0.0.1:8080
```

Poi aprire:

```text
http://127.0.0.1:8089/index.php
```

Se `APP_BASE_URL` è `/webapp`, servire la cartella padre dal web server. Se si usa il server integrato da dentro `webapp`, impostare:

```bash
REQ_APP_BASE_URL=
```

## Import JSON

I cataloghi possono essere ricaricati dai JSON già estratti:

```bash
php tools/import_json.php ../requisiti_data.json ../servizi_data.json
```

Lo script svuota e ricarica le tabelle `requisiti` e `servizi`, e rigenera le `regole_requisiti` dalla matrice `criteri` del JSON.

## Stato MVP

Già presente:

- UI base;
- admin login;
- CRUD minimo domande;
- lista requisiti e servizi;
- gestione manuale regole domanda -> requisito;
- gestione manuale regole domanda -> servizio;
- questionario con salvataggio risposte;
- calcolo risultati.

Da completare nella fase successiva:

- auth admin su database;
- gestione documenti;
- criteri normalizzati come layer separato dalle domande;
- import diretto dagli XLSX;
- export risultati in PDF/Excel;
- supporto PostgreSQL con DDL dedicato o migrazione framework.
