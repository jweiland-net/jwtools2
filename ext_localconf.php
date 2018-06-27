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

        if ($jwToolsConfiguration['typo3EnableUidInPageTree']) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
                'options.pageTree.showPageIdWithTitle = 1'
            );
        }

        // retrieve stdWrap current value into sub cObj. CONTENT
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit'][] = \JWeiland\Jwtools2\Hooks\InitializeStdWrap::class;

        // Solr Tools
        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class,
            'tablesDefinitionIsBeingBuilt',
            \JWeiland\Jwtools2\Tca\SolrBoostingKeywordRegistry::class,
            'addBoostingKeywordFieldToAffectedTables'
        );

        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Extensionmanager\Controller\ConfigurationController::class,
            'afterExtensionConfigurationWrite',
            \JWeiland\Jwtools2\Slot\ExtensionConfigurationSlot::class,
            'updateDatabase'
        );

        if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
            $signalSlotDispatcher->connect(
                \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
                'tablesDefinitionIsBeingBuilt',
                \JWeiland\Jwtools2\Tca\SolrBoostingKeywordRegistry::class,
                'addBoostingKeywordFieldToAffectedTables'
            );
        }
    },
    'jwtools2'
);
