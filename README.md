# WhereAreYou2 - Moodle Plugin

Un plugin moderno per Moodle 5+ che mostra una modal post-login per permettere agli utenti di specificare il proprio dipartimento e posizione.

## Caratteristiche

- ✅ Modal automatica dopo ogni login (sempre visibile)
- ✅ Campi Department e Position personalizzabili
- ✅ Salvataggio in campi personalizzati utente
- ✅ Compatibile con Moodle 5+
- ✅ Utilizza il nuovo sistema di hook (niente callback legacy)
- ✅ Design moderno e responsive
- ✅ Supporto multilingua (IT/EN)

## Installazione

1. Copia il plugin in `$MOODLE/local/whereareyou`
2. Accedi come amministratore
3. Vai in Site administration > Notifications
4. Completa l'installazione

Il plugin creerà automaticamente:
- Categoria "Dove Sei Tu" nei campi profilo
- Campo personalizzato "Department" con opzioni: Pizzicaroli, Gesmundo, Remoto
- Campo personalizzato "Position" con opzioni: Preside, Insegnante, Alunno

## Funzionamento

- La modal appare automaticamente dopo ogni login
- L'utente deve selezionare dipartimento e posizione
- Due pulsanti disponibili: "Salva" e "Logout"
- I dati vengono salvati nei campi personalizzati del profilo
- La modal è sempre visibile (anche se i campi sono già compilati)

## Requisiti

- Moodle 5.0+
- PHP 8.0+
- JavaScript abilitato

## Supporto

Per problemi o domande, consulta la documentazione di Moodle o contatta l'amministratore del sistema.
