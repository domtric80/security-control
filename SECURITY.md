# Politica di sicurezza

## Versioni supportate

La versione presente sul branch `main` riceve gli aggiornamenti di sicurezza.
Le versioni precedenti non sono supportate, salvo indicazione esplicita in una
release.

## Segnalare una vulnerabilità

Non aprire issue pubbliche per vulnerabilità reali o sospette. Usa invece la
funzione **Report a vulnerability** nella scheda **Security** del repository:

<https://github.com/domtric80/security-control/security/advisories/new>

Includi, quando possibile:

- componente e versione interessati;
- descrizione dell'impatto e prerequisiti di sfruttamento;
- passaggi minimi per riprodurre il problema;
- proof of concept non distruttiva;
- mitigazioni o correzioni suggerite;
- eventuale CVE già assegnato.

Non inserire segreti, dati personali o dati di produzione nella segnalazione.
Usa dati sintetici e rimuovi token, password e identificativi sensibili dai log.

## Tempi di gestione

- conferma di ricezione: entro 3 giorni lavorativi;
- prima valutazione tecnica: entro 7 giorni lavorativi;
- aggiornamenti: almeno ogni 14 giorni fino alla risoluzione.

I tempi di correzione dipendono da gravità, sfruttabilità e disponibilità di una
mitigazione sicura. La pubblicazione coordinata avverrà dopo la distribuzione
della correzione o secondo una data concordata con il segnalante.

## Ambito e safe harbor

Sono consentiti test in buona fede su sistemi e dati propri, con il minimo
impatto necessario. Non sono autorizzati accesso a dati di terzi, persistenza,
social engineering, denial of service o alterazione/distruzione di dati.

Chi rispetta questa politica e segnala tempestivamente il problema sarà trattato
come ricercatore in buona fede. In caso di dubbio sull'ambito, interrompi il test
e usa il canale privato prima di proseguire.
