<?php
require_once('../../config.php');

// Require admin login
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/whereareyou/debug.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('WhereAreYou Debug');
$PAGE->set_heading('WhereAreYou Debug');
$PAGE->set_pagelayout('admin');

// Handle force show action
$forceshow = optional_param('forceshow', 0, PARAM_INT);
$resetsession = optional_param('resetsession', 0, PARAM_INT);

if ($forceshow && confirm_sesskey()) {
    // Reset both session and daily preferences
    $session_key = 'local_whereareyou_shown_' . session_id();
    unset_user_preference($session_key);
    unset_user_preference('local_whereareyou_last_shown');
    
    redirect(new moodle_url('/'), 'Tutte le preferenze resettate. La modale dovrebbe apparire nella prossima pagina.', null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($resetsession && confirm_sesskey()) {
    // Reset only session preference
    $session_key = 'local_whereareyou_shown_' . session_id();
    unset_user_preference($session_key);
    
    redirect(new moodle_url('/'), 'Preferenza di sessione resettata. La modale dovrebbe apparire nella prossima pagina.', null, \core\output\notification::NOTIFY_SUCCESS);
}

// Get current user data
$department_field = $DB->get_record('user_info_field', ['shortname' => 'department']);
$position_field = $DB->get_record('user_info_field', ['shortname' => 'position']);

$current_department = '';
$current_position = '';

if ($department_field) {
    $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $department_field->id]);
    $current_department = $data ? $data->data : '';
}

if ($position_field) {
    $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $position_field->id]);
    $current_position = $data ? $data->data : '';
}

// Get preferences
$last_shown = get_user_preferences('local_whereareyou_last_shown', 0, $USER->id);
$last_shown_date = $last_shown ? date('Y-m-d H:i:s', $last_shown) : 'Mai';

$session_key = 'local_whereareyou_shown_' . session_id();
$session_shown = get_user_preferences($session_key, 0, $USER->id);
$session_shown_date = $session_shown ? date('Y-m-d H:i:s', $session_shown) : 'Mai';

// Check if modal should be shown
$should_show = false;
$reason = '';

// Get available options usando le stesse funzioni della classe hook_callbacks - SPOSTATO QUI
$department_options = [];
if ($department_field && !empty($department_field->param1)) {
    // Gestisce sia newline che spazi come separatori
    $raw_options = $department_field->param1;
    
    // Prima prova con newline
    if (strpos($raw_options, "\n") !== false) {
        $options = explode("\n", $raw_options);
    } 
    // Se non ci sono newline, prova con gli spazi
    else if (strpos($raw_options, " ") !== false) {
        $options = explode(" ", $raw_options);
    }
    // Altrimenti è un singolo valore
    else {
        $options = [$raw_options];
    }
    
    foreach ($options as $option) {
        $option = trim($option);
        if (!empty($option)) {
            $department_options[] = ['value' => $option, 'text' => $option];
        }
    }
}

$position_options = [];
if ($position_field && !empty($position_field->param1)) {
    // Gestisce sia newline che spazi come separatori
    $raw_options = $position_field->param1;
    
    // Prima prova con newline
    if (strpos($raw_options, "\n") !== false) {
        $options = explode("\n", $raw_options);
    } 
    // Se non ci sono newline, prova con gli spazi
    else if (strpos($raw_options, " ") !== false) {
        $options = explode(" ", $raw_options);
    }
    // Altrimenti è un singolo valore
    else {
        $options = [$raw_options];
    }
    
    foreach ($options as $option) {
        $option = trim($option);
        if (!empty($option)) {
            $position_options[] = ['value' => $option, 'text' => $option];
        }
    }
}

// Ora continua con la logica di controllo

