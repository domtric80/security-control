# Web application per questionario, requisiti, servizi e documenti

## Obiettivo

Sostituire il flusso basato su Excel con una web application manutenibile, in cui:

- le domande sono censite a database con identificativo stabile;
- ogni questionario compilato viene salvato con tutte le risposte;
- le risposte attivano criteri/fattori decisionali;
- criteri e risposte determinano requisiti, servizi e documenti da estrarre;
- l'amministratore può modificare domande, opzioni e relazioni senza intervenire sul codice.

## Lettura dei file attuali

### `Questionario v1.8.xlsx`

Contiene il questionario utente e una sintesi calcolata.

Fogli principali:

- `Informazioni preliminari`: anagrafica progetto/servizio.
- `Ulteriori informazioni`: caratteristiche operative, deadline, RPO/RTO e stime.
- `Trattamento dei dati`: tipologie di dati trattati.
- `Dettaglio delle funzionalità`: funzionalità, utenti, esposizioni, canali.
- `Informazioni architetturali`: elementi infrastrutturali e architetturali.
- `Gestione utenti e accessi`: autenticazione, AD, utenze e anagrafiche.
- `Sintesi questionario`: matrice/fattori calcolati usati dal selettore.
- `Elenchi di risposte`: liste di appoggio per le risposte.

### `Selettore requisiti 20260422.xlsx`

Contiene il catalogo requisiti e la matrice di applicabilità.

Dati rilevati:

- 147 requisiti nel foglio `Requisiti`.
- ID requisiti in colonna `ID`, es. `SEC-AM2-001.00`.
- colonne descrittive: categoria, subcategoria, versione, titolo, descrizione, contesto, note, importanza, STD, owner.
- colonne di mapping da `Da valutare manualmente`, `TLT`, `Nuova realizzazione`, dati trattati, target utenti, canali, interfacce, utenze, autenticazione.
- le celle con `x` rappresentano la relazione tra criterio/fattore e requisito.

### `Servizi_SEC_26_01_2026 - Share.xlsx`

Contiene il catalogo servizi da collegare a criteri e requisiti.

Foglio principale:

- `Merged_list`, con intestazioni alla riga 6.

Campi principali:

- reparto owner;
- Canone/CI;
- portfolio category;
- macro services;
- categoria;
- servizio elementare;
- descrizione;
- tipo attività ordinarie;
- misurabilità output;
- commessa;
- check component;
- asset primario;
- software;
- orario di servizio;
- note.

## Concetto chiave: separare domande, risposte e criteri

Nel file Excel oggi la logica è distribuita tra formule e colonne di mapping. Nella web app conviene separarla in tre livelli:

1. **Domande**: ciò che l'utente vede.
2. **Risposte**: ciò che l'utente seleziona o scrive.
3. **Criteri**: segnali normalizzati usati dal motore di valutazione.

Esempio:

- domanda: "Il progetto tratta dati personali comuni dei clienti?"
- risposta: "Sì"
- criterio attivato: `DATI_PERSONALI_COMUNI_CLIENTI`
- requisiti collegati: tutti i requisiti mappati a quel criterio.
- servizi collegati: tutti i servizi mappati a quel criterio o ai requisiti risultanti.

Questa separazione evita che il testo di una domanda diventi una chiave tecnica fragile. L'applicativo lavora per ID/codici stabili.

## Modello funzionale

### Area utente

- Creazione nuovo questionario.
- Salvataggio bozza.
- Compilazione guidata per sezioni.
- Validazione risposte obbligatorie.
- Invio finale.
- Visualizzazione risultato:
  - requisiti applicabili;
  - requisiti da valutare manualmente;
  - servizi suggeriti;
  - documenti/link estratti;
  - criteri che hanno generato ogni risultato.

### Area amministrativa

- Gestione sezioni del questionario.
- Gestione domande:
  - codice stabile;
  - testo;
  - tipo risposta;
  - obbligatorietà;
  - ordine;
  - stato attivo/non attivo.
