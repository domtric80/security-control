# Security Control

Web application PHP/MySQL per gestire questionari di sicurezza, requisiti, servizi, PIR, eccezioni e supporto AI locale/compatibile.

## Avvio rapido

```bash
docker compose up -d --build
```

- Applicazione: http://127.0.0.1:8089
- Adminer: http://127.0.0.1:8090

Il fallback admin legacy ? disabilitato di default. Usare utenti DB, LDAP oppure OIDC e ruoli RBAC.

## Componenti principali

- Questionari dinamici e import XLSX.
- Calcolo requisiti/servizi tramite regole amministrabili.
- PIR con stati requisito, riunioni, allegati ed eccezioni sicurezza.
- Versioning requisiti e snapshot dei requisiti assegnati.
- Integrazione LDAP/OIDC e configurazione provider AI.
- Export CSV, XLS, PDF e formato Confluence.

## Note sicurezza

- Le porte Docker sono pubblicate solo su `127.0.0.1`.
- Gli upload PIR sono protetti da esecuzione PHP lato Apache.
- Gli script in `webapp/tools` sono bloccati via web e pensati per uso CLI.
- I file sorgenti locali e i dataset privati sono esclusi da `.gitignore` e `.dockerignore`.
