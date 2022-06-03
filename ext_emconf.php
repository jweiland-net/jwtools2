<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'JW tools',
    'description' => 'Jwtools2 contains a scheduler task for Solr to index multiple Pagetrees and a task to execute 
        SQL-Queries. Further there are settings to enable some features in TYPO3 like showing the Page UID in Pagetree
        with a simple click in extensionmanager.',
    'category' => 'module',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '5.6.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.17-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'solr' => '11.0.4-0.0.0',
        ],
    ],
];
