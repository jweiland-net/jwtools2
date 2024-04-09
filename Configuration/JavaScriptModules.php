<?php

return [
    'dependencies' => [
        'backend', 'core'
    ],
    'tags' => [
        'backend.module',
        'backend.contextmenu',
    ],
    'imports' => [
        '@jweiland/jwtools2/' => 'EXT:jwtools2/Resources/Public/JavaScript/',
    ],
];
