<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Hook to add modal to page after login
 */
function local_whereareyou_before_footer() {
    global $PAGE, $USER, $DB, $OUTPUT;
    
    // Only show on pages after login, not on login page itself
    if (!isloggedin() || isguestuser() || $PAGE->pagelayout == 'login') {
        return;
    }
    
    // Skip for admin pages
    if (strpos($PAGE->url->get_path(), '/admin/') !== false) {
        return;
    }
    
    // Get custom field data
    $departments = local_whereareyou_get_department_options();
    $positions = local_whereareyou_get_position_options();
    
    // Get current user values
    $current_department = local_whereareyou_get_user_field_value($USER->id, 'department');
    $current_position = local_whereareyou_get_user_field_value($USER->id, 'position');
    
    // Create modal renderer
    $modal = new \local_whereareyou\output\modal($departments, $positions, $current_department, $current_position);
    
    // Render modal
    echo $OUTPUT->render($modal);
    
    // Include JavaScript
    $PAGE->requires->js_call_amd('local_whereareyou/modal', 'init');
}

/**
 * Get department options from custom field
 */
function local_whereareyou_get_department_options() {
    global $DB;
    
    $field = $DB->get_record('user_info_field', ['shortname' => 'department']);
    if ($field && $field->param1) {
        return array_filter(explode("\n", trim($field->param1)));
    }
    
    return ['Pizzicaroli', 'Gesmundo', 'Remoto'];
}

/**
 * Get position options from custom field
 */
function local_whereareyou_get_position_options() {
    global $DB;
    
    $field = $DB->get_record('user_info_field', ['shortname' => 'position']);
    if ($field && $field->param1) {
        return array_filter(explode("\n", trim($field->param1)));
    }
    
    return ['Preside', 'Insegnante', 'Alunno'];
}

/**
 * Get user field value
 */
function local_whereareyou_get_user_field_value($userid, $fieldname) {
    global $DB;
    
    $field = $DB->get_record('user_info_field', ['shortname' => $fieldname]);
    if (!$field) {
        return '';
    }
    
    $data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
    return $data ? $data->data : '';
}

/**
 * Save user field value
 */
function local_whereareyou_save_user_field_value($userid, $fieldname, $value) {
    global $DB;
    
    $field = $DB->get_record('user_info_field', ['shortname' => $fieldname]);
    if (!$field) {
        return false;
    }
    
    $existing = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
    
    if ($existing) {
        $existing->data = $value;
        return $DB->update_record('user_info_data', $existing);
    } else {
        $data = new stdClass();
        $data->userid = $userid;
        $data->fieldid = $field->id;
        $data->data = $value;
        return $DB->insert_record('user_info_data', $data);
    }
}
