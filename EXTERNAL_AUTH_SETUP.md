# Integrazione LDAP e OIDC/Keycloak

## LDAP

LDAP è disattivato di default. Puoi configurarlo da **Gestione → Autenticazione**.
Le variabili ambiente restano disponibili come fallback:

```env
REQ_LDAP_ENABLED=true
REQ_LDAP_HOST=ldap.example.local
REQ_LDAP_PORT=636
REQ_LDAP_ENCRYPTION=ldaps
REQ_LDAP_PROTOCOL_VERSION=3
REQ_LDAP_URI=
REQ_LDAP_BASE_DN=DC=example,DC=local
REQ_LDAP_BIND_DN=CN=svc-requisiti,OU=Service Accounts,DC=example,DC=local
REQ_LDAP_BIND_PASSWORD=...
REQ_LDAP_USER_FILTER=(sAMAccountName={username})
REQ_LDAP_DEFAULT_ROLE=utente
```

`REQ_LDAP_URI` è opzionale e sovrascrive host/porta/cifratura se valorizzato.
Valori cifratura ammessi: `none`, `starttls`, `ldaps`.
Il filtro utente deve sempre contenere `{username}`. Esempi validi: `(uid={username})`, `(sAMAccountName={username})`, `(&(objectClass=person)(uid={username}))`.
Non usare filtri generici come `(objectClass=*)`, perché il login deve trovare un solo DN utente.

Da **Gestione → Autenticazione** puoi usare **Test connessione LDAP**:
- verifica connessione verso host/porta o URI avanzata;
- applica la versione protocollo configurata;
- verifica StartTLS se selezionato;
- esegue bind con DN/password di servizio o bind anonimo;
- prova a leggere il Base DN se configurato.

Sempre da **Gestione → Autenticazione** puoi usare **Cerca utente LDAP**:
- salva prima i valori correnti del form;
- applica il filtro configurato sostituendo `{username}`;
- mostra filtro effettivo, DN trovato e attributi mappati;
- aiuta a distinguere errori di connessione/bind da errori di Base DN, filtro o attributi.

Attributi configurabili:

```env
REQ_LDAP_ATTR_USERNAME=sAMAccountName
REQ_LDAP_ATTR_EMAIL=mail
REQ_LDAP_ATTR_FIRST_NAME=givenName
REQ_LDAP_ATTR_LAST_NAME=sn
```

Note:
- usare preferibilmente `ldaps://`;
- dopo la modifica del `Dockerfile` serve ricostruire il container per installare l'estensione PHP `ldap`;
- il primo login LDAP crea automaticamente l'utente locale e gli assegna il ruolo `REQ_LDAP_DEFAULT_ROLE`.

## OIDC / Keycloak

OIDC è disattivato di default. Puoi configurarlo da **Gestione → Autenticazione**.
Le variabili ambiente restano disponibili come fallback. Per Keycloak:

```env
REQ_OIDC_ENABLED=true
REQ_OIDC_ISSUER=https://keycloak.example.local/realms/NOME_REALM
REQ_OIDC_CLIENT_ID=requisiti-sec
REQ_OIDC_CLIENT_SECRET=...
REQ_OIDC_REDIRECT_URI=https://app.example.local/auth/oidc_callback.php
REQ_OIDC_DEFAULT_ROLE=utente
```

Configurazione client Keycloak:
- Client type: `OpenID Connect`;
- Access type: confidential;
- Valid redirect URI: `https://app.example.local/auth/oidc_callback.php`;
- Standard flow: enabled;
- Scope minimo: `openid profile email`.

Il primo login OIDC crea automaticamente l'utente locale usando:
- `sub` come identificativo esterno;
- `preferred_username` o `email` come username;
- `given_name`, `family_name`, `email` per l'anagrafica;
- ruolo default `REQ_OIDC_DEFAULT_ROLE`.

Da **Gestione → Autenticazione** puoi usare **Test connessione OIDC**:
- verifica la discovery `.well-known/openid-configuration`;
- controlla che l'issuer restituito coincida;
- controlla la presenza di authorization endpoint, token endpoint, userinfo endpoint e JWKS URI;
- segnala se manca il Client ID.

## Sicurezza

- In produzione usare HTTPS e `REQ_SESSION_SECURE=true`.
- Ruoli e permessi restano gestiti localmente dalla RBAC applicativa.
- Gli utenti esterni sono tracciati in `utenti.auth_provider` e `utenti.external_id`.
