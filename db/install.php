<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_whereareyou_install() {
    global $DB;
    
    // Crea categoria per i campi personalizzati
    $category = new stdClass();
    $category->name = get_string('pluginname', 'local_whereareyou');
    $category->sortorder = $DB->count_records('user_info_category') + 1;
    $categoryid = $DB->insert_record('user_info_category', $category);
    
    // Campo Department
    $field_department = new stdClass();
    $field_department->shortname = 'whereareyou_department';
    $field_department->name = get_string('department', 'local_whereareyou');
    $field_department->datatype = 'menu';
    $field_department->categoryid = $categoryid;
    $field_department->sortorder = 1;
    $field_department->required = 0;
    $field_department->locked = 0;
    $field_department->visible = 2;
    $field_department->forceunique = 0;
    $field_department->signup = 0;
    $field_department->defaultdata = '';
    $field_department->param1 = "Pizzicaroli\nGesmundo\nRemoto";
    $deptid = $DB->insert_record('user_info_field', $field_department);
    
    // Campo Position
    $field_position = new stdClass();
    $field_position->shortname = 'whereareyou_position';
    $field_position->name = get_string('position', 'local_whereareyou');
    $field_position->datatype = 'menu';
    $field_position->categoryid = $categoryid;
    $field_position->sortorder = 2;
    $field_position->required = 0;
    $field_position->locked = 0;
    $field_position->visible = 2;
    $field_position->forceunique = 0;
    $field_position->signup = 0;
    $field_position->defaultdata = '';
    $field_position->param1 = "Preside\nInsegnante\nAlunno";
    $posid = $DB->insert_record('user_info_field', $field_position);
    
    return true;
}