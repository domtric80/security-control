-- Seed: Regole requisiti generate da requisiti_data.json
INSERT INTO regole_requisiti (domanda_id, valore_atteso, requisito_id)
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM2-001.02' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM8-001.00' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM8-001.00' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM8-001.00' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM8-001.00' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM8-001.00' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM8-001.00' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AM8-001.00' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-RA1-002.00' WHERE d.codice = 'nuova_realizzazione'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-RA1-002.00' WHERE d.codice = 'modifica'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-RA1-002.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-RA1-002.00' WHERE d.codice = 'fornitori_critici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-RA4-003.00' WHERE d.codice = 'fornitori_critici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.00' WHERE d.codice = 'acc_adm_azienda'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.00' WHERE d.codice = 'acc_adm_cliente'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.00' WHERE d.codice = 'acc_privilegiati'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.00' WHERE d.codice = 'acc_auditor'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.00' WHERE d.codice = 'acc_utenti_base'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.00' WHERE d.codice = 'acc_sdo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.00' WHERE d.codice = 'acc_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.01' WHERE d.codice = 'acc_adm_azienda'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.01' WHERE d.codice = 'acc_adm_cliente'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.01' WHERE d.codice = 'acc_privilegiati'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.01' WHERE d.codice = 'acc_auditor'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.01' WHERE d.codice = 'acc_utenti_base'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.01' WHERE d.codice = 'acc_sdo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.01' WHERE d.codice = 'acc_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.02' WHERE d.codice = 'acc_adm_azienda'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.02' WHERE d.codice = 'acc_adm_cliente'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.02' WHERE d.codice = 'acc_privilegiati'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.02' WHERE d.codice = 'acc_auditor'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.02' WHERE d.codice = 'acc_utenti_base'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.02' WHERE d.codice = 'acc_sdo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC1-001.02' WHERE d.codice = 'acc_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC3-001.00' WHERE d.codice = 'usr_personale_for'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC3-001.00' WHERE d.codice = 'usr_interni'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC3-003.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_adm_azienda'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_adm_cliente'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_privilegiati'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_auditor'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_utenti_base'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_sdo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_sdo_imp'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.00' WHERE d.codice = 'acc_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_adm_azienda'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_adm_cliente'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_privilegiati'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_auditor'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_utenti_base'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_sdo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_sdo_imp'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-001.01' WHERE d.codice = 'acc_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.00' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.00' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.00' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.00' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.00' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.00' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.00' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.01' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.01' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.01' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.01' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.01' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.01' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.01' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.02' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.02' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.02' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.02' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.02' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.02' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.02' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.03' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.03' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.03' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.03' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.03' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.03' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-003.03' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC4-005.00' WHERE d.codice = 'acc_sdo_imp'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-001.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-002.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-003.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-003.01' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-004.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-005.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-005.01' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC5-005.02' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-001.00' WHERE d.codice = 'auth_nuovo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-001.00' WHERE d.codice = 'auth_locale'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.00' WHERE d.codice = 'auth_nuovo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.00' WHERE d.codice = 'auth_locale'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.01' WHERE d.codice = 'auth_nuovo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.01' WHERE d.codice = 'auth_locale'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.02' WHERE d.codice = 'auth_nuovo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.02' WHERE d.codice = 'auth_locale'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.03' WHERE d.codice = 'auth_nuovo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-002.03' WHERE d.codice = 'auth_locale'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_web_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_cli_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_api_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_app_mobile_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_app_mobile_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_app_desktop_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.00' WHERE d.codice = 'int_app_desktop_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_web_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_cli_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_api_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_app_mobile_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_app_mobile_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_app_desktop_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.01' WHERE d.codice = 'int_app_desktop_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_web_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_cli_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_api_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_app_mobile_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_app_mobile_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_app_desktop_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-003.02' WHERE d.codice = 'int_app_desktop_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_web_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_cli_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_api_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_app_mobile_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_app_mobile_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_app_desktop_int'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-004.00' WHERE d.codice = 'int_app_desktop_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-005.00' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-005.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-005.00' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-005.00' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-005.00' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_app_mobile_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.00' WHERE d.codice = 'int_app_desktop_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_app_mobile_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.01' WHERE d.codice = 'int_app_desktop_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_app_mobile_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.02' WHERE d.codice = 'int_app_desktop_pub'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_web_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_cli_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.03' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_web_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_cli_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-006.04' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-007.00' WHERE d.codice = 'auth_nuovo'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.00' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.00' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.00' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.01' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.01' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-008.01' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-009.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-009.00' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-009.00' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-010.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-010.00' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-010.00' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.00' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.00' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.01' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.01' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.01' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.02' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.02' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-011.02' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-012.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-012.00' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-012.00' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-013.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-013.00' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-013.00' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-014.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-014.00' WHERE d.codice = 'usr_personale_soc'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-014.00' WHERE d.codice = 'usr_personale_pa'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-014.00' WHERE d.codice = 'usr_personale_for'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-AC7-014.00' WHERE d.codice = 'usr_interni'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS1-001.00' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS1-001.00' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS1-002.00' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS1-002.00' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS1-003.00' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS1-003.00' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS1-003.00' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.01' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.01' WHERE d.codice = 'int_web_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.01' WHERE d.codice = 'int_web_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.01' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.01' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.01' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.02' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.02' WHERE d.codice = 'int_cli_usr_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.02' WHERE d.codice = 'int_cli_usr_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.02' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.02' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.02' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.03' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.03' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.03' WHERE d.codice = 'int_api_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.04' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.04' WHERE d.codice = 'int_api_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS2-001.04' WHERE d.codice = 'int_api_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.00' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.00' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.01' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.01' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.01' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.02' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.02' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-001.02' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-002.00' WHERE d.codice = 'usr_clienti_priv'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-003.00' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-003.00' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-003.00' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-003.00' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-003.00' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-003.00' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-DS5-003.00' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-002.00' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-002.02' WHERE d.codice = 'nuovi_infrastrutturali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-004.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-004.00' WHERE d.codice = 'int_web_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-004.00' WHERE d.codice = 'int_web_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-004.00' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-004.00' WHERE d.codice = 'int_cli_adm_privata'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP1-004.00' WHERE d.codice = 'int_cli_adm_interna'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.00' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.00' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.00' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.00' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.00' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.00' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.00' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.01' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.01' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.01' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.01' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.01' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.01' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.01' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.02' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.02' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.02' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.02' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.02' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.02' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.02' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.03' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.03' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.03' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.03' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.03' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.03' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.03' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.04' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.04' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.04' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.04' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.04' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.04' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.04' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.05' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.05' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.05' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.05' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.05' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.05' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-IP4-001.05' WHERE d.codice = 'dati_tecnici'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-PT3-002.00' WHERE d.codice = 'int_web_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-PT3-002.00' WHERE d.codice = 'int_web_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-PT3-002.00' WHERE d.codice = 'int_cli_usr_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-PT3-002.00' WHERE d.codice = 'int_cli_adm_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-PT3-002.00' WHERE d.codice = 'int_api_internet'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-CM1-001.03' WHERE d.codice = 'dati_pers_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-CM1-001.03' WHERE d.codice = 'dati_part_clienti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-CM1-001.03' WHERE d.codice = 'dati_pers_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-CM1-001.03' WHERE d.codice = 'dati_part_dipendenti'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-CM1-001.03' WHERE d.codice = 'dati_pers_fornitori'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-CM1-001.03' WHERE d.codice = 'dati_aziendali'
UNION ALL
SELECT d.id, '1', r.id FROM domande d JOIN requisiti r ON r.codice = 'SEC-CM4-001.00' WHERE d.codice = 'flusso_acquisizione';
