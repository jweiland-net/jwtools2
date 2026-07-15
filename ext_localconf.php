<?php

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

use JWeiland\Jwtools2\Fal\Filter\FileNameFilter;
use JWeiland\Jwtools2\Hooks\MoveTranslatedContentElementsHook;
use JWeiland\Jwtools2\Routing\Aspect\PersistedTableMapper;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$jwToolsConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('jwtools2');

// Create our own logger file
if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Jwtools2']['writerConfiguration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Jwtools2']['writerConfiguration'] = [
        LogLevel::INFO => [
            FileWriter::class => [
                'logFileInfix' => 'jwtools2',
            ],
        ],
    ];
}

if ($jwToolsConfiguration['typo3ExcludeVideoFilesFromFalFilter'] ?? false) {
    // Exclude .youtube and .vimeo from hidden files in filelist
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'] = [
        [
            FileNameFilter::class,
            'filterHiddenFilesAndFolders',
        ],
    ];
}

// Register an Aspect to store source/target-mapping. Will be activated, if used in SiteConfiguration only.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['PersistedTableMapper']
    = PersistedTableMapper::class;