if (empty($department_field) || empty($position_field)) {
    $reason = 'Campi profilo non configurati';
} elseif (empty($department_field->param1) || empty($position_field->param1)) {
    $reason = 'Opzioni dei campi profilo non configurate';
} elseif ($session_shown) {
    $reason = 'Già mostrata in questa sessione';
} else {
    // Controlla se è già stata mostrata oggi (se questa logica è attiva)
    $today = date('Y-m-d');
    $last_shown_date_only = $last_shown ? date('Y-m-d', $last_shown) : '';
    
    // Questa parte è commentata nel codice, quindi non blocca la visualizzazione
    // if ($last_shown_date_only === $today) {
    //     $reason = 'Già mostrata oggi';
    // } else {
        $should_show = true;
        $reason = 'Dovrebbe essere mostrata (modale attiva sempre al login)';
    // }
}

// Debug diretto del hook - simuliamo la logica per capire dove si blocca
echo "<div style='background: #f0f0f0; padding: 15px; margin: 15px 0; border: 1px solid #ccc;'>";
echo "<strong>🔍 DEBUG HOOK LOGIC:</strong><br>";

// Test 1: Utente loggato
$test1 = isloggedin() && !isguestuser();
echo "✅ Utente loggato e non guest: " . ($test1 ? 'SI' : 'NO') . "<br>";

// Test 2: Non è pagina login
$test2 = ($PAGE->pagelayout !== 'login');
echo "✅ Non è pagina login: " . ($test2 ? 'SI' : 'NO') . " (Layout: {$PAGE->pagelayout})<br>";

// Test 3: Non è AJAX
$test3 = !defined('AJAX_SCRIPT') || !AJAX_SCRIPT;
echo "✅ Non è AJAX: " . ($test3 ? 'SI' : 'NO') . " (AJAX_SCRIPT defined: " . (defined('AJAX_SCRIPT') ? 'true' : 'false') . ")<br>";

// Test 4: Non è popup/frametree
$test4 = !($PAGE->pagelayout === 'popup' || $PAGE->pagelayout === 'frametree');
echo "✅ Non è popup/frametree: " . ($test4 ? 'SI' : 'NO') . "<br>";

// Test 5: Controllo admin page
$current_path = $PAGE->url->get_path();
$is_admin = ($PAGE->pagelayout === 'admin');
$is_test_page = (strpos($current_path, '/local/whereareyou/test.php') !== false);
$is_debug_page = (strpos($current_path, '/local/whereareyou/debug.php') !== false);
$admin_allowed = !$is_admin || $is_test_page || $is_debug_page;

echo "🔍 Pagina admin: " . ($is_admin ? 'SI' : 'NO') . "<br>";
echo "🔍 È test page: " . ($is_test_page ? 'SI' : 'NO') . "<br>";
echo "🔍 È debug page: " . ($is_debug_page ? 'SI' : 'NO') . "<br>";
echo "🔍 Percorso corrente: {$current_path}<br>";
echo "✅ Admin allowed: " . ($admin_allowed ? 'SI' : 'NO') . "<br>";

// Test 6: Opzioni disponibili (gestione null)
$dept_count = is_array($department_options) ? count($department_options) : 0;
$pos_count = is_array($position_options) ? count($position_options) : 0;
$options_ok = !empty($department_options) && !empty($position_options);

echo "🔍 Department options type: " . gettype($department_options) . "<br>";
echo "🔍 Position options type: " . gettype($position_options) . "<br>";
echo "✅ Opzioni dipartimento ({$dept_count}): " . ($dept_count > 0 ? 'SI' : 'NO') . "<br>";
echo "✅ Opzioni posizione ({$pos_count}): " . ($pos_count > 0 ? 'SI' : 'NO') . "<br>";
echo "✅ Opzioni configurate: " . ($options_ok ? 'SI' : 'NO') . "<br>";

