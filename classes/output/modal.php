<?php
namespace local_whereareyou\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

class modal implements renderable, templatable {
    private $departments;
    private $positions;
    private $current_department;
    private $current_position;

    public function __construct($departments = [], $positions = [], $current_department = '', $current_position = '') {
        $this->departments = $departments;
        $this->positions = $positions;
        $this->current_department = $current_department;
        $this->current_position = $current_position;
    }

    public function export_for_template(renderer_base $output) {
        global $USER;
        
        $data = new stdClass();
        $data->title = get_string('modal_title', 'local_whereareyou');
        $data->description = get_string('modal_description', 'local_whereareyou');
        $data->department_label = get_string('department', 'local_whereareyou');
        $data->position_label = get_string('position', 'local_whereareyou');
        $data->save_button = get_string('save', 'local_whereareyou');
        $data->close_button = get_string('close', 'local_whereareyou');
        $data->skip_button = get_string('skip', 'local_whereareyou');
        $data->select_department = get_string('select_department', 'local_whereareyou');
        $data->select_position = get_string('select_position', 'local_whereareyou');
        $data->userid = $USER->id;
        $data->sesskey = sesskey();
        
        // Prepare department options
        $data->departments = [];
        foreach ($this->departments as $dept) {
            $data->departments[] = [
                'value' => $dept,
                'text' => $dept,
                'selected' => ($dept === $this->current_department)
            ];
        }
        
        // Prepare position options
        $data->positions = [];
        foreach ($this->positions as $pos) {
            $data->positions[] = [
                'value' => $pos,
                'text' => $pos,
                'selected' => ($pos === $this->current_position)
            ];
        }
        
        return $data;
    }
}
