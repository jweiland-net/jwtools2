<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$boot = function ($extensionKey) {
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
};
$boot($_EXTKEY);
unset($boot);
