<?php

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'dependencies' => [
        'backend', 'core',
    ],
    'tags' => [
        'backend.module',
        'backend.contextmenu',
    ],
    'imports' => [
        '@jweiland/jwtools2/' => 'EXT:jwtools2/Resources/Public/JavaScript/',
    ],
];
