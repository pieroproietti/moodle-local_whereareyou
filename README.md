# WhereAreYou - Moodle Plugin

Un plugin moderno per Moodle 5+ che mostra una modal post-login per permettere agli utenti di specificare il proprio dipartimento e posizione.

## Caratteristiche

- ✅ Modal automatica dopo ogni login (sempre visibile)
- ✅ Campi Department e Position personalizzabili
- ✅ Salvataggio in campi personalizzati utente
- ✅ Compatibile con Moodle 5+
- ✅ Utilizza il nuovo sistema di hook (niente callback legacy)
- ✅ Design moderno e responsive
- ✅ Supporto multilingua (IT/EN)
- ✅ **Pagina di test per amministratori**

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

### Struttura Directory
```
local/whereareyou/
├── version.php
├── settings.php  
├── test.php
├── ajax.php
├── db/
│   ├── install.php
│   └── hooks.php
├── classes/
│   └── hook_callbacks.php
├── lang/
│   ├── en/local_whereareyou.php
│   └── it/local_whereareyou.php
├── templates/
│   └── modal.mustache
├── amd/src/
│   └── modal.js
└── styles.css (opzionale)
```

## Funzionamento

- La modal appare automaticamente dopo ogni login
- L'utente deve selezionare dipartimento e posizione
- Due pulsanti disponibili: "Salva" e "Logout"
- I dati vengono salvati nei campi personalizzati del profilo
- La modal è sempre visibile (anche se i campi sono già compilati)

## Testing

Per testare la funzionalità senza dover fare login/logout ripetutamente:

1. Vai in **Site administration > Local plugins > WhereAreYou Test Page**
2. Clicca **"Show Modal"** per visualizzare la modale
3. Testa il salvataggio dei dati
4. Usa **"Reset My Data"** per pulire i valori e ritestare

La pagina di test mostra anche:
- Valori correnti di dipartimento e posizione
- Opzioni disponibili per entrambi i campi
- Istruzioni dettagliate per il testing

## Requisiti

- Moodle 5.0+
- PHP 8.0+
- JavaScript abilitato

## Supporto

Per problemi o domande, consulta la documentazione di Moodle o contatta l'amministratore del sistema.

## Troubleshooting

### Errore "No define call for local_whereareyou/modal"
**Problema**: Il modulo JavaScript non viene caricato correttamente.
**Soluzione**:
1. Vai in Site administration > Development > **Purge caches**
2. Verifica che il file `amd/src/modal.js` sia presente
3. Controlla i permessi della directory del plugin
4. Ricarica la pagina dopo aver purgato le cache

### La modal non appare dopo il login
**Problema**: L'hook non viene eseguito.
**Soluzioni**:
1. Verifica che il plugin sia attivato
2. Controlla che i campi personalizzati siano stati creati
3. Purga le cache di Moodle
4. Verifica che non ci siano conflitti con altri plugin

### Errori di salvataggio
**Problema**: I dati non vengono salvati.
**Soluzioni**:
1. Controlla che `ajax.php` sia accessibile
2. Verifica i permessi della directory del plugin
3. Controlla i log di errore di Moodle
4. Assicurati che i campi personalizzati esistano

### Testing
Se riscontri problemi, usa la **pagina di test** per debuggare:
1. Site administration > Local plugins > WhereAreYou Test Page
2. Controlla la console browser per errori JavaScript
3. Testa il salvataggio e il reset dei dati
