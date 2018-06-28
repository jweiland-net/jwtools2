<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/** @var \JWeiland\Jwtools2\Configuration\ExtConf $extConf */
$extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \JWeiland\Jwtools2\Configuration\ExtConf::class
);

foreach ($extConf->getSolrTablesToAddKeywordBoosting() as $table) {
    $tempColumn = [
        \JWeiland\Jwtools2\Configuration\Settings::SOLR_BOOSTING_KEYWORDS_FIELD_NAME => [
            'exclude' => 1,
            'label' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang_db.xlf:'
                . \JWeiland\Jwtools2\Configuration\Settings::SOLR_BOOSTING_KEYWORDS_FIELD_NAME,
            'config' => [
                'type' => 'input',
                'size' => 48,
                'placeholder' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang_db.xlf:'
                    . \JWeiland\Jwtools2\Configuration\Settings::SOLR_BOOSTING_KEYWORDS_FIELD_NAME .
                    '.placeholder'
            ]
        ]
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $tempColumn);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        '--div--;Solr,' . \JWeiland\Jwtools2\Configuration\Settings::SOLR_BOOSTING_KEYWORDS_FIELD_NAME
    );
}
