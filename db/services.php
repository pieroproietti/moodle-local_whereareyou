<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_whereareyou_get_user_data' => array(
        'classname'   => 'local_whereareyou\external\get_user_data',
        'methodname'  => 'execute',
        'description' => 'Get user department and position data',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ),
    'local_whereareyou_save_user_data' => array(
        'classname'   => 'local_whereareyou\external\save_user_data',
        'methodname'  => 'execute',
        'description' => 'Save user department and position data',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    )
);
