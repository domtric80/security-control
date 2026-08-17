# Security Control

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?logo=bootstrap&logoColor=white)
![Auth](https://img.shields.io/badge/Auth-Local%20%7C%20LDAP%20%7C%20OIDC-2ea44f)
![Release](https://img.shields.io/github/v/release/domtric80/security-control?include_prereleases)

Security Control è una web application per trasformare questionari di sicurezza, cataloghi requisiti e servizi associati in un processo tracciabile, amministrabile e versionato.

L'applicazione consente di compilare questionari, calcolare requisiti e servizi applicabili, gestire requisiti specifici di progetto, condurre Post Implementation Review, tracciare eccezioni di sicurezza e usare provider AI per supportare analisi e reportistica.

## Funzionalità principali

- **Questionari dinamici**: creazione, modifica, compilazione, import XLSX e salvataggio storico delle risposte.
- **Motore regole**: associazione di requisiti e servizi tramite gruppi logici `AND`/`OR` configurabili da pannello amministrativo.
- **Catalogo requisiti**: gestione requisiti catalogo, standard, categorie, versioning e snapshot dei requisiti assegnati.
- **Requisiti specifici**: requisiti creati per singolo progetto, collegabili ai questionari e promuovibili a catalogo.
- **Servizi associati**: catalogo servizi, mapping da risposte e gestione override manuali.
- **PIR**: Post Implementation Review per progetto con riunioni, partecipanti, allegati, stato requisiti e report PDF.
- **Eccezioni di sicurezza**: gestione eccezioni da PIR o manuali, data di rientro, approvatore e calendario scadenze.
- **AI assistant**: integrazione con Ollama o provider OpenAI-compatible per Threat Analysis, suggerimenti e report executive.
- **Export**: CSV, XLS, PDF e formato sorgente compatibile con tabelle Confluence.
- **RBAC**: ruoli e permessi CRUD per funzioni applicative.

## Stack tecnologico

| Area | Tecnologia |
| --- | --- |
| Backend | PHP 8.3, PDO |
| Database | MySQL 8.x; schema PostgreSQL disponibile come riferimento |
| Frontend | HTML server-rendered, Bootstrap 5, JavaScript vanilla |
| Runtime | Docker Compose, Apache PHP image |
| Autenticazione | Utenti locali, LDAP, OIDC/Keycloak-compatible |
| AI | Ollama locale o API OpenAI-compatible |
| Export | CSV/XLS HTML, PDF applicativi |

## Avvio rapido con Docker

```bash
docker compose up -d --build
```

Endpoint locali:

- Applicazione: `http://127.0.0.1:8089`
- Adminer: `http://127.0.0.1:8090`

Adminer usa questi parametri nel profilo Docker di default:

- Sistema: `MySQL`
- Server: `db`
- Database: `requisiti`
- Utente: `requisiti`
- Password: `requisiti`

Il fallback admin legacy è disabilitato per impostazione predefinita. La gestione degli accessi deve passare da utenti locali, LDAP oppure OIDC con ruoli RBAC.

## Configurazione

Le impostazioni principali sono configurabili tramite variabili ambiente nel `docker-compose.yml` oppure nel pannello amministrativo quando previsto:

- `REQ_DB_*`: connessione database.
- `REQ_LDAP_*`: integrazione LDAP, porta, protocollo e cifratura.
- `REQ_OIDC_*`: integrazione OIDC/Keycloak-compatible.
- `REQ_OLLAMA_*`: endpoint e modello Ollama di default.
- `REQ_LEGACY_ADMIN_ENABLED`: abilita solo in emergenza locale il fallback admin legacy.
- `REQ_SESSION_IDLE_SECONDS`: timeout inattività sessione.
- `REQ_UPLOAD_MAX_BYTES`: limite massimo upload PIR.

## Struttura progetto

```text
.
├── docker/                  # Dockerfile PHP/Apache
├── docker-compose.yml       # Stack locale app + MySQL + Adminer
├── webapp/                  # Applicazione PHP
│   ├── admin/               # Pannelli amministrativi
│   ├── ai/                  # Suggerimenti AI
│   ├── auth/                # Flussi OIDC
│   ├── database/            # Schema, seed e migrazioni
│   ├── includes/            # Configurazione, DB e funzioni applicative
│   ├── pir/                 # PIR, report ed eccezioni
│   ├── questionario/        # Questionari, risultati, import/export
│   └── threat_analysis/     # Generazione e gestione Threat Analysis
└── schema_postgres.sql      # Schema PostgreSQL di riferimento
```

## Sicurezza applicativa

- Cookie sessione `HttpOnly`, `SameSite=Lax` e `Secure` quando la richiesta è HTTPS.
- CSRF token sui form mutativi.
- Query DB tramite prepared statement PDO.
- Throttling dei tentativi di login.
- Porte Docker pubblicate solo su `127.0.0.1`.
- Tool manutentivi bloccati da web server e pensati per uso CLI.
- Upload PIR con estensioni/MIME allowlist e blocco esecuzione PHP lato Apache.
- Export CSV/XLS protetti da formula injection.
- Output HTML della Threat Analysis sanitizzato con allowlist di tag e attributi.
- Chiamate server-side verso provider IA limitate a host/reti configurati e senza redirect HTTP automatici.

## Sicurezza GitHub

Il repository include workflow GitHub Actions per:

- lint sintattico dei file PHP;
- validazione `docker compose config`;
- build dell'immagine PHP/Apache;
- analisi statica CodeQL per workflow GitHub Actions;
- analisi statica Semgrep per PHP, security audit e OWASP Top 10;
- aggiornamenti Dependabot per GitHub Actions e immagini Docker.

## Comandi utili

```bash
docker compose logs -f app
docker compose logs -f db
docker compose exec app php tools/smoke_test.php
docker compose down
```

Ricreazione completa del database locale:

```bash
docker compose down -v
docker compose up -d --build
```

## Stato release

La prima release pubblica è `v0.1.0` e contiene la baseline applicativa sanitizzata per pubblicazione open source.
