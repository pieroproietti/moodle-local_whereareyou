<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_whereareyou_save_data' => [
        'classname'    => 'local_whereareyou\external\save_data',
        'methodname'   => 'execute',
        'description'  => 'Save user department and position data',
        'type'         => 'write',
        'ajax'         => true,
        'loginrequired' => true,
    ],
    'local_whereareyou_reset_data' => [
        'classname'    => 'local_whereareyou\external\reset_data',
        'methodname'   => 'execute',
        'description'  => 'Reset user department and position data',
        'type'         => 'write',
        'ajax'         => true,
        'loginrequired' => true,
    ],
    'local_whereareyou_get_status' => [
        'classname'    => 'local_whereareyou\external\get_status',
        'methodname'   => 'execute',
        'description'  => 'Get current user status and data',
        'type'         => 'read',
        'ajax'         => true,
        'loginrequired' => true,
    ],
];

$services = [
    'WhereAreYou Services' => [
        'functions' => [
            'local_whereareyou_save_data',
            'local_whereareyou_reset_data',
            'local_whereareyou_get_status'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];