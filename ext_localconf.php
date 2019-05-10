<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function($extensionKey) {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $jwToolsConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey]);

        if ($jwToolsConfiguration['solrEnable']) {
            // Add scheduler task to index all Solr Sites
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['JWeiland\\Jwtools2\\Task\\IndexQueueWorkerTask'] = [
                'extension' => $extensionKey,
                'title' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_title',
                'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_description',
                'additionalFields' => 'JWeiland\\Jwtools2\\Task\\IndexQueueWorkerTaskAdditionalFieldProvider'
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
        }

        if ($jwToolsConfiguration['enableSqlQueryTask']) {
            // Add scheduler task to execute SQL-Queries
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['JWeiland\\Jwtools2\\Task\\ExecuteQueryTask'] = [
                'extension' => $extensionKey,
                'title' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.title',
                'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.description',
                'additionalFields' => 'JWeiland\\Jwtools2\\Task\\ExecuteQueryTaskAdditionalFieldProvider'
            ];
        }

        if ($jwToolsConfiguration['typo3EnableUidInPageTree']) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
                'options.pageTree.showPageIdWithTitle = 1'
            );
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

        // retrieve stdWrap current value into sub cObj. CONTENT
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit'][] = \JWeiland\Jwtools2\Hooks\InitializeStdWrap::class;

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['Jwtools2-SolrCommandController'] = \JWeiland\Jwtools2\Command\SolrCommandController::class;
    },
    'jwtools2'
);
