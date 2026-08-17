# Security Audit OWASP / OSSTMM

Data verifica: 2026-06-15

Documentazione usata:
- `F:/OwnCloud/SHARE/SYSADM/CYBER/OWASP_Testing_Guide_v4.pdf`
- `F:/OwnCloud/SHARE/SYSADM/CYBER/OSSTMM.3.pdf`
- OWASP Cheat Sheets: Session Management, Authentication, CSRF Prevention.

## Scope

Verifica mirata dell'app PHP/MySQL Docker su:
- autenticazione e gestione sessioni;
- autorizzazione RBAC;
- CSRF;
- input/output handling;
- upload e allegati PIR;
- URL fetching per recupero titoli;
- configurazione HTTP di base.

## Esito sintetico

| Area | Stato | Evidenza |
| --- | --- | --- |
| Password storage | OK | `password_hash()` / `password_verify()` |
| CSRF | OK | Token random per sessione e verifica sui POST |
| Session fixation | OK | `session_regenerate_id(true)` dopo login |
| Session cookie | OK locale / da forzare in prod | `HttpOnly`, `SameSite=Lax`, `Secure` configurabile |
| Logout | OK | Distruzione sessione e cookie |
| Idle timeout | OK | Timeout inattività default 30 minuti |
| Login throttling | OK | 5 errori / 15 minuti per username + IP |
| RBAC | OK | Controlli CRUD lato server per funzioni applicative |
| Link preview SSRF | Migliorato | Blocca URL private/reserved/localhost e richiede auth PIR |
| Upload allegati | Migliorato | Allowlist estensioni/MIME, limite dimensione, blocco script upload |
| Security headers | Parziale | Header base presenti; CSP non ancora restrittiva |

## Fix applicati durante audit

- Hardening cookie/sessione in `webapp/config/config.php`.
- Header base: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Tabella `auth_login_attempts` e throttling login.
- Logout completo con invalidazione cookie.
- Password policy minima su nuovi utenti/cambio password: 12 caratteri, maiuscola, minuscola, numero.
- Protezione anti-SSRF su URL allegati e recupero titolo.
- Endpoint `pir/link_title.php` protetto da permesso `pir:read`.
- Upload PIR limitato per dimensione, estensione e MIME reale.
- `.htaccess` in `public/uploads/pir` per impedire listing/esecuzione script.

## Test eseguiti

- `PHP_LINT_OK` su tutti i file PHP.
- `SECURITY_AUTH_FLOW_OK`: login, cookie sessione, logout e blocco accesso post-logout.
- `LOGIN_THROTTLE_OK`: blocco dopo tentativi falliti.
- `OWASP_FIX_VALIDATION_OK`: anti-SSRF su localhost/file scheme e accesso anonimo a link title.
- `AUTH_AND_FULL_LINT_OK`: flusso autenticato e lint finale.

## Rischi residui e prossimi step

1. **HTTPS obbligatorio in produzione**
   - Impostare `REQ_SESSION_SECURE=true`.
   - Esporre l'app solo dietro TLS.

2. **Admin tecnico bootstrap**
   - Sostituire l'admin da variabili Docker con un utente DB Admin.
   - Rimuovere o disabilitare `REQ_ADMIN_USER` / `REQ_ADMIN_PASS` in produzione.

3. **CSP**
   - Attualmente ci sono CDN e handler inline che impediscono una CSP molto rigida.
   - Migrare JS inline/event handler in file statici e poi applicare CSP restrittiva.

4. **Upload fuori webroot**
   - Il fix attuale riduce il rischio, ma la soluzione più robusta è spostare allegati fuori da `public/` e servirli tramite controller autorizzato.

5. **Audit log**
   - Aggiungere log applicativo per login, logout, cambio ruoli, modifica requisiti/PIR, export e upload.

6. **MFA**
   - Raccomandato per ruoli `manager` e `admin`.

7. **Password policy avanzata**
   - Valutare controllo password compromesse, scadenza opzionale per account privilegiati e reset password sicuro.

8. **Verifica dinamica**
   - Eseguire test con OWASP ZAP/Burp in ambiente staging.
   - Validare IDOR/BOLA su questionari, PIR, allegati e export.

## Nota

Questa verifica aumenta sensibilmente il livello di sicurezza, ma non equivale a una certificazione o penetration test completo. Per esposizione internet o dati sensibili reali serve un ciclo di test dinamico e revisione infrastrutturale.
