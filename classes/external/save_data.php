<?php
namespace local_whereareyou\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;
use stdClass;

class save_data extends external_api {
    
    public static function execute_parameters() {
        return new external_function_parameters([
            'department' => new external_value(PARAM_TEXT, 'User department'),
            'position' => new external_value(PARAM_TEXT, 'User position'),
        ]);
    }
    
    public static function execute($department, $position) {
        global $DB, $USER;
        
        $params = self::validate_parameters(self::execute_parameters(), [
            'department' => $department,
            'position' => $position
        ]);
        
        $context = context_system::instance();
        self::validate_context($context);
        require_login();
        
        $valid_departments = ['Pizzicaroli', 'Gesmundo', 'Remoto'];
        $valid_positions = ['Preside', 'Insegnante', 'Alunno'];
        
        if (!in_array($params['department'], $valid_departments)) {
            throw new \invalid_parameter_exception('Invalid department: ' . $params['department']);
        }
        
        if (!in_array($params['position'], $valid_positions)) {
            throw new \invalid_parameter_exception('Invalid position: ' . $params['position']);
        }
        
        try {
            $dept_field = $DB->get_record('user_info_field', 
                ['shortname' => 'whereareyou_department'], 
                'id', MUST_EXIST
            );
            
            $pos_field = $DB->get_record('user_info_field', 
                ['shortname' => 'whereareyou_position'], 
                'id', MUST_EXIST
            );
            
            self::save_user_profile_field($dept_field->id, $params['department']);
            self::save_user_profile_field($pos_field->id, $params['position']);
            
            error_log("WhereAreYou WebService: User {$USER->id} saved - Department: {$params['department']}, Position: {$params['position']}");
            
            return [
                'success' => true,
                'message' => 'Data saved successfully',
                'department' => $params['department'],
                'position' => $params['position'],
                'timestamp' => time()
            ];
            
        } catch (\Exception $e) {
            error_log("WhereAreYou WebService Error: " . $e->getMessage());
            throw new \moodle_exception('saveerror', 'local_whereareyou', '', null, $e->getMessage());
        }
    }
    
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'department' => new external_value(PARAM_TEXT, 'Saved department'),
            'position' => new external_value(PARAM_TEXT, 'Saved position'),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp'),
        ]);
    }
    
    private static function save_user_profile_field($fieldid, $value) {
        global $DB, $USER;
        
        $existing = $DB->get_record('user_info_data', [
            'userid' => $USER->id,
            'fieldid' => $fieldid
        ]);
        
        if ($existing) {
            $existing->data = $value;
            $DB->update_record('user_info_data', $existing);
        } else {
            $record = new stdClass();
            $record->userid = $USER->id;
            $record->fieldid = $fieldid;
            $record->data = $value;
            $DB->insert_record('user_info_data', $record);
        }
    }
}