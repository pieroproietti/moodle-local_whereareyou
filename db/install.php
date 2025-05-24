// ============================================================================
// FILE: db/install.php
// ============================================================================
<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_whereareyou_install() {
    global $DB;
    
    // Get or create default category for profile fields
    $category = $DB->get_record('user_info_category', ['name' => 'Dove Sei Tu']);
    if (!$category) {
        $category = new stdClass();
        $category->name = 'Dove Sei Tu';
        $category->sortorder = 1;
        $categoryid = $DB->insert_record('user_info_category', $category);
    } else {
        $categoryid = $category->id;
    }
    
    // Create custom profile field for Department
    if (!$DB->record_exists('user_info_field', ['shortname' => 'department'])) {
        $field = new stdClass();
        $field->shortname = 'department';
        $field->name = 'Department';
        $field->description = 'User department';
        $field->descriptionformat = 1;
        $field->datatype = 'menu';
        $field->categoryid = $categoryid;
        $field->sortorder = 1;
        $field->required = 0;
        $field->locked = 0;
        $field->visible = 2; // Visible to everyone
        $field->forceunique = 0;
        $field->signup = 0;
        $field->defaultdata = '';
        $field->defaultdataformat = 0;
        $field->param1 = "Pizzicaroli\nGesmundo\nRemoto"; // Menu options
        $field->param2 = ''; 
        $field->param3 = ''; 
        $field->param4 = ''; 
        $field->param5 = '';
        
        $DB->insert_record('user_info_field', $field);
    }
    
    // Create custom profile field for Position
    if (!$DB->record_exists('user_info_field', ['shortname' => 'position'])) {
        $field = new stdClass();
        $field->shortname = 'position';
        $field->name = 'Position';
        $field->description = 'User position';
        $field->descriptionformat = 1;
        $field->datatype = 'menu';
        $field->categoryid = $categoryid;
        $field->sortorder = 2;
        $field->required = 0;
        $field->locked = 0;
        $field->visible = 2; // Visible to everyone
        $field->forceunique = 0;
        $field->signup = 0;
        $field->defaultdata = '';
        $field->defaultdataformat = 0;
        $field->param1 = "Preside\nInsegnante\nAlunno"; // Menu options
        $field->param2 = ''; 
        $field->param3 = ''; 
        $field->param4 = ''; 
        $field->param5 = '';
        
        $DB->insert_record('user_info_field', $field);
    }
    
    return true;
}
