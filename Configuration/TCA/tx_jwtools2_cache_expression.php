<?php

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'ctrl' => [
        'title' => 'Cache Expressions',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'adminOnly' => true,
        'rootLevel' => 1,
        'versioningWS' => false,
        'typeicon_classes' => [
            'default' => 'actions-filter',
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'title, is_regexp,
                --palette--;Exception Handling;exception,
                expression',
        ],
    ],
    'palettes' => [
        'exception' => ['showitem' => 'is_exception, exception_fe_only'],
    ],
    'columns' => [
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim, required',
            ],
        ],
        'is_regexp' => [
            'label' => 'Is regular expression?',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'is_exception' => [
            'label' => 'Throw exception?',
            'description' => 'Deactivated: just protocol the match. ' .
                'Activated: protocol the match and throws exception to prevent inserting invalid cache entries. No further caches will be analyzed.',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'exception_fe_only' => [
            'label' => 'Throw exception in frontend only?',
            'description' => 'If "throw exception" is activated, it may also throw exceptions in backend, because of page previews. Activate to throw exception in frontend only.',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'expression' => [
            'label' => 'Expression',
            'description' => 'For non regular expressions it searches with PHP:strpos. For regular expressions it uses PHP:preg_match. No need to add or escape any delimiter. It uses "/" internally.',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim, required',
            ],
        ],
    ],
];
