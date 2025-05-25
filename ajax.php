<?php
/**
 * WhereAreYou Plugin - Endpoint AJAX
 * 
 * Gestisce le richieste AJAX per salvare i dati dell'utente
 * 
 * @package    local_whereareyou
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

// Headers per JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

/**
 * Invia risposta JSON e termina
 */
function send_json_response($success, $data = null, $error = null) {
    $response = [
        'success' => $success,
        'timestamp' => time()
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Gestisce errori e li logga
 */
function handle_error($message, $exception = null) {
    global $USER;
    
    // Log dell'errore
    $log_message = "WhereAreYou AJAX Error - User {$USER->id}: {$message}";
    if ($exception) {
        $log_message .= " - Exception: " . $exception->getMessage();
    }
    error_log($log_message);
    
    // Risposta JSON di errore
    send_json_response(false, null, $message);
}

try {
    // === CONTROLLI DI SICUREZZA ===
    
    // Verifica che sia una richiesta POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handle_error('Metodo non consentito');
    }
    
    // Verifica che l'utente sia autenticato
    require_login();
    
    if (!isloggedin() || isguestuser()) {
        handle_error('Accesso non autorizzato');
    }
    
    // Verifica sesskey per CSRF protection
    $sesskey = optional_param('sesskey', '', PARAM_ALPHANUM);
    if (!confirm_sesskey($sesskey)) {
        handle_error('Sessione non valida');
    }
    
    // === GESTIONE AZIONI ===
    
    $action = required_param('action', PARAM_ALPHA);
    
    switch ($action) {
        case 'save':
            handle_save_action();
            break;
            
        case 'reset':
            handle_reset_action();
            break;
            
        default:
            handle_error('Azione non riconosciuta: ' . $action);
    }
    
} catch (Exception $e) {
    handle_error('Errore interno del server', $e);
}

/**
 * Gestisce il salvataggio dei dati
 */
function handle_save_action() {
    global $DB, $USER;
    
    try {
        // Recupera e valida i parametri
        $department = required_param('department', PARAM_TEXT);
        $position = required_param('position', PARAM_TEXT);
        
        // Valida department
        $valid_departments = ['Pizzicaroli', 'Gesmundo', 'Remoto'];
        if (!in_array($department, $valid_departments)) {
            handle_error('Dipartimento non valido: ' . $department);
        }
        
        // Valida position
        $valid_positions = ['Preside', 'Insegnante', 'Alunno'];
        if (!in_array($position, $valid_positions)) {
            handle_error('Posizione non valida: ' . $position);
        }
        
        // Recupera gli ID dei campi personalizzati
        $dept_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_department'], 
            'id', MUST_EXIST
        );
        
        $pos_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_position'], 
            'id', MUST_EXIST
        );
        
        // Salva Department
        $success1 = save_user_profile_field($dept_field->id, $department);
        
        // Salva Position
        $success2 = save_user_profile_field($pos_field->id, $position);
        
        if ($success1 && $success2) {
            // Log dell'attività
            error_log("WhereAreYou: User {$USER->id} saved - Department: {$department}, Position: {$position}");
            
            send_json_response(true, [
                'department' => $department,
                'position' => $position,
                'message' => 'Dati salvati correttamente'
            ]);
        } else {
            handle_error('Errore durante il salvataggio dei dati');
        }
        
    } catch (Exception $e) {
        handle_error('Errore nel salvataggio', $e);
    }
}

/**
 * Gestisce il reset dei dati (per testing)
 */
function handle_reset_action() {
    global $DB, $USER;
    
    try {
        // Recupera gli ID dei campi personalizzati
        $dept_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_department'], 
            'id'
        );
        
        $pos_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_position'], 
            'id'
        );
        
        $success = true;
        
        // Elimina Department se esiste
        if ($dept_field) {
            $DB->delete_records('user_info_data', [
                'userid' => $USER->id,
                'fieldid' => $dept_field->id
            ]);
        }
        
        // Elimina Position se esiste
        if ($pos_field) {
            $DB->delete_records('user_info_data', [
                'userid' => $USER->id,
                'fieldid' => $pos_field->id
            ]);
        }
        
        // Log dell'attività
        error_log("WhereAreYou: User {$USER->id} reset data");
        
        send_json_response(true, [
            'message' => 'Dati resettati correttamente'
        ]);
        
    } catch (Exception $e) {
        handle_error('Errore nel reset dei dati', $e);
    }
}

/**
 * Salva un campo profilo utente
 * 
 * @param int $fieldid ID del campo
 * @param string $value Valore da salvare
 * @return bool Success
 */
function save_user_profile_field($fieldid, $value) {
    global $DB, $USER;
    
    try {
        // Controlla se esiste già un record
        $existing = $DB->get_record('user_info_data', [
            'userid' => $USER->id,
            'fieldid' => $fieldid
        ]);
        
        if ($existing) {
            // Aggiorna record esistente
            $existing->data = $value;
            return $DB->update_record('user_info_data', $existing);
        } else {
            // Crea nuovo record
            $record = new stdClass();
            $record->userid = $USER->id;
            $record->fieldid = $fieldid;
            $record->data = $value;
            return $DB->insert_record('user_info_data', $record);
        }
        
    } catch (Exception $e) {
        error_log("WhereAreYou: Error saving field {$fieldid} for user {$USER->id}: " . $e->getMessage());
        return false;
    }
}

/**
 * Recupera valore campo profilo utente
 * 
 * @param int $fieldid ID del campo
 * @return string Valore del campo
 */
function get_user_profile_field($fieldid) {
    global $DB, $USER;
    
    $record = $DB->get_record('user_info_data', [
        'userid' => $USER->id,
        'fieldid' => $fieldid
    ]);
    
    return $record ? $record->data : '';
}

/**
 * Funzione helper per debug (rimuovere in produzione)
 */
function debug_log($message, $data = null) {
    if (debugging() && has_capability('moodle/site:config', context_system::instance())) {
        $log = "WhereAreYou Debug: {$message}";
        if ($data) {
            $log .= " - Data: " . json_encode($data);
        }
        error_log($log);
    }
}