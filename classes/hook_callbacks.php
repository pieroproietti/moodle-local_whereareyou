<?php
namespace local_whereareyou;

defined('MOODLE_INTERNAL') || die();

class hook_callbacks {
    
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        global $USER, $PAGE;
        
        // Debug per vedere se il hook si attiva
        error_log("WhereAreYou DEBUG: Hook attivato - Pagina: " . $PAGE->pagetype . " - User: " . $USER->id);
        
        if (!isloggedin() || isguestuser()) {
            error_log("WhereAreYou DEBUG: Utente non loggato, esco");
            return;
        }
        
        if ($PAGE->pagetype === 'login-index') {
            error_log("WhereAreYou DEBUG: Pagina login, esco");
            return;
        }
        
        $show_modal = self::should_show_modal();
        error_log("WhereAreYou DEBUG: Show modal = " . ($show_modal ? 'SI' : 'NO'));
        
        if ($show_modal) {
            error_log("WhereAreYou DEBUG: Inietto JavaScript");
            self::inject_modal_javascript();
        }
    }
    
    private static function should_show_modal(): bool {
        global $SESSION;
        
        // Per il test, commentiamo il controllo sessione
        // if (isset($SESSION->whereareyou_modal_shown)) {
        //     return false;
        // }
        
        return true; // Sempre true per il momento
    }
    
    private static function inject_modal_javascript(): void {
        global $PAGE, $SESSION;
        
        error_log("WhereAreYou DEBUG: Inizio injection JavaScript");
        
        // $SESSION->whereareyou_modal_shown = true;
        
        // Prepara i dati
        $ajax_url = (new \moodle_url('/local/whereareyou/ajax.php'))->out(false);
        $logout_url = (new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false);
        $current_dept = self::get_user_profile_field('whereareyou_department');
        $current_pos = self::get_user_profile_field('whereareyou_position');
        
        // JavaScript con injection diretta (stesso metodo che funziona nel test)
        $js = "
        console.log('WhereAreYou: Hook injection - Inizio');
        
        // Iniettiamo i dati direttamente
        window.M.cfg.whereareyou_config = {
            'ajax_url': '{$ajax_url}',
            'logout_url': '{$logout_url}',
            'current_department': '{$current_dept}',
            'current_position': '{$current_pos}'
        };
        
        console.log('WhereAreYou: Dati iniettati dal hook:', window.M.cfg.whereareyou_config);
        
        // Carica il modulo
        require(['local_whereareyou/modal'], function(Modal) {
            console.log('WhereAreYou: Modulo caricato, inizializzo modal');
            Modal.init();
        });
        ";
        
        $PAGE->requires->js_init_code($js);
        
        error_log("WhereAreYou DEBUG: JavaScript iniettato con successo");
    }
    
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