- Gestione opzioni di risposta.
- Gestione criteri decisionali.
- Mapping opzioni/risposte -> criteri.
- Import e gestione requisiti.
- Import e gestione servizi.
- Mapping criteri -> requisiti.
- Mapping criteri -> servizi.
- Mapping requisiti -> servizi.
- Gestione documenti/link associati a requisiti e servizi.
- Simulatore admin: selezionare risposte e vedere requisiti/servizi risultanti prima di pubblicare modifiche.

## Motore di valutazione

Il motore lavora in tre passaggi.

### 1. Risposte -> criteri

Per ogni risposta salvata:

- se la risposta è a scelta singola/multipla, si leggono le relazioni `opzione -> criterio`;
- se la risposta è libera/numerica/data, si applicano regole condizionali;
- i criteri attivati vengono salvati come snapshot del questionario.

### 2. Criteri -> requisiti

Un requisito può essere:

- `applicabile`: almeno una regola applicabile è soddisfatta;
- `da_valutare`: esiste una relazione di tipo manuale;
- `non_applicabile`: nessuna regola soddisfatta.

Per compatibilità iniziale con l'Excel, la relazione `x` della matrice può essere importata come criterio che rende applicabile il requisito con logica `ANY`.

### 3. Criteri/requisiti -> servizi/documenti

I servizi possono essere collegati in due modi:

- direttamente ai criteri, quando una risposta implica un servizio;
- ai requisiti, quando un servizio serve per soddisfare o supportare un requisito.

I documenti possono essere collegati a:

- requisiti;
- servizi;
- criteri;
- questionari generati.

## Scelta stack consigliata

Per una prima versione PHP:

- backend: Laravel o Symfony;
- database consigliato: PostgreSQL;
- autenticazione admin: ruoli `admin`, `editor`, `viewer`;
- frontend iniziale: Blade/Twig server-side, poi eventualmente Vue/React se serve più interattività;
- import Excel: job amministrativo che legge i workbook e popola tabelle di staging prima della pubblicazione.

PostgreSQL è preferibile perché gestisce bene JSONB, snapshot e query di audit. MySQL resta possibile se si evitano funzionalità troppo spinte su JSON.

## Tabelle principali

Il database deve contenere almeno:

- `question_sections`;
- `questions`;
- `question_options`;
- `criteria`;
- `question_option_criteria`;
- `answer_criteria_rules`;
- `questionnaires`;
- `questionnaire_answers`;
- `questionnaire_answer_options`;
- `questionnaire_criteria`;
- `requirements`;
- `requirement_criteria`;
- `services`;
- `service_criteria`;
- `service_requirements`;
- `documents`;
- `requirement_documents`;
- `service_documents`;
- `questionnaire_requirement_results`;
- `questionnaire_service_results`.

## Regola importante per la manutenibilità

Le relazioni operative non devono basarsi sul testo visibile all'utente.

Usare sempre:

- `question.code` per le domande;
- `question_options.code` per le opzioni;
- `criteria.code` per i criteri;
- `requirements.requirement_code` per i requisiti;
- `services.service_code` per i servizi.

Il testo può cambiare. Il codice stabile no.

## Strategia di migrazione dagli Excel

### Fase 1 - Import cataloghi

- Importare i requisiti da `Selettore requisiti 20260422.xlsx`, foglio `Requisiti`.
- Importare i criteri dalle colonne di mapping del foglio `Requisiti` e dal foglio `Sintesi questionario`.
- Importare la matrice `x` in `requirement_criteria`.
- Importare i servizi da `Servizi_SEC_26_01_2026 - Share.xlsx`, foglio `Merged_list`.

### Fase 2 - Ricostruzione questionario

- Censire sezioni e domande partendo dai fogli del questionario.
- Assegnare codici stabili alle domande.
- Censire opzioni di risposta.
- Collegare ogni opzione ai criteri corrispondenti.

### Fase 3 - Mapping servizi

- Collegare i servizi ai criteri quando la risposta determina direttamente il servizio.
- Collegare i servizi ai requisiti quando il servizio serve a soddisfare/supportare un requisito.
- Consentire la gestione manuale da pannello admin.

