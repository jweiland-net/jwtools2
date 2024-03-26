<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'JW tools',
    'description' => 'Jwtools2 contains a scheduler task for Solr to index multiple Pagetrees and a task to execute 
        SQL-Queries. Further there are settings to enable some features in TYPO3 like showing the Page UID in Pagetree
        with a simple click in extensionmanager.',
    'category' => 'module',
    'author' => 'Stefan Froemken, Hoja Mustaffa Abdul Latheef',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '7.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.9-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'solr' => '12.0.0-0.0.0',
        ],
    ],
];
