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
        global $USER, $PAGE, $OUTPUT;
        
        // Only show modal for logged in users, not on login page
        if (!isloggedin() || isguestuser() || $PAGE->pagelayout === 'login') {
            return;
        }
        
        // Skip for AJAX requests and certain page types
        if (defined('AJAX_SCRIPT') || $PAGE->pagelayout === 'popup' || $PAGE->pagelayout === 'frametree') {
            return;
        }
        
        // Always show the modal (as requested)
        $context = \context_system::instance();
        $renderer = $PAGE->get_renderer('local_whereareyou');
        
        // Get current values
        $department = self::get_user_department($USER->id);
        $position = self::get_user_position($USER->id);
        
        // Get available options
        $department_options = self::get_department_options();
        $position_options = self::get_position_options();
        
        // Prepare template context
        $templatecontext = [
            'department_options' => $department_options,
            'position_options' => $position_options,
            'current_department' => $department,
            'current_position' => $position,
            'sesskey' => sesskey(),
            'wwwroot' => $CFG->wwwroot ?? '',
        ];
        
        // Add JavaScript module
        $PAGE->requires->js_call_amd('local_whereareyou/modal', 'init', [$templatecontext]);
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

