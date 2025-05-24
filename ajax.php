<?php
define('AJAX_SCRIPT', true);
require_once('../../config.php');

require_login();
require_sesskey();

$action = required_param('action', PARAM_ALPHA);

if ($action === 'save') {
    $department = required_param('department', PARAM_TEXT);
    $position = required_param('position', PARAM_TEXT);
    
    // Save to custom profile fields
    $success = true;
    
    // Save department
    $field = $DB->get_record('user_info_field', ['shortname' => 'department']);
    if ($field) {
        $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $field->id]);
        if ($data) {
            $data->data = $department;
            $DB->update_record('user_info_data', $data);
        } else {
            $data = new stdClass();
            $data->userid = $USER->id;
            $data->fieldid = $field->id;
            $data->data = $department;
            $data->dataformat = 0;
            $DB->insert_record('user_info_data', $data);
        }
    }
    
    // Save position
    $field = $DB->get_record('user_info_field', ['shortname' => 'position']);
    if ($field) {
        $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $field->id]);
        if ($data) {
            $data->data = $position;
            $DB->update_record('user_info_data', $data);
        } else {
            $data = new stdClass();
            $data->userid = $USER->id;
            $data->fieldid = $field->id;
            $data->data = $position;
            $data->dataformat = 0;
            $DB->insert_record('user_info_data', $data);
        }
    }
    
    // Also save to user preferences for quick access
    set_user_preference('local_whereareyou_department', $department);
    set_user_preference('local_whereareyou_position', $position);
    
    echo json_encode(['success' => $success]);
    
} elseif ($action === 'update_last_shown') {
    // Aggiorna il timestamp dell'ultima visualizzazione della modale
    set_user_preference('local_whereareyou_last_shown', time());
    
    // Segna come mostrata in questa sessione
    $session_key = 'local_whereareyou_shown_' . session_id();
    set_user_preference($session_key, time());
    
    echo json_encode(['success' => true]);
    
} elseif ($action === 'check_preferences') {
    // Controlla le preferenze attuali
    $session_key = 'local_whereareyou_shown_' . session_id();
    $session_shown = get_user_preferences($session_key, 0, $USER->id);
    $last_shown = get_user_preferences('local_whereareyou_last_shown', 0, $USER->id);
    
    echo json_encode([
        'success' => true,
        'session_key' => $session_key,
        'session_shown' => $session_shown,
        'session_shown_date' => $session_shown ? date('Y-m-d H:i:s', $session_shown) : 'Mai',
        'last_shown' => $last_shown,
        'last_shown_date' => $last_shown ? date('Y-m-d H:i:s', $last_shown) : 'Mai',
        'session_id' => session_id(),
        'user_id' => $USER->id
    ]);
    
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
