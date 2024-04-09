<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

use JWeiland\Jwtools2\Fal\Filter\FileNameFilter;
use JWeiland\Jwtools2\Hooks\CachingFrameworkLoggerHook;
use JWeiland\Jwtools2\Hooks\InitializeStdWrap;
use JWeiland\Jwtools2\Hooks\ModifyElementInformationHook;
use JWeiland\Jwtools2\Hooks\MoveTranslatedContentElementsHook;
use JWeiland\Jwtools2\Routing\Aspect\PersistedTableMapper;
use JWeiland\Jwtools2\Task\ExecuteQueryTask;
use JWeiland\Jwtools2\Task\ExecuteQueryTaskAdditionalFieldProvider;
use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use JWeiland\Jwtools2\Task\IndexQueueWorkerTaskAdditionalFieldProvider;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(static function () {
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

    if ($jwToolsConfiguration['solrEnable'] ?? false) {
        // Add scheduler task to index all Solr Sites
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][IndexQueueWorkerTask::class] = [
            'extension' => 'jwtools2',
            'title' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_title',
            'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_description',
            'additionalFields' => IndexQueueWorkerTaskAdditionalFieldProvider::class,
        ];
    }

    if ($jwToolsConfiguration['enableSqlQueryTask'] ?? false) {
        // Add scheduler task to execute SQL-Queries
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ExecuteQueryTask::class] = [
            'extension' => 'jwtools2',
            'title' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.title',
            'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.description',
            'additionalFields' => ExecuteQueryTaskAdditionalFieldProvider::class,
        ];
    }

    if ($jwToolsConfiguration['typo3EnableUidInPageTree'] ?? false) {
        ExtensionManagementUtility::addUserTSConfig(
            'options.pageTree.showPageIdWithTitle = 1'
        );
    }

    if ($jwToolsConfiguration['typo3ShowEditButtonInElementInformation'] ?? false) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering']['jwtools']
            = ModifyElementInformationHook::class;
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

    if ($jwToolsConfiguration['typo3ApplyFixForMoveTranslatedContentElements'] ?? false) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['jwtools2MoveTranslated']
            = MoveTranslatedContentElementsHook::class;
    }

    if ($jwToolsConfiguration['enableCachingFrameworkLogger'] ?? false) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_cache_frontend_variablefrontend.php']['set'][1655965501]
            = CachingFrameworkLoggerHook::class . '->analyze';
    }

    // Retrieve stdWrap current value into sub cObj. CONTENT
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']['jwtools2_initStdWrap']
        = InitializeStdWrap::class;

    // Register an Aspect to store source/target-mapping. Will be activated, if used in SiteConfiguration only.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['PersistedTableMapper']
        = PersistedTableMapper::class;
});
