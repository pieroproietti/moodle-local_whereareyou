<?php
defined('MOODLE_INTERNAL') || die();

// Add settings page to admin menu
if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_whereareyou_test',
        get_string('test_page', 'local_whereareyou'),
        new moodle_url('/local/whereareyou/test.php'),
        'moodle/site:config'
    ));
    
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_whereareyou_debug',
        'WhereAreYou Debug',
        new moodle_url('/local/whereareyou/debug.php'),
        'moodle/site:config'
    ));
}