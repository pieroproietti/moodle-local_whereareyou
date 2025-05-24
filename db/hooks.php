// ============================================================================
// FILE: db/hooks.php
// ============================================================================
<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => 'local_whereareyou\hook_callbacks::before_standard_head_html_generation',
        'priority' => 500,
    ],
];
