# WhereAreYou - Moodle Plugin

Un plugin moderno per Moodle 5+ che mostra una modal post-login per permettere agli utenti di specificare il proprio dipartimento e posizione.

## Caratteristiche

- Modal automatica dopo ogni login (sempre visibile)
- Campi Department e Position personalizzabili
- Salvataggio in campi personalizzati utente
- Compatibile con Moodle 5+
- Utilizza il nuovo sistema di hook (niente callback legacy)
- Design moderno e responsive
- Supporto multilingua (IT/EN)
- **Pagina di test per amministratori**

## Installazione

1. Copia il plugin in `$MOODLE/local/whereareyou`
2. Accedi come amministratore
3. Vai in Site administration > Notifications
4. Completa l'installazione
5. **IMPORTANTE**: Vai in Site administration > Development > Purge caches per compilare i moduli JavaScript

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

# Panoramica Tecnica

## **Tipo**: Plugin Moodle con versioning (usa nuovo sistema Hook)

### **Architettura chiave**:
* Hook: Sistema moderno di hook per iniettare funzionalità
* Template: Mustache per rendering interfacce
* JavaScript: Moduli AMD/RequireJS per funzionalità client-side, NON USARE jquery
* AJAX: Endpoint per comunicazione asincrona
* Installazione: Setup automatico database/configurazione

### **File critici**:
* `ajax.php` - Endpoint AJAX
* `classes/hook_callbacks.php` - Logica principale hook
* `version.php` - Metadata plugin
* `amd/src/modal.js` - Modulo JavaScript principale
* `templates/modal.mustache` - Template interfaccia

## Flusso Funzionale

### **Flusso identificato (Modal post-login)**:
1. **Hook rileva evento** → inietta JavaScript nell'header della pagina
2. **JavaScript si carica** → utilizza RequireJS per caricare moduli
3. **Modal si attiva** → mostra interfaccia all'utente
4. **Utente interagisce** → seleziona opzioni e conferma
5. **Salvataggio AJAX** → invia dati al server per persistenza
6. **Controlli sessione** → previene ri-visualizzazione indesiderata

## NOTE
Il web service in realtà FUNZIONA - l'ho visto nei log: "WhereAreYou WebService: User 2 saved". Il JavaScript usa PRIMA il web service, poi fallback su AJAX.
È più professionale e segue le linee guida Moodle Non pesa sul plugin (i file sono già scritti).
