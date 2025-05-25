<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Callback che si attiva su ogni pagina - CONTROLLATO
 */
function local_whereareyou_before_standard_html_head() {
    global $USER, $PAGE, $SESSION;
    
    // Solo per utenti autenticati
    if (!isloggedin() || isguestuser()) {
        return;
    }
    
    // Escludi pagine specifiche
    $excluded_pages = [
        'login-index',
        'login-signup', 
        'login-forgot_password',
        'admin-',
        'local-whereareyou-test'
    ];
    
    foreach ($excluded_pages as $excluded) {
        if (strpos($PAGE->pagetype, $excluded) === 0) {
            return;
        }
    }
    
    // CONTROLLO SESSIONE - mostra solo UNA volta per sessione
    if (isset($SESSION->whereareyou_modal_shown)) {
        return;
    }
    
    // CONTROLLO GIÀ COMPILATO (opzionale)
    $current_dept = local_whereareyou_get_user_field('whereareyou_department');
    $current_pos = local_whereareyou_get_user_field('whereareyou_position');
    
    // Se entrambi i campi sono già compilati, non mostrare
    // if (!empty($current_dept) && !empty($current_pos)) {
    //    $SESSION->whereareyou_modal_shown = true;
    //      return;
    //}
    
    error_log("WhereAreYou: Mostro modal per user {$USER->id} su pagina {$PAGE->pagetype}");
    
    // Marca come mostrata
    $SESSION->whereareyou_modal_shown = true;
    
    // Prepara i dati
    $ajax_url = (new moodle_url('/local/whereareyou/ajax.php'))->out(false);
    $logout_url = (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false);
    
    // JavaScript con injection diretta - UNA SOLA VOLTA
    $js = "
    if (typeof window.whereareyou_loaded === 'undefined') {
        window.whereareyou_loaded = true;
        
        console.log('WhereAreYou: Prima inizializzazione');
        
        window.M.cfg.whereareyou_config = {
            'ajax_url': '{$ajax_url}',
            'logout_url': '{$logout_url}',
            'current_department': '{$current_dept}',
            'current_position': '{$current_pos}'
        };
        
        require(['local_whereareyou/modal'], function(Modal) {
            console.log('WhereAreYou: Inizializzo modal UNICA volta');
            Modal.init();
        });
    } else {
        console.log('WhereAreYou: Già caricato, salto inizializzazione');
    }
    ";
    
    $PAGE->requires->js_init_code($js);
}

/**
 * Helper function per recuperare campi profilo
 */
function local_whereareyou_get_user_field($fieldname) {
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