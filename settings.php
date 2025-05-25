<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_whereareyou_test',
        get_string('testpage', 'local_whereareyou'),
        new moodle_url('/local/whereareyou/test.php'),
        'moodle/site:config'
    ));
}