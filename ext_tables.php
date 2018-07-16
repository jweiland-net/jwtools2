<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
    /** Register JW Tools module */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'JWeiland.jwtools2',
        'tools',
        'tools',
        '',
        [
            'Tools' => 'overview',
            'Solr' => 'list, show, createIndexQueueForAllSites, cleanUpSolrIndex'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:jwtools2/Resources/Public/Icons/module_tools.svg',
            'labels' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang_module_tools.xlf',
        ]
    );
}
