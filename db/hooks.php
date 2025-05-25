<?php
defined('MOODLE_INTERNAL') || die();

$hooks = [
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => [\local_whereareyou\hook_callbacks::class, 'before_footer_html_generation'],
        'priority' => 0,
    ],
];