# Webapp

Questa directory contiene l'applicazione PHP di Security Control.

## Moduli

- `admin/`: gestione domande, requisiti, servizi, regole, utenti, ruoli, autenticazione e provider AI.
- `questionario/`: creazione, compilazione, import, risultati ed export dei questionari.
- `pir/`: Post Implementation Review, riunioni, allegati, report PDF ed eccezioni di sicurezza.
- `threat_analysis/`: generazione, normalizzazione ed export delle analisi AI.
- `ai/`: suggerimenti AI operativi su questionari, requisiti, servizi e PIR.
- `auth/`: flussi OIDC.
- `database/`: schema, seed e migrazioni MySQL.
- `includes/`: configurazione, connessione DB e funzioni condivise.

## Runtime

Il runtime consigliato è Docker Compose dalla root del repository:

```bash
docker compose up -d --build
```

L'applicazione usa PHP 8.3 su Apache e MySQL 8.x. Le configurazioni runtime sono lette da variabili ambiente `REQ_*` e, per alcune integrazioni, dal pannello amministrativo.

## Database

Per installazioni MySQL pulite, Docker esegue automaticamente:

1. `database/schema.sql`
2. seed base in `database/seed_*.sql`
3. migrazioni in `database/migrations/*.sql`

Gli script in `database/migrations` devono essere idempotenti o sicuri su installazioni esistenti.

## Tool CLI

Gli script in `tools/` sono destinati all'esecuzione da CLI nel container applicativo, ad esempio:

```bash
docker compose exec app php tools/smoke_test.php
```

L'accesso web a `tools/` è bloccato a livello Apache.
