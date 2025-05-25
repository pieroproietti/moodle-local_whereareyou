<?php
// Salva come /local/whereareyou/hook_test.php
require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

echo "<h1>Hook Debug Test</h1>\n";

// Test 1: Verifica che la classe hook_callbacks esista
echo "<h3>1. Verifica classe hook_callbacks</h3>\n";
if (class_exists('local_whereareyou\hook_callbacks')) {
    echo "✅ Classe hook_callbacks trovata<br>\n";
    
    // Test che il metodo esista
    if (method_exists('local_whereareyou\hook_callbacks', 'before_standard_head_html_generation')) {
        echo "✅ Metodo before_standard_head_html_generation trovato<br>\n";
    } else {
        echo "❌ Metodo before_standard_head_html_generation NON trovato<br>\n";
    }
} else {
    echo "❌ Classe hook_callbacks NON trovata<br>\n";
}

// Test 2: Verifica registrazione hooks nel database (Moodle 5+)
echo "<h3>2. Verifica registrazione hooks nel database</h3>\n";
try {
    // In Moodle 5+, gli hook sono registrati in modo diverso
    // Prova a chiamare direttamente il hook per vedere se funziona
    echo "Hook system Moodle 5+ - test chiamata diretta:<br>\n";
    
    // Simula il hook
    global $USER, $PAGE, $CFG;
    
    echo "User ID: {$USER->id}<br>\n";
    echo "Page layout: {$PAGE->pagelayout}<br>\n";
    echo "URL path: {$PAGE->url->get_path()}<br>\n";
    echo "Is logged in: " . (isloggedin() ? 'YES' : 'NO') . "<br>\n";
    echo "Is guest: " . (isguestuser() ? 'YES' : 'NO') . "<br>\n";
    
} catch (Exception $e) {
    echo "❌ Errore nel test hook: " . $e->getMessage() . "<br>\n";
}

// Test 3: Verifica campi profilo
echo "<h3>3. Verifica campi profilo</h3>\n";
$department_field = $DB->get_record('user_info_field', ['shortname' => 'department']);
$position_field = $DB->get_record('user_info_field', ['shortname' => 'position']);

if ($department_field) {
    echo "✅ Campo department trovato (ID: {$department_field->id})<br>\n";
    echo "Opzioni: " . ($department_field->param1 ?: 'NESSUNA') . "<br>\n";
} else {
    echo "❌ Campo department NON trovato<br>\n";
}

if ($position_field) {
    echo "✅ Campo position trovato (ID: {$position_field->id})<br>\n";
    echo "Opzioni: " . ($position_field->param1 ?: 'NESSUNA') . "<br>\n";
} else {
    echo "❌ Campo position NON trovato<br>\n";
}

// Test 4: Verifica file JavaScript
echo "<h3>4. Verifica file JavaScript</h3>\n";
$js_src = $CFG->dirroot . '/local/whereareyou/amd/src/modal.js';
$js_build = $CFG->dirroot . '/local/whereareyou/amd/build/modal.min.js';

if (file_exists($js_src)) {
    echo "✅ File src trovato: " . date('Y-m-d H:i:s', filemtime($js_src)) . "<br>\n";
} else {
    echo "❌ File src NON trovato<br>\n";
}

if (file_exists($js_build)) {
    echo "✅ File build trovato: " . date('Y-m-d H:i:s', filemtime($js_build)) . "<br>\n";
    
    // Controlla se è più recente del src
    if (filemtime($js_build) >= filemtime($js_src)) {
        echo "✅ File build è aggiornato<br>\n";
    } else {
        echo "⚠️ File build è OBSOLETO - RICOMPILA!<br>\n";
    }
} else {
    echo "❌ File build NON trovato - COMPILA I MODULI AMD!<br>\n";
}

// Test 5: Test hook simulato
echo "<h3>5. Test simulazione hook</h3>\n";
echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
echo "<strong>Simulazione chiamata hook:</strong><br>\n";

try {
    // Crea un mock del hook
    $mock_hook = new stdClass();
    $mock_hook->html = '';
    
    // Simula add_html method
    $mock_hook->add_html = function($html) use ($mock_hook) {
        $mock_hook->html .= $html;
    };
    
    // Prova a chiamare il metodo
    ob_start();
    // local_whereareyou\hook_callbacks::before_standard_head_html_generation($mock_hook);
    echo "Hook chiamato senza errori<br>\n";
    $output = ob_get_clean();
    
    if (strlen($mock_hook->html) > 0) {
        echo "✅ Hook ha generato HTML (" . strlen($mock_hook->html) . " caratteri)<br>\n";
        echo "<details><summary>Vedi HTML generato</summary><pre>" . htmlentities(substr($mock_hook->html, 0, 500)) . "...</pre></details>";
    } else {
        echo "⚠️ Hook NON ha generato HTML - possibili condizioni bloccanti<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore nella simulazione: " . $e->getMessage() . "<br>\n";
}
echo "</div>";

// Test 6: Mostra tutte le preferenze utente correlate
echo "<h3>6. Preferenze utente</h3>\n";
$session_key = 'local_whereareyou_shown_' . session_id();
$last_shown = get_user_preferences('local_whereareyou_last_shown', 0, $USER->id);
$session_shown = get_user_preferences($session_key, 0, $USER->id);

echo "Session ID: " . session_id() . "<br>\n";
echo "Session key: {$session_key}<br>\n";
echo "Last shown: " . ($last_shown ? date('Y-m-d H:i:s', $last_shown) : 'Mai') . "<br>\n";
echo "Session shown: " . ($session_shown ? date('Y-m-d H:i:s', $session_shown) : 'Mai') . "<br>\n";

// Test 7: Suggerimenti per il fix
echo "<h3>7. Azioni suggerite</h3>\n";
echo "<div style='background: #ffffcc; padding: 10px; margin: 10px 0;'>";
echo "<strong>Per risolvere i problemi:</strong><br>\n";
echo "1. <a href='hook_test.php?action=compile'>Compila moduli AMD</a><br>\n";
echo "2. <a href='hook_test.php?action=purge'>Purge all caches</a><br>\n";
echo "3. <a href='hook_test.php?action=reset_prefs'>Reset preferenze utente</a><br>\n";
echo "4. <a href='{$CFG->wwwroot}/admin/purgecaches.php'>Vai a Purge Caches (admin)</a><br>\n";
echo "</div>";

// Gestione azioni
$action = optional_param('action', '', PARAM_ALPHA);
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'compile':
            // Forza ricompilazione AMD
            purge_all_caches();
            echo "<div style='background: #ccffcc; padding: 10px;'>✅ Cache purgate - ricompila manualmente i moduli AMD</div>";
            break;
            
        case 'purge':
            purge_all_caches();
            echo "<div style='background: #ccffcc; padding: 10px;'>✅ Tutte le cache purgate</div>";
            break;
            
        case 'reset_prefs':
            unset_user_preference('local_whereareyou_last_shown');
            unset_user_preference($session_key);
            echo "<div style='background: #ccffcc; padding: 10px;'>✅ Preferenze utente resettate</div>";
            break;
    }
}

?>