// Debug campi profilo
echo "<br><strong>🔍 DEBUG CAMPI PROFILO:</strong><br>";
if ($department_field) {
    echo "✅ Campo dipartimento trovato (ID: {$department_field->id})<br>";
    echo "🔍 Param1 dipartimento: '" . ($department_field->param1 ?? 'null') . "'<br>";
    
    // Test parsing opzioni dipartimento
    $raw_dept = $department_field->param1;
    $has_newline = strpos($raw_dept, "\n") !== false;
    $has_space = strpos($raw_dept, " ") !== false;
    
    echo "🔍 Contiene newline: " . ($has_newline ? 'SI' : 'NO') . "<br>";
    echo "🔍 Contiene spazi: " . ($has_space ? 'SI' : 'NO') . "<br>";
    
    if ($has_newline) {
        $dept_test = explode("\n", $raw_dept);
        echo "🔍 Parsing con newline: " . count($dept_test) . " opzioni (" . implode(' | ', array_map('trim', $dept_test)) . ")<br>";
    } else if ($has_space) {
        $dept_test = explode(" ", $raw_dept);
        echo "🔍 Parsing con spazi: " . count($dept_test) . " opzioni (" . implode(' | ', array_map('trim', $dept_test)) . ")<br>";
    } else {
        echo "🔍 Singola opzione: {$raw_dept}<br>";
    }
} else {
    echo "❌ Campo dipartimento NON trovato<br>";
}

if ($position_field) {
    echo "✅ Campo posizione trovato (ID: {$position_field->id})<br>";
    echo "🔍 Param1 posizione: '" . ($position_field->param1 ?? 'null') . "'<br>";
    
    // Test parsing opzioni posizione
    $raw_pos = $position_field->param1;
    $has_newline = strpos($raw_pos, "\n") !== false;
    $has_space = strpos($raw_pos, " ") !== false;
    
    echo "🔍 Contiene newline: " . ($has_newline ? 'SI' : 'NO') . "<br>";
    echo "🔍 Contiene spazi: " . ($has_space ? 'SI' : 'NO') . "<br>";
    
    if ($has_newline) {
        $pos_test = explode("\n", $raw_pos);
        echo "🔍 Parsing con newline: " . count($pos_test) . " opzioni (" . implode(' | ', array_map('trim', $pos_test)) . ")<br>";
    } else if ($has_space) {
        $pos_test = explode(" ", $raw_pos);
        echo "🔍 Parsing con spazi: " . count($pos_test) . " opzioni (" . implode(' | ', array_map('trim', $pos_test)) . ")<br>";
    } else {
        echo "🔍 Singola opzione: {$raw_pos}<br>";
    }
} else {
    echo "❌ Campo posizione NON trovato<br>";
}

// Test 7: Controllo sessione
$session_key_test = 'local_whereareyou_shown_' . session_id();
$session_shown_test = get_user_preferences($session_key_test, 0, $USER->id);
$session_ok = !$session_shown_test;
echo "<br>🔍 Session key: {$session_key_test}<br>";
echo "🔍 Session shown value: {$session_shown_test}<br>";
echo "✅ Sessione OK (non già mostrata): " . ($session_ok ? 'SI' : 'NO') . "<br>";

// Risultato finale
$hook_should_run = $test1 && $test2 && $test3 && $test4 && $admin_allowed && $options_ok && $session_ok;
echo "<br><strong>🎯 IL HOOK DOVREBBE FUNZIONARE: " . ($hook_should_run ? 'SI ✅' : 'NO ❌') . "</strong><br>";

if (!$hook_should_run) {
    echo "<strong style='color: red;'>❌ Problemi trovati:</strong><br>";
    if (!$test1) echo "- Utente non loggato correttamente<br>";
    if (!$test2) echo "- È una pagina login<br>";
    if (!$test3) echo "- È una richiesta AJAX<br>";
    if (!$test4) echo "- È una pagina popup/frametree<br>";
    if (!$admin_allowed) echo "- Pagina admin non permessa<br>";
    if (!$options_ok) echo "- Opzioni non configurate correttamente<br>";
    if (!$session_ok) echo "- Già mostrata in questa sessione<br>";
}

