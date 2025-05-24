<?php
namespace local_whereareyou\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

class get_user_data extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([]);
    }

    public static function execute() {
        global $USER;
        
        $context = \context_system::instance();
        self::validate_context($context);
        
        require_capability('local/whereareyou:view', $context);
        
        $department = local_whereareyou_get_user_field_value($USER->id, 'department');
        $position = local_whereareyou_get_user_field_value($USER->id, 'position');
        
        return [
            'department' => $department,
            'position' => $position,
            'has_data' => !empty($department) && !empty($position)
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'department' => new external_value(PARAM_TEXT, 'User department'),
            'position' => new external_value(PARAM_TEXT, 'User position'),
            'has_data' => new external_value(PARAM_BOOL, 'Whether user has both fields filled')
        ]);
    }
}
