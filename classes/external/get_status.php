<?php
namespace local_whereareyou\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;

class get_status extends external_api {
    
    public static function execute_parameters() {
        return new external_function_parameters([]);
    }
    
    public static function execute() {
        global $DB, $USER, $SESSION;
        
        $context = context_system::instance();
        self::validate_context($context);
        require_login();
        
        try {
            $dept_field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_department']);
            $pos_field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_position']);
            
            $department = '';
            $position = '';
            
            if ($dept_field) {
                $data = $DB->get_record('user_info_data', [
                    'userid' => $USER->id,
                    'fieldid' => $dept_field->id
                ]);
                $department = $data ? $data->data : '';
            }
            
            if ($pos_field) {
                $data = $DB->get_record('user_info_data', [
                    'userid' => $USER->id,
                    'fieldid' => $pos_field->id
                ]);
                $position = $data ? $data->data : '';
            }
            
            return [
                'success' => true,
                'department' => $department,
                'position' => $position,
                'modal_shown' => isset($SESSION->whereareyou_modal_shown),
                'timestamp' => time()
            ];
            
        } catch (\Exception $e) {
            throw new \moodle_exception('statuserror', 'local_whereareyou', '', null, $e->getMessage());
        }
    }
    
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'department' => new external_value(PARAM_TEXT, 'Current department'),
            'position' => new external_value(PARAM_TEXT, 'Current position'),
            'modal_shown' => new external_value(PARAM_BOOL, 'Modal already shown in session'),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp'),
        ]);
    }
}