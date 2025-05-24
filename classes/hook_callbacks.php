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
        
        // Prepare template context
        $templatecontext = [
            'department_options' => $department_options,
            'position_options' => $position_options,
            'current_department' => $department,
            'current_position' => $position,
            'sesskey' => sesskey(),
            'wwwroot' => $CFG->wwwroot,
        ];
        
        // Simple approach: Add script directly to head
        $js_config = json_encode($templatecontext);
        $script = "
        <script>
        console.log('WhereAreYou: Hook called for user {$USER->id}');
        
        // Store config globally
        window.whereAreYouConfig = {$js_config};
        
        // Function to initialize modal
        function initWhereAreYouModal() {
            if (typeof require !== 'undefined') {
                console.log('WhereAreYou: Loading modal module');
                require(['local_whereareyou/modal'], function(modal) {
                    console.log('WhereAreYou: Modal module loaded');
                    modal.init(window.whereAreYouConfig);
                }, function(error) {
                    console.error('WhereAreYou: Failed to load modal', error);
                });
            } else {
                console.log('WhereAreYou: RequireJS not ready, retrying...');
                setTimeout(initWhereAreYouModal, 500);
            }
        }
        
        // Initialize when page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initWhereAreYouModal, 100);
            });
        } else {
            setTimeout(initWhereAreYouModal, 100);
        }
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