echo "</div>";

// Get available options (fix per evitare l'errore)
$department_options = $department_options ?? [];
$position_options = $position_options ?? [];
$department_options = [];
if ($department_field && !empty($department_field->param1)) {
    $options = explode("\n", $department_field->param1);
    foreach ($options as $option) {
        $option = trim($option);
        if (!empty($option)) {
            $department_options[] = ['value' => $option, 'text' => $option];
        }
    }
}

$position_options = [];
if ($position_field && !empty($position_field->param1)) {
    $options = explode("\n", $position_field->param1);
    foreach ($options as $option) {
        $option = trim($option);
        if (!empty($option)) {
            $position_options[] = ['value' => $option, 'text' => $option];
        }
    }
}

// Prepare template context for modal
$templatecontext = [
    'department_options' => $department_options,
    'position_options' => $position_options,
    'current_department' => $current_department,
    'current_position' => $current_position,
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot,
];

echo $OUTPUT->header();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Stato Attuale</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Utente</th>
                            <td><?php echo fullname($USER) . ' (ID: ' . $USER->id . ')'; ?></td>
                        </tr>
                        <tr>
                            <th>Dipartimento Attuale</th>
                            <td><?php echo $current_department ?: '<em>Non impostato</em>'; ?></td>
                        </tr>
                        <tr>
                            <th>Posizione Attuale</th>
                            <td><?php echo $current_position ?: '<em>Non impostata</em>'; ?></td>
                        </tr>
                        <tr>
                            <th>Ultima Visualizzazione</th>
                            <td><?php echo $last_shown_date; ?></td>
                        </tr>
                        <tr>
                            <th>Mostrata in Questa Sessione</th>
                            <td><?php echo $session_shown_date; ?> 
                                <small>(ID sessione: <?php echo session_id(); ?>)</small>
                            </td>
                        </tr>
                        <tr>
                            <th>Dovrebbe Mostrarsi?</th>
                            <td>
                                <span class="badge bg-<?php echo $should_show ? 'success' : 'danger'; ?>">
                                    <?php echo $should_show ? 'SÌ' : 'NO'; ?>
                                </span>
                                <br><small><?php echo $reason; ?></small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Configurazione Campi</h4>
                </div>
                <div class="card-body">
                    <h6>Campo Dipartimento:</h6>
                    <?php if ($department_field): ?>
                        <ul>
                            <li>ID: <?php echo $department_field->id; ?></li>
                            <li>Nome: <?php echo $department_field->name; ?></li>
                            <li>Opzioni: 
                                <?php 
                                if ($department_field->param1) {
                                    $options = explode("\n", $department_field->param1);
                                    echo '<br>' . implode('<br>', array_map('trim', $options));
                                } else {
                                    echo '<em>Nessuna opzione configurata</em>';
                                }
                                ?>
                            </li>
                        </ul>
                    <?php else: ?>
                        <p class="text-danger">Campo non trovato!</p>
                    <?php endif; ?>
                    
                    <h6>Campo Posizione:</h6>
                    <?php if ($position_field): ?>
                        <ul>
                            <li>ID: <?php echo $position_field->id; ?></li>
                            <li>Nome: <?php echo $position_field->name; ?></li>
                            <li>Opzioni: 
                                <?php 
                                if ($position_field->param1) {
                                    $options = explode("\n", $position_field->param1);
                                    echo '<br>' . implode('<br>', array_map('trim', $options));
                                } else {
                                    echo '<em>Nessuna opzione configurata</em>';
                                }
                                ?>
                            </li>
                        </ul>
                    <?php else: ?>
                        <p class="text-danger">Campo non trovato!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Azioni di Test</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="<?php echo $PAGE->url->out(false, ['forceshow' => 1, 'sesskey' => sesskey()]); ?>" 
                           class="btn btn-primary">
                            <i class="fa fa-refresh"></i> Forza Visualizzazione Modale (reset completo)
                        </a>
                        
                        <a href="<?php echo $PAGE->url->out(false, ['resetsession' => 1, 'sesskey' => sesskey()]); ?>" 
                           class="btn btn-warning">
                            <i class="fa fa-clock-o"></i> Reset Solo Sessione
                        </a>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-success" onclick="checkPreferences()">
                            <i class="fa fa-database"></i> Controlla Preferenze Live
                        </button>
                        
                        <button type="button" class="btn btn-danger" onclick="forceShowModal()">
                            <i class="fa fa-eye"></i> Forza Modale Su Questa Pagina
                        </button>
                        
                        <button type="button" class="btn btn-info" onclick="checkConsole()">
                            <i class="fa fa-terminal"></i> Controlla Console JavaScript
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <a href="test.php" class="btn btn-secondary">
                            <i class="fa fa-cog"></i> Vai alla Pagina di Test
                        </a>
                        
                        <a href="<?php echo new moodle_url('/'); ?>" class="btn btn-primary">
                            <i class="fa fa-home"></i> Vai alla Homepage (per test normale)
                        </a>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <h6><i class="fa fa-info-circle"></i> Come Funziona Ora:</h6>
                        <ul class="mb-0">
                            <li><strong>La modale appare SEMPRE al login</strong>, anche se i campi sono già compilati</li>
                            <li><strong>Non appare più volte nella stessa sessione</strong> (per evitare spam)</li>
                            <li><strong>La logica giornaliera è disattivata</strong> - puoi riattivarla modificando il codice</li>
                            <li><strong>Usa "Reset Solo Sessione"</strong> per testare senza logout/login</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fa fa-wrench"></i> Guida ai Test:</h6>
                        <ol class="mb-0">
                            <li><strong>Controlla Preferenze Live</strong> - Vedi lo stato attuale delle preferenze utente</li>
                            <li><strong>Reset Solo Sessione</strong> - Resetta solo la sessione corrente</li>
                            <li><strong>Ricarica la pagina</strong> - La modale dovrebbe apparire</li>
                            <li><strong>Se non appare</strong> - Usa "Forza Modale Su Questa Pagina"</li>
                            <li><strong>Controlla Console</strong> - Per messaggi di debug dettagliati</li>
                            <li><strong>Test su Homepage</strong> - Prova su una pagina normale (non admin)</li>
                        </ol>
                    </div>
                    
                    <div class="alert alert-success">
                        <h6><i class="fa fa-lightbulb-o"></i> Suggerimenti per il Debug:</h6>
                        <ul class="mb-0">
                            <li>Apri sempre la <strong>Console del Browser</strong> (F12) durante i test</li>
                            <li>I messaggi di debug PHP sono nei <strong>log di Moodle/Apache</strong></li>
                            <li>Se la modale non appare, controlla se il file <code>amd/build/modal.min.js</code> esiste</li>
                            <li>Prova su una <strong>finestra in incognito</strong> per escludere problemi di cache</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funzione per controllare le preferenze in tempo reale
