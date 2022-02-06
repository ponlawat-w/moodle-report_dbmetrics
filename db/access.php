<?php

defined('MOODLE_INTERNAL') or die();

$capabilities = [
    'report/dbmetrics:view' => [
        'captype' => 'view',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ]
];
