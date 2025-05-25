<?php
namespace local_whereareyou;

use core\hook\output\before_standard_head_html_generation;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for WhereAreYou plugin - VERSIONE FISSA
 */
class hook_callbacks {
    
    /**
     * Callback for before_standard_head_html_generation hook
     * 
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        global $USER, $PAGE, $CFG;
        
        // DEBUG: Log sempre per capire se il hook viene chiamato
        error_log("WhereAreYou Hook: Chiamato per utente {$USER->id} su pagina {$PAGE->url->get_path()}");
        
        // Only show modal for logged in users, not on login page
        if (!isloggedin() || isguestuser() || $PAGE->pagelayout === 'login') {
            error_log("WhereAreYou Hook: Saltato - utente non loggato o pagina login");
            return;
        }
        
        // Skip for AJAX requests and certain page types
        if (defined('AJAX_SCRIPT') || $PAGE->pagelayout === 'popup' || $PAGE->pagelayout === 'frametree') {
            error_log("WhereAreYou Hook: Saltato - AJAX o popup/frametree");
            return;
        }
        
        // Skip for admin pages - but allow test page
        if ($PAGE->pagelayout === 'admin' && 
            strpos($PAGE->url->get_path(), '/local/whereareyou/test.php') === false && 
            strpos($PAGE->url->get_path(), '/local/whereareyou/debug.php') === false &&
            strpos($PAGE->url->get_path(), '/local/whereareyou/hook_test.php') === false) {
            error_log("WhereAreYou Hook: Saltato - pagina admin non permessa");
            return;
        }        
        
        // Get current values
        $department = self::get_user_department($USER->id);
        $position = self::get_user_position($USER->id);
        
        // Get available options
        $department_options = self::get_department_options();
        $position_options = self::get_position_options();
        
        // Only proceed if we have options configured
        if (empty($department_options) || empty($position_options)) {
            error_log("WhereAreYou Hook: Saltato - opzioni non configurate");
            return;
        }
        
        // Verifica se è già stata mostrata in questa sessione
        $session_key = 'local_whereareyou_shown_' . session_id();
        $session_shown = get_user_preferences($session_key, 0, $USER->id);
        
        // Se è già stata mostrata in questa sessione, non mostrarla di nuovo
        if ($session_shown) {
            error_log("WhereAreYou Hook: Saltato - già mostrata in questa sessione");
            return;
        }
        
        error_log("WhereAreYou Hook: Tutte le condizioni soddisfatte - generazione script");
        
        // Prepare template context
        $templatecontext = [
            'department_options' => $department_options,
            'position_options' => $position_options,
            'current_department' => $department,
            'current_position' => $position,
            'sesskey' => sesskey(),
            'wwwroot' => $CFG->wwwroot,
        ];
        
        // Controlla se esiste il file AMD compilato
        $amd_build_file = $CFG->dirroot . '/local/whereareyou/amd/build/modal.min.js';
        $use_requirejs = file_exists($amd_build_file);
        
        error_log("WhereAreYou Hook: File AMD build esiste: " . ($use_requirejs ? 'SI' : 'NO'));
        
        $js_config = json_encode($templatecontext);
        
        if ($use_requirejs) {
            // Usa RequireJS (metodo originale)
            $script = self::generateRequireJSScript($js_config, $templatecontext);
        } else {
            // Usa workaround diretto
            $script = self::generateDirectScript($js_config, $templatecontext);
        }
        
        // Add to head
        $hook->add_html($script);
        
        error_log("WhereAreYou Hook: Script aggiunto al head");
    }
    
    /**
     * Genera script RequireJS (metodo originale)
     */
    private static function generateRequireJSScript($js_config, $templatecontext) {
        global $USER, $CFG;
        $sesskey = sesskey();
        
        return "
        <script>
        console.log('WhereAreYou: Hook chiamato per utente {$USER->id} (RequireJS)');
        
        // Store config globally
        window.whereAreYouConfig = {$js_config};
        window.whereAreYouInitialized = false;
        
        // Function to initialize modal
        function initWhereAreYouModal() {
            if (window.whereAreYouInitialized) {
                console.log('WhereAreYou: Già inizializzato, saltando...');
                return;
            }
            
            if (typeof require !== 'undefined') {
                console.log('WhereAreYou: Caricamento modulo modale RequireJS...');
                require(['local_whereareyou/modal'], function(modal) {
                    console.log('WhereAreYou: Modulo modale caricato con successo');
                    try {
                        modal.init(window.whereAreYouConfig);
                        window.whereAreYouInitialized = true;
                        
                        // Aggiorna timestamp
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '{$CFG->wwwroot}/local/whereareyou/ajax.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.send('action=update_last_shown&sesskey=' + encodeURIComponent('{$sesskey}'));
                        
                    } catch (error) {
                        console.error('WhereAreYou: Errore inizializzazione:', error);
                    }
                }, function(error) {
                    console.error('WhereAreYou: Fallito caricamento modale RequireJS, provo metodo diretto', error);
                    // Fallback al metodo diretto
                    window.whereAreYouInitialized = false;
                    initWhereAreYouModalDirect();
                });
            } else {
                console.log('WhereAreYou: RequireJS non pronto, provo metodo diretto...');
                initWhereAreYouModalDirect();
            }
        }
        
        // Fallback diretto se RequireJS fallisce
        function initWhereAreYouModalDirect() {
            console.log('WhereAreYou: Tentativo metodo diretto...');
            // Carica script diretto se non già caricato
            if (typeof window.WhereAreYouModal === 'undefined') {
                var script = document.createElement('script');
                script.src = '{$CFG->wwwroot}/local/whereareyou/modal_direct.js?v=' + Date.now();
                script.onload = function() {
                    console.log('WhereAreYou: Script diretto caricato');
                    if (window.WhereAreYouModal) {
                        window.WhereAreYouModal.init(window.whereAreYouConfig);
                        window.whereAreYouInitialized = true;
                        
                        // Aggiorna timestamp
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '{$CFG->wwwroot}/local/whereareyou/ajax.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.send('action=update_last_shown&sesskey=' + encodeURIComponent('{$sesskey}'));
                    }
                };
                script.onerror = function() {
                    console.error('WhereAreYou: Impossibile caricare anche lo script diretto');
                };
                document.head.appendChild(script);
            } else {
                window.WhereAreYouModal.init(window.whereAreYouConfig);
                window.whereAreYouInitialized = true;
            }
        }
        
        // Inizializza quando la pagina è pronta
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initWhereAreYouModal, 500);
            });
        } else {
            setTimeout(initWhereAreYouModal, 1000);
        }
        
        window.addEventListener('load', function() {
            if (!window.whereAreYouInitialized) {
                setTimeout(initWhereAreYouModal, 500);
            }
        });
        </script>
        ";
    }
    
    /**
     * Genera script diretto (workaround)
     */
    private static function generateDirectScript($js_config, $templatecontext) {
        global $USER, $CFG;
        $sesskey = sesskey();
        
        return "
        <script>
        console.log('WhereAreYou: Hook chiamato per utente {$USER->id} (Metodo Diretto)');
        
        // Store config globally
        window.whereAreYouConfig = {$js_config};
        window.whereAreYouInitialized = false;
        
        // Carica script diretto
        function loadWhereAreYouDirect() {
            if (window.whereAreYouInitialized) {
                return;
            }
            
            console.log('WhereAreYou: Caricamento script diretto...');
            
            var script = document.createElement('script');
            script.src = '{$CFG->wwwroot}/local/whereareyou/modal_direct.js?v=' + Date.now();
            script.onload = function() {
                console.log('WhereAreYou: Script diretto caricato con successo');
                if (window.WhereAreYouModal) {
                    window.WhereAreYouModal.init(window.whereAreYouConfig);
                    window.whereAreYouInitialized = true;
                    
                    // Aggiorna timestamp
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '{$CFG->wwwroot}/local/whereareyou/ajax.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send('action=update_last_shown&sesskey=' + encodeURIComponent('{$sesskey}'));
                } else {
                    console.error('WhereAreYou: Script caricato ma oggetto non trovato');
                }
            };
            script.onerror = function() {
                console.error('WhereAreYou: Errore nel caricamento script diretto');
            };
            document.head.appendChild(script);
        }
        
        // Inizializza
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(loadWhereAreYouDirect, 500);
            });
        } else {
            setTimeout(loadWhereAreYouDirect, 1000);
        }
        
        window.addEventListener('load', function() {
            if (!window.whereAreYouInitialized) {
                setTimeout(loadWhereAreYouDirect, 500);
            }
        });
        </script>
        ";
    }
    
    /**
     * Get user's current department
     */
    private static function get_user_department($userid) {
        global $DB;
        
        $field = $DB->get_record('user_info_field', ['shortname' => 'department']);
        if (!$field) {
            return '';
        }
        
        $data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
        return $data ? $data->data : '';
    }
    
    /**
     * Get user's current position
     */
    private static function get_user_position($userid) {
        global $DB;
        
        $field = $DB->get_record('user_info_field', ['shortname' => 'position']);
        if (!$field) {
            return '';
        }
        
        $data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
        return $data ? $data->data : '';
    }
    
    /**
     * Get department options
     */
    private static function get_department_options() {
        global $DB;
        
        $field = $DB->get_record('user_info_field', ['shortname' => 'department']);
        if (!$field || empty($field->param1)) {
            return [];
        }
        
        $raw_options = $field->param1;
        
        if (strpos($raw_options, "\n") !== false) {
            $options = explode("\n", $raw_options);
        } else if (strpos($raw_options, " ") !== false) {
            $options = explode(" ", $raw_options);
        } else {
            $options = [$raw_options];
        }
        
        $result = [];
        foreach ($options as $option) {
            $option = trim($option);
            if (!empty($option)) {
                $result[] = ['value' => $option, 'text' => $option];
            }
        }
        return $result;
    }
    
    /**
     * Get position options
     */
    private static function get_position_options() {
        global $DB;
        
        $field = $DB->get_record('user_info_field', ['shortname' => 'position']);
        if (!$field || empty($field->param1)) {
            return [];
        }
        
        $raw_options = $field->param1;
        
        if (strpos($raw_options, "\n") !== false) {
            $options = explode("\n", $raw_options);
        } else if (strpos($raw_options, " ") !== false) {
            $options = explode(" ", $raw_options);
        } else {
            $options = [$raw_options];
        }
        
        $result = [];
        foreach ($options as $option) {
            $option = trim($option);
            if (!empty($option)) {
                $result[] = ['value' => $option, 'text' => $option];
            }
        }
        return $result;
    }
}
