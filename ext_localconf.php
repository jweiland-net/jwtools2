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

    if ($jwToolsConfiguration['typo3UploadFieldsInTopOfEB']) {
        // for LinkHandler
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Recordlist\LinkHandler\FileLinkHandler::class]['className'] = \JWeiland\Jwtools2\XClasses\LinkHandler\FileLinkHandler::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Recordlist\LinkHandler\FolderLinkHandler::class]['className'] = \JWeiland\Jwtools2\XClasses\LinkHandler\FolderLinkHandler::class;

        // for ElementBrowser
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Recordlist\Browser\FileBrowser::class]['className'] = \JWeiland\Jwtools2\XClasses\Browser\FileBrowser::class;
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
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1622440501] = \JWeiland\Jwtools2\ContextMenu\ItemProviders\UpdateFileMetaDataProvider::class;
    }

    // retrieve stdWrap current value into sub cObj. CONTENT
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit'][] = \JWeiland\Jwtools2\Hooks\InitializeStdWrap::class;

    // Register an Aspect to store source/target-mapping. Will be activated, if used in SiteConfiguration only.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['PersistedTableMapper'] = \JWeiland\Jwtools2\Routing\Aspect\PersistedTableMapper::class;
});
