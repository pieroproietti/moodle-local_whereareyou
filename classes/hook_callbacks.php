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
        if ($PAGE->pagelayout === 'admin' && strpos($PAGE->url->get_path(), '/local/whereareyou/test.php') === false) {
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
        
        // IMPORTANTE: Mostra la modale solo se i dati non sono ancora impostati
        if (!empty($department) && !empty($position)) {
            // Entrambi i campi sono già impostati, non mostrare la modale
            return;
        }
        
        // Verifica se è la prima volta che l'utente accede oggi
        $last_shown = get_user_preferences('local_whereareyou_last_shown', 0, $USER->id);
        $today = date('Y-m-d');
        $last_shown_date = date('Y-m-d', $last_shown);
        
        // Se è già stata mostrata oggi e i dati sono parzialmente impostati, non mostrarla di nuovo
        if ($last_shown_date === $today && (!empty($department) || !empty($position))) {
            return;
        }
        
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
                        
                        // Aggiorna timestamp dell'ultima visualizzazione
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
        
        $options = explode("\n", $field->param1);
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
        
        $options = explode("\n", $field->param1);
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