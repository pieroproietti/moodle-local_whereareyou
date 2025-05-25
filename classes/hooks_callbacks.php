<?php
namespace local_whereareyou;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks per WhereAreYou plugin
 */
class hook_callbacks {
    
    /**
     * Hook per iniettare JavaScript dopo il login
     * 
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        global $USER, $PAGE;
        
        // Solo per utenti autenticati (non guest)
        if (!isloggedin() || isguestuser()) {
            return;
        }
        
        // Solo su pagine dopo login (escludiamo login page stessa)
        if ($PAGE->pagetype === 'login-index') {
            return;
        }
        
        // Controlla se l'utente ha una sessione "fresca" (appena loggato)
        $show_modal = self::should_show_modal();
        
        if ($show_modal) {
            // Inietta il JavaScript per mostrare la modal
            self::inject_modal_javascript($hook);
        }
    }
    
    /**
     * Determina se mostrare la modal
     * 
     * @return bool
     */
    private static function should_show_modal(): bool {
        global $USER, $SESSION;
        
        // Per il testing, mostriamo sempre la modal
        // In produzione potresti voler controllare se i campi sono già compilati
        
        // Controlla se è una sessione fresca (appena loggato)
        if (isset($SESSION->whereareyou_modal_shown)) {
            return false; // Già mostrata in questa sessione
        }
        
        // Controlla se l'utente ha già compilato i campi
        // (opzionale - potresti voler mostrare sempre la modal)
        /*
        $department = self::get_user_profile_field('whereareyou_department');
        $position = self::get_user_profile_field('whereareyou_position');
        
        if (!empty($department) && !empty($position)) {
            return false; // Già compilato
        }
        */
        
        return true;
    }
    
    /**
     * Inietta il JavaScript per la modal
     * 
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    private static function inject_modal_javascript(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE, $SESSION;
        
        // Marca la modal come mostrata per questa sessione
        $SESSION->whereareyou_modal_shown = true;
        
        // Carica il modulo JavaScript
        $PAGE->requires->js_call_amd('local_whereareyou/modal', 'init');
        
        // Passa i dati necessari al JavaScript
        $data = [
            'ajax_url' => new \moodle_url('/local/whereareyou/ajax.php'),
            'logout_url' => new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]),
            'current_department' => self::get_user_profile_field('whereareyou_department'),
            'current_position' => self::get_user_profile_field('whereareyou_position'),
        ];
        
        $PAGE->requires->data_for_js('whereareyou_config', $data);
    }
    
    /**
     * Recupera valore campo profilo utente
     * 
     * @param string $fieldname
     * @return string
     */
    private static function get_user_profile_field(string $fieldname): string {
        global $DB, $USER;
        
        $field = $DB->get_record('user_info_field', ['shortname' => $fieldname]);
        if (!$field) {
            return '';
        }
        
        $data = $DB->get_record('user_info_data', [
            'userid' => $USER->id,
            'fieldid' => $field->id
        ]);
        
        return $data ? $data->data : '';
    }
}