function checkPreferences() {
    console.log('=== Controllo Preferenze Live ===');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo $CFG->wwwroot; ?>/local/whereareyou/ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                console.log('Preferenze attuali:', response);
                console.log('Session ID:', response.session_id);
                console.log('Session Key:', response.session_key);
                console.log('Session Shown:', response.session_shown_date);
                console.log('Last Shown:', response.last_shown_date);
                
                // Mostra anche nell'interfaccia
                var info = `
Session ID: ${response.session_id}
Session Key: ${response.session_key}
Mostrata in Sessione: ${response.session_shown_date}
Ultima Visualizzazione: ${response.last_shown_date}
                `;
                alert('Preferenze controllate!\n\n' + info + '\n\nVedi console per dettagli completi.');
            } catch (e) {
                console.error('Errore nel parsing della risposta:', e);
                console.log('Risposta raw:', xhr.responseText);
                alert('Errore nel controllo preferenze. Vedi console per dettagli.');
            }
        } else if (xhr.readyState === 4) {
            console.error('Errore HTTP:', xhr.status, xhr.statusText);
            alert('Errore nella richiesta HTTP: ' + xhr.status);
        }
    };
    xhr.send('action=check_preferences&sesskey=<?php echo sesskey(); ?>');
}

// Funzione per forzare la visualizzazione della modale su questa pagina
function forceShowModal() {
    console.log('=== Tentativo Forzatura Modale ===');
    
    if (window.whereAreYouConfig) {
        console.log('Configurazione trovata:', window.whereAreYouConfig);
        
        if (typeof require !== 'undefined') {
            console.log('RequireJS disponibile, caricamento modulo...');
            require(['local_whereareyou/modal'], function(modal) {
                console.log('Modulo caricato con successo, inizializzazione modale...');
                try {
                    modal.init(window.whereAreYouConfig);
                    console.log('Modale inizializzata con successo!');
                } catch (error) {
                    console.error('Errore nell\'inizializzazione:', error);
                    alert('Errore nell\'inizializzazione della modale: ' + error.message);
                }
            }, function(error) {
                console.error('Errore nel caricamento del modulo:', error);
                alert('Errore nel caricamento del modulo modal. Controlla che il file amd/build/modal.min.js esista.');
            });
        } else {
            console.error('RequireJS non disponibile');
            alert('RequireJS non disponibile. Questo è un problema del tema o della configurazione Moodle.');
        }
    } else {
        console.warn('Configurazione WhereAreYou non trovata');
        console.log('window.whereAreYouConfig:', window.whereAreYouConfig);
        alert('Configurazione WhereAreYou non trovata. Il plugin potrebbe non essere attivo su questa pagina.\n\nProva prima "Reset Solo Sessione" e poi ricarica la pagina.');
    }
}

function checkConsole() {
    console.log('=== WhereAreYou Debug Info Completo ===');
    console.log('Current URL:', window.location.href);
    console.log('Page layout:', '<?php echo $PAGE->pagelayout; ?>');
    console.log('User logged in:', <?php echo isloggedin() ? 'true' : 'false'; ?>);
    console.log('Is guest:', <?php echo isguestuser() ? 'true' : 'false'; ?>);
    console.log('AJAX_SCRIPT defined:', typeof AJAX_SCRIPT !== 'undefined');
    console.log('RequireJS available:', typeof require !== 'undefined');
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('WhereAreYou config:', window.whereAreYouConfig || 'Not set');
    console.log('WhereAreYou initialized:', window.whereAreYouInitialized || false);
    
    // Test RequireJS
    if (typeof require !== 'undefined') {
        console.log('Testing RequireJS module loading...');
        require(['local_whereareyou/modal'], function(modal) {
            console.log('✅ Modulo modal caricato con successo:', modal);
        }, function(error) {
            console.error('❌ Errore caricamento modulo modal:', error);
        });
    }
    
    console.log('=== Fine Debug Info ===');
    
    alert('Debug info completo scritto nella console. Apri gli strumenti sviluppatore (F12) e controlla la scheda Console.');
}

// Test automatico al caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Debug Page Loaded ===');
    console.log('WhereAreYou config al caricamento:', window.whereAreYouConfig || 'Non impostato');
    
    // Controlla se ci sono messaggi nella console dal plugin
    setTimeout(function() {
        console.log('=== Controllo Post-Caricamento ===');
        console.log('Config dopo 2 secondi:', window.whereAreYouConfig || 'Ancora non impostato');
        console.log('Initialized flag:', window.whereAreYouInitialized || 'Non impostato');
    }, 2000);
});
</script>

<?php
echo $OUTPUT->footer();
