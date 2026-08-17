# Backlog funzionalità IA

## Obiettivo
Integrare la IA come supporto operativo all'analista, mantenendo sempre controllo umano, tracciabilità e possibilità di approvare, modificare o scartare ogni suggerimento prima che produca effetti sul database.

## Epic 0 - Affidabilità IA
- TASK 0.1 - Semaforo IA globale: mostrare in alto se il provider IA è raggiungibile e se sono presenti modelli utilizzabili.
- TASK 0.2 - Pagina diagnostica IA: test endpoint, lista modelli, generazione breve, ultimo errore e tempo risposta.
- TASK 0.3 - Log esecuzioni IA: salvare richiesta, provider, modello, durata, stato, errore e utente.
- TASK 0.4 - Limiti prompt/output: mostrare dimensione contesto, token stimati e warning se il prompt è troppo grande.
- TASK 0.5 - Timeout e retry controllati: evitare blocchi lunghi e rendere esplicito quando la IA non è affidabile.

## Epic 1 - Suggerimento requisiti specifici
- TASK 1.1 - Estrarre dalla Threat Analysis possibili requisiti specifici in formato strutturato.
- TASK 1.2 - Creare pagina “Suggerimenti IA” per approvare, modificare o scartare le proposte.
- TASK 1.3 - Collegare i requisiti approvati al questionario, task JIRA e analista.
- TASK 1.4 - Rilevare duplicati o requisiti simili già presenti tra requisiti specifici e catalogo.
- TASK 1.5 - Salvare motivazione IA e decisione dell'analista per audit.

## Epic 2 - Rilevazione falsi positivi
- TASK 2.1 - Analizzare requisiti assegnati e risposte che li hanno attivati.
- TASK 2.2 - Produrre un punteggio di confidenza e una motivazione.
- TASK 2.3 - Evidenziare a video requisiti potenzialmente falsi positivi.
- TASK 2.4 - Consentire conferma, esclusione o richiesta di approfondimento.
- TASK 2.5 - Usare il feedback per migliorare le regole.

## Epic 3 - Spiegazione risultati
- TASK 3.1 - Generare una spiegazione per ogni requisito assegnato.
- TASK 3.2 - Mostrare collegamento tra domanda, risposta, regola e requisito.
- TASK 3.3 - Aggiungere spiegazioni negli export PDF/XLS/CSV/Confluence.
- TASK 3.4 - Salvare versione della spiegazione usata nel report.

## Epic 4 - Supporto PIR
- TASK 4.1 - Analizzare note PIR, stato requisito, applicazione e rientro/eccezione.
- TASK 4.2 - Suggerire requisiti in stato KO/parziale o con note insufficienti.
- TASK 4.3 - Proporre rischi residui ed evidenze mancanti.
- TASK 4.4 - Generare bozza sezione report PIR sui requisiti non soddisfatti.
- TASK 4.5 - Consentire accettazione/modifica/scarto dei suggerimenti.

## Epic 5 - Report executive
- TASK 5.1 - Generare sintesi manageriale non tecnica del questionario.
- TASK 5.2 - Generare sintesi manageriale della PIR.
- TASK 5.3 - Evidenziare rischi principali, decisioni richieste e prossimi passi.
- TASK 5.4 - Inserire la sintesi negli export PDF.

## Epic 6 - Qualità questionario
- TASK 6.1 - Segnalare risposte mancanti, incoerenti o ambigue.
- TASK 6.2 - Suggerire domande di chiarimento per l'analista.
- TASK 6.3 - Bloccare solo le valutazioni critiche, non la compilazione.
- TASK 6.4 - Salvare check qualità e stato di revisione.

## Epic 7 - Mapping servizi
- TASK 7.1 - Spiegare perché un servizio è stato associato al questionario.
- TASK 7.2 - Suggerire servizi mancanti in base alle risposte.
- TASK 7.3 - Proporre modifiche alle regole servizi.
- TASK 7.4 - Gestire approvazione umana prima di modificare regole o risultati.

## Epic 8 - Normalizzazione requisiti specifici
- TASK 8.1 - Calcolare similarità tra nuovo requisito e requisiti esistenti.
- TASK 8.2 - Mostrare possibili duplicati prima del salvataggio.
- TASK 8.3 - Proporre merge o riuso di un requisito specifico già esistente.
- TASK 8.4 - Supportare promozione da specifico a catalogo con tracciabilità.

## Sequenza consigliata
1. Epic 0 - Affidabilità IA.
2. Epic 1 - Suggerimento requisiti specifici.
3. Epic 6 - Qualità questionario.
4. Epic 2 e 3 - Falsi positivi e spiegazione risultati.
5. Epic 7 - Mapping servizi.
6. Epic 4 - Supporto PIR.
7. Epic 5 - Report executive.
8. Epic 8 - Normalizzazione avanzata.

## Stato implementazione
- Implementato: semaforo IA globale con cache e link configurazione.
- Implementato: anagrafica provider IA con test connessione e generazione breve.
- Implementato: storico run IA su database con prompt, contesto, risposta, modello, provider, durata, stato ed errore.
- Implementato: pagina unica “Suggerimenti IA” per lanciare tutte le analisi previste.
- Implementato: salvataggio suggerimenti strutturati con stato proposto, approvato, scartato o applicato.
- Implementato: applicazione automatica dei requisiti specifici suggeriti dalla IA.
- Implementato: applicazione automatica dei falsi positivi tramite esclusione requisito.
- Implementato: moduli IA per requisiti specifici, falsi positivi, spiegazioni risultati, qualità questionario, mapping servizi, supporto PIR, report executive e normalizzazione.
- Implementato: collegamenti rapidi da risultati questionario e PIR.
- Da rifinire: export PDF/XLS/Confluence dei contenuti IA approvati.
- Da rifinire: UI dedicata per confrontare duplicati requisiti con evidenza visuale.
- Da rifinire: job asincroni/background per analisi IA molto lunghe.
