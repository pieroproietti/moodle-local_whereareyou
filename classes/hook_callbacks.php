<?php
namespace local_whereareyou;

use core\hook\output\before_standard_head_html_generation;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for WhereAreYou plugin
 */
class hook_callbacks {
    
    /**
     * Callback for before_standard_head_html_generation hook
     * 
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        global $USER, $PAGE, $CFG;
        
        // Only show modal for logged in users, not on login page
        if (!isloggedin() || isguestuser() || $PAGE->pagelayout === 'login') {
            return;
        }
        
        // Skip for AJAX requests and certain page types
        if (defined('AJAX_SCRIPT') || $PAGE->pagelayout === 'popup' || $PAGE->pagelayout === 'frametree') {
            return;
        }
        
        // Skip for admin pages - but allow test page
        if ($PAGE->pagelayout === 'admin' && 
            strpos($PAGE->url->get_path(), '/local/whereareyou/test.php') === false && 
            strpos($PAGE->url->get_path(), '/local/whereareyou/debug.php') === false) {
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
            return;
        }
        
        // Verifica se è già stata mostrata in questa sessione
        $session_key = 'local_whereareyou_shown_' . session_id();
        $last_shown = get_user_preferences('local_whereareyou_last_shown', 0, $USER->id);
        $session_shown = get_user_preferences($session_key, 0, $USER->id);
        
        // Se è già stata mostrata in questa sessione, non mostrarla di nuovo
        if ($session_shown) {
            return;
        }
        
        // Opzionale: mostra solo una volta al giorno (rimuovi questo blocco se vuoi che appaia ad ogni login)
        $today = date('Y-m-d');
        $last_shown_date = date('Y-m-d', $last_shown);
        
        // Se è già stata mostrata oggi, non mostrarla di nuovo (commenta queste righe per mostrarla ad ogni login)
        // if ($last_shown_date === $today) {
        //     return;
        // }
        
        // Prepare template context
        $templatecontext = [
            'department_options' => $department_options,
            'position_options' => $position_options,
            'current_department' => $department,
            'current_position' => $position,
            'sesskey' => sesskey(),
            'wwwroot' => $CFG->wwwroot,
        ];
        
        // Migliore approccio: Add script direttamente to head con gestione errori
        $js_config = json_encode($templatecontext);
        $script = "
        <script>
        console.log('WhereAreYou: Hook chiamato per utente {$USER->id}');
        console.log('WhereAreYou: Dipartimento corrente: {$department}');
        console.log('WhereAreYou: Posizione corrente: {$position}');
        
        // Store config globally
        window.whereAreYouConfig = {$js_config};
        
        // Flag per evitare inizializzazioni multiple
        window.whereAreYouInitialized = false;
        
        // Function to initialize modal
        function initWhereAreYouModal() {
            if (window.whereAreYouInitialized) {
                console.log('WhereAreYou: Già inizializzato, saltando...');
                return;
            }
            
            if (typeof require !== 'undefined') {
                console.log('WhereAreYou: Caricamento modulo modale...');
                require(['local_whereareyou/modal'], function(modal) {
                    console.log('WhereAreYou: Modulo modale caricato con successo');
                    try {
                        modal.init(window.whereAreYouConfig);
                        window.whereAreYouInitialized = true;
                        
                        // Aggiorna timestamp dell'ultima visualizzazione e segna come mostrata in questa sessione
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '{$CFG->wwwroot}/local/whereareyou/ajax.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.send('action=update_last_shown&sesskey=' + encodeURIComponent('{$sesskey}'));
                        
                    } catch (error) {
                        console.error('WhereAreYou: Errore nell\\'inizializzazione della modale:', error);
                    }
                }, function(error) {
                    console.error('WhereAreYou: Fallito caricamento modale', error);
                    // Riprova dopo un po'
                    setTimeout(function() {
                        console.log('WhereAreYou: Nuovo tentativo di caricamento...');
                        window.whereAreYouInitialized = false;
                        initWhereAreYouModal();
                    }, 2000);
                });
            } else {
                console.log('WhereAreYou: RequireJS non pronto, riprovo tra 1 secondo...');
                setTimeout(initWhereAreYouModal, 1000);
            }
        }
        
        // Inizializza quando la pagina è caricata
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                console.log('WhereAreYou: DOM caricato, inizializzazione in 500ms...');
                setTimeout(initWhereAreYouModal, 500);
            });
        } else {
            console.log('WhereAreYou: DOM già caricato, inizializzazione in 1 secondo...');
            setTimeout(initWhereAreYouModal, 1000);
        }
        
        // Fallback: inizializza anche quando la window è completamente caricata
        window.addEventListener('load', function() {
            if (!window.whereAreYouInitialized) {
                console.log('WhereAreYou: Window caricata, ultimo tentativo di inizializzazione...');
                setTimeout(initWhereAreYouModal, 500);
            }
        });
        </script>
        ";
        
        // Add to head
        $hook->add_html($script);
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
        
        // Gestisce sia newline che spazi come separatori
        $raw_options = $field->param1;
        
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
        
        // Gestisce sia newline che spazi come separatori
        $raw_options = $field->param1;
        
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