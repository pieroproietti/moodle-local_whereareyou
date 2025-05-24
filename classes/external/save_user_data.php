<?php
namespace local_whereareyou\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

class save_user_data extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'department' => new external_value(PARAM_TEXT, 'Department value'),
            'position' => new external_value(PARAM_TEXT, 'Position value')
        ]);
    }

    public static function execute($department, $position) {
        global $USER;
        
        $params = self::validate_parameters(self::execute_parameters(), [
            'department' => $department,
            'position' => $position
        ]);
        
        $context = \context_system::instance();
        self::validate_context($context);
        
        require_capability('local/whereareyou:view', $context);
        
        try {
            $dept_saved = local_whereareyou_save_user_field_value($USER->id, 'department', $params['department']);
            $pos_saved = local_whereareyou_save_user_field_value($USER->id, 'position', $params['position']);
            
            return [
                'success' => $dept_saved && $pos_saved,
                'message' => $dept_saved && $pos_saved ? 
                    get_string('save_success', 'local_whereareyou') : 
                    get_string('save_error', 'local_whereareyou')
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether save was successful'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message')
        ]);
    }
}
