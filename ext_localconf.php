<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function () {
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
    );
    $jwToolsConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get('jwtools2');

    // Create our own logger file
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Jwtools2']['writerConfiguration'])) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Jwtools2']['writerConfiguration'] = [
            \Psr\Log\LogLevel::INFO => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                    'logFileInfix' => 'jwtools2',
                ],
            ],
        ];
    }

    if ($jwToolsConfiguration['solrEnable']) {
        // Add scheduler task to index all Solr Sites
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\JWeiland\Jwtools2\Task\IndexQueueWorkerTask::class] = [
            'extension' => 'jwtools2',
            'title' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_title',
            'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_description',
            'additionalFields' => \JWeiland\Jwtools2\Task\IndexQueueWorkerTaskAdditionalFieldProvider::class
        ];
        // override RealUrl Utility to reset current cached HTTP_HOST
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\DmitryDulepov\Realurl\Utility::class]['className'] = \JWeiland\Jwtools2\Utility\RealurlUtility::class;
        // Hook into Solr Index Service
        $signalSlotDispatcher->connect(
            \ApacheSolrForTypo3\Solr\Domain\Index\IndexService::class,
            'beforeIndexItem',
            \JWeiland\Jwtools2\Hooks\IndexService::class,
            'beforeIndexItem'
        );
        if ($jwToolsConfiguration['solrApplyPatches']) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Util::class]['className'] = \JWeiland\Jwtools2\XClasses\Util::class;
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\IndexQueue\Indexer::class]['className'] = \JWeiland\Jwtools2\XClasses\Indexer::class;
        }
    }

    if ($jwToolsConfiguration['enableSqlQueryTask']) {
        // Add scheduler task to execute SQL-Queries
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\JWeiland\Jwtools2\Task\ExecuteQueryTask::class] = [
            'extension' => 'jwtools2',
            'title' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.title',
            'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.description',
            'additionalFields' => \JWeiland\Jwtools2\Task\ExecuteQueryTaskAdditionalFieldProvider::class
        ];
    }

    if ($jwToolsConfiguration['typo3EnableUidInPageTree']) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
            'options.pageTree.showPageIdWithTitle = 1'
        );
    }

    if (
        $jwToolsConfiguration['typo3RequiredColumnsForFiles']
        || $jwToolsConfiguration['typo3UploadFieldsInTopOfEB']
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering']['jwtools2_file']
            = \JWeiland\Jwtools2\Backend\Browser\FileBrowser::class;
    }

    if ($jwToolsConfiguration['typo3UploadFieldsInTopOfEB']) {
        // For LinkHandler: overwrite TYPO3's LinkHandlers
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks']['jwtools2'] = [
            'handler' => \JWeiland\Jwtools2\Hooks\ModifyLinkHandlerHook::class
        ];
    }

    if ($jwToolsConfiguration['typo3ShowEditButtonInElementInformation']) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering']['jwtools']
            = \JWeiland\Jwtools2\Hooks\ModifyElementInformationHook::class;
    }

    if ($jwToolsConfiguration['enableLiveSearchPerformanceForAdmins']) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Search\LiveSearch\LiveSearch::class]['className'] = \JWeiland\Jwtools2\XClasses\LiveSearch\LiveSearch::class;
    }

    if ($jwToolsConfiguration['typo3ExcludeVideoFilesFromFalFilter']) {
        // Exclude .youtube and .vimeo from hidden files in filelist
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'] = [
            [
                \JWeiland\Jwtools2\Fal\Filter\FileNameFilter::class,
                'filterHiddenFilesAndFolders'
            ]
        ];
    }

    if ($jwToolsConfiguration['typo3ApplyFixForMoveTranslatedContentElements']) {
        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['jwtools2MoveTranslated']
                = \JWeiland\Jwtools2\Hooks\MoveTranslatedContentElementsHook::class;
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['jwtools2MoveTranslated']
                = \JWeiland\Jwtools2\Hooks\MoveTranslatedContentElementsHook::class;
        }
    }

    if ($jwToolsConfiguration['reduceCategoriesToPageTree']) {
        // Reduce categories to PIDs of current page tree
        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::class,
            \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::SIGNAL_PostProcessTreeData,
            \JWeiland\Jwtools2\Tca\ReduceCategoryTreeToPageTree::class,
            'reduceCategoriesToPageTree'
        );
    }

    if ($jwToolsConfiguration['enableContextMenuToUpdateFileMetadata']) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1622440501]
            = \JWeiland\Jwtools2\ContextMenu\ItemProviders\UpdateFileMetaDataProvider::class;
    }

    if ($jwToolsConfiguration['enableCachingFrameworkLogger']) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_cache_frontend_variablefrontend.php']['set'][1655965501]
            = \JWeiland\Jwtools2\Hooks\CachingFrameworkLoggerHook::class . '->analyze';
    }

    if (
        $jwToolsConfiguration['enableReportProvider']
        && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reports')
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['jwtools2'][] = \JWeiland\Jwtools2\Provider\ReportProvider::class;
    }

    // retrieve stdWrap current value into sub cObj. CONTENT
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit'][] = \JWeiland\Jwtools2\Hooks\InitializeStdWrap::class;

    // Register an Aspect to store source/target-mapping. Will be activated, if used in SiteConfiguration only.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['PersistedTableMapper'] = \JWeiland\Jwtools2\Routing\Aspect\PersistedTableMapper::class;
});
