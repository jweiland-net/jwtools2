<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'jwtools2',
    'tools',
    'tools',
    '',
    [
        \JWeiland\Jwtools2\Controller\ToolsController::class => 'overview',
        \JWeiland\Jwtools2\Controller\SolrController::class => 'list, show, showIndexQueue, indexOneRecord, createIndexQueueForAllSites, showClearFullIndexForm, showClearIndexForm, clearIndex',
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:jwtools2/Resources/Public/Icons/module_tools.svg',
        'labels' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang_module_tools.xlf',
    ]
);
