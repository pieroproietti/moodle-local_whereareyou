<?php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

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

function handle_error($message, $exception = null) {
    global $USER;
    
    $log_message = "WhereAreYou AJAX Error - User {$USER->id}: {$message}";
    if ($exception) {
        $log_message .= " - Exception: " . $exception->getMessage();
    }
    error_log($log_message);
    
    send_json_response(false, null, $message);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handle_error('Metodo non consentito');
    }
    
    require_login();
    
    if (!isloggedin() || isguestuser()) {
        handle_error('Accesso non autorizzato');
    }
    
    $sesskey = optional_param('sesskey', '', PARAM_ALPHANUM);
    if (!confirm_sesskey($sesskey)) {
        handle_error('Sessione non valida');
    }
    
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

function handle_save_action() {
    global $DB, $USER;
    
    try {
        $department = required_param('department', PARAM_TEXT);
        $position = required_param('position', PARAM_TEXT);
        
        $valid_departments = ['Pizzicaroli', 'Gesmundo', 'Remoto'];
        if (!in_array($department, $valid_departments)) {
            handle_error('Dipartimento non valido: ' . $department);
        }
        
        $valid_positions = ['Preside', 'Insegnante', 'Alunno'];
        if (!in_array($position, $valid_positions)) {
            handle_error('Posizione non valida: ' . $position);
        }
        
        $dept_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_department'], 
            'id', MUST_EXIST
        );
        
        $pos_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_position'], 
            'id', MUST_EXIST
        );
        
        $success1 = save_user_profile_field($dept_field->id, $department);
        $success2 = save_user_profile_field($pos_field->id, $position);
        
        if ($success1 && $success2) {
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

function handle_reset_action() {
    global $DB, $USER;
    
    try {
        $dept_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_department'], 
            'id'
        );
        
        $pos_field = $DB->get_record('user_info_field', 
            ['shortname' => 'whereareyou_position'], 
            'id'
        );
        
        if ($dept_field) {
            $DB->delete_records('user_info_data', [
                'userid' => $USER->id,
                'fieldid' => $dept_field->id
            ]);
        }
        
        if ($pos_field) {
            $DB->delete_records('user_info_data', [
                'userid' => $USER->id,
                'fieldid' => $pos_field->id
            ]);
        }
        
        error_log("WhereAreYou: User {$USER->id} reset data");
        
        send_json_response(true, [
            'message' => 'Dati resettati correttamente'
        ]);
        
    } catch (Exception $e) {
        handle_error('Errore nel reset dei dati', $e);
    }
}

function save_user_profile_field($fieldid, $value) {
    global $DB, $USER;
    
    try {
        $existing = $DB->get_record('user_info_data', [
            'userid' => $USER->id,
            'fieldid' => $fieldid
        ]);
        
        if ($existing) {
            $existing->data = $value;
            return $DB->update_record('user_info_data', $existing);
        } else {
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