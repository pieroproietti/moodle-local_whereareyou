<?php
namespace local_whereareyou\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;

class reset_data extends external_api {
    
    public static function execute_parameters() {
        return new external_function_parameters([]);
    }
    
    public static function execute() {
        global $DB, $USER;
        
        $context = context_system::instance();
        self::validate_context($context);
        require_login();
        
        try {
            $dept_field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_department']);
            $pos_field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_position']);
            
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
            
            error_log("WhereAreYou WebService: User {$USER->id} reset data");
            
            return [
                'success' => true,
                'message' => 'Data reset successfully',
                'timestamp' => time()
            ];
            
        } catch (\Exception $e) {
            error_log("WhereAreYou WebService Reset Error: " . $e->getMessage());
            throw new \moodle_exception('reseterror', 'local_whereareyou', '', null, $e->getMessage());
        }
    }
    
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp'),
        ]);
    }
}