### Fase 4 - Risultati storicizzati

Quando un questionario viene inviato, salvare:

- risposte;
- criteri attivati;
- requisiti risultanti;
- servizi risultanti;
- documenti risultanti;
- snapshot testuale dei dati principali.

Questo impedisce che modifiche future alle regole cambino retroattivamente i risultati storici.

## Primo MVP consigliato

1. Import requisiti e servizi.
2. CRUD admin per domande/opzioni/criteri.
3. Mapping opzioni -> criteri.
4. Mapping criteri -> requisiti.
5. Mapping criteri/requisiti -> servizi.
6. Compilazione questionario.
7. Salvataggio risposte.
8. Calcolo risultati e pagina export.

## Query logiche principali

### Requisiti applicabili da un questionario

```sql
SELECT
    r.requirement_code,
    r.title,
    r.importance,
    jsonb_agg(DISTINCT c.code) AS matched_criteria
FROM requirements r
JOIN requirement_criteria rc
    ON rc.requirement_id = r.id
   AND rc.relation = 'include'
JOIN questionnaire_criteria qc
    ON qc.criterion_id = rc.criterion_id
   AND qc.value = true
JOIN criteria c
    ON c.id = qc.criterion_id
WHERE qc.questionnaire_id = :questionnaire_id
  AND r.is_active = true
GROUP BY r.id
ORDER BY r.importance, r.requirement_code;
```

### Servizi suggeriti direttamente dalle risposte

```sql
SELECT
    s.service_code,
    s.elementary_service,
    s.owner_department,
    jsonb_agg(DISTINCT c.code) AS matched_criteria
FROM services s
JOIN service_criteria sc
    ON sc.service_id = s.id
   AND sc.relation = 'include'
JOIN questionnaire_criteria qc
    ON qc.criterion_id = sc.criterion_id
   AND qc.value = true
JOIN criteria c
    ON c.id = qc.criterion_id
WHERE qc.questionnaire_id = :questionnaire_id
  AND s.is_active = true
GROUP BY s.id
ORDER BY s.owner_department, s.elementary_service;
```

### Servizi suggeriti tramite requisiti applicabili

```sql
SELECT DISTINCT
    s.service_code,
    s.elementary_service,
    s.owner_department,
    r.requirement_code
FROM questionnaire_requirement_results qrr
JOIN service_requirements sr
    ON sr.requirement_id = qrr.requirement_id
   AND sr.relation = 'include'
JOIN services s
    ON s.id = sr.service_id
JOIN requirements r
    ON r.id = qrr.requirement_id
WHERE qrr.questionnaire_id = :questionnaire_id
  AND qrr.status = 'applicable'
  AND s.is_active = true
ORDER BY s.owner_department, s.elementary_service;
```

### Documenti da estrarre

```sql
SELECT DISTINCT
    d.document_code,
    d.title,
    d.document_type,
    d.storage_path,
    d.external_url
FROM documents d
LEFT JOIN requirement_documents rd
    ON rd.document_id = d.id
LEFT JOIN questionnaire_requirement_results qrr
    ON qrr.requirement_id = rd.requirement_id
   AND qrr.questionnaire_id = :questionnaire_id
   AND qrr.status IN ('applicable', 'manual_review')
LEFT JOIN service_documents sd
    ON sd.document_id = d.id
LEFT JOIN questionnaire_service_results qsr
    ON qsr.service_id = sd.service_id
   AND qsr.questionnaire_id = :questionnaire_id
   AND qsr.status IN ('applicable', 'manual_review')
WHERE d.is_active = true
  AND (qrr.id IS NOT NULL OR qsr.id IS NOT NULL)
ORDER BY d.document_type, d.title;
```

## Nota sui documenti

La richiesta parla di "estrarre i documenti". Per questo è utile modellare i documenti come entità autonoma, non come semplice colonna del requisito.

Un documento può essere:

- un file caricato;
- un link;
- una procedura;
- una scheda tecnica;
- una policy;
- una checklist.

Poi può essere collegato a requisiti, servizi o criteri.
