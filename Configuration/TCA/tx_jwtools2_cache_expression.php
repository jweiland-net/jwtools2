<?php

return [
    'ctrl' => [
        'title' => 'Cache Expressions',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'adminOnly' => true,
        'rootLevel' => 1,
        'versioningWS' => false,
    ],
    'types' => [
        '1' => [
            'showitem' => 'title, is_regexp, expression',
        ],
    ],
    'palettes' => [],
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
                'default' => 0,
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
