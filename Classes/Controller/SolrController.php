<?php
namespace JWeiland\Jwtools2\Controller;

/*
* This file is part of the jwtools2 project.
*
* It is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License, either version 2
* of the License, or any later version.
*
* For the full copyright and license information, please read the
* LICENSE.txt file that was distributed with this source code.
*
* The TYPO3 project - inspiring people to share!
*/

use ApacheSolrForTypo3\Solr\IndexQueue\Indexer;
use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use ApacheSolrForTypo3\Solr\Site;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use JWeiland\Jwtools2\Backend\SolrDocHeader;
use JWeiland\Jwtools2\Domain\Repository\SchedulerRepository;
use JWeiland\Jwtools2\Domain\Repository\SolrRepository;
use JWeiland\Jwtools2\Service\SolrService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\NotFoundView;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Class SolrController
 */
class SolrController extends AbstractController
{
    /**
     * @var SolrRepository
     */
    protected $solrRepository;

    /**
     * @var SchedulerRepository
     */
    protected $schedulerRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * inject solrRepository
     *
     * @param SolrRepository $solrRepository
     */
    public function injectSolrRepository(SolrRepository $solrRepository)
    {
        $this->solrRepository = $solrRepository;
    }

    /**
     * inject schedulerRepository
     *
     * @param SchedulerRepository $schedulerRepository
     */
    public function injectSchedulerRepository(SchedulerRepository $schedulerRepository)
    {
        $this->schedulerRepository = $schedulerRepository;
    }

    /**
     * inject registry
     *
     * @param Registry $registry
     */
    public function injectRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Pre-Execute some scripts
     *
     * @param ViewInterface $view
     */
    public function initializeView(ViewInterface $view)
    {
        if (!$view instanceof NotFoundView) {
            parent::initializeView($view);

            /** @var SolrDocHeader $docHeader */
            $docHeader = $this->objectManager->get(SolrDocHeader::class, $this->request, $view);
            $docHeader->renderDocHeader();

            if (!$this->schedulerRepository->findSolrSchedulerTask()) {
                $this->addFlashMessage('No or wrong scheduler task UID configured in ExtensionManager Configuration of jwtools2', 'Missing or wrong configuration', FlashMessage::WARNING);
            }
        }
    }

    /**
     * List action
     */
    public function listAction()
    {
        $this->view->assign('sites', $this->solrRepository->findAllAvailableSites());
        $this->view->assign('currentRootPageUid', $this->registry->get('jwtools2-solr', 'rootPageId', 0));
    }

    /**
     * Show action
     *
     * @param int $rootPageUid
     * @param int $languageUid
     */
    public function showAction($rootPageUid, $languageUid = 0)
    {
        $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
        $this->view->assign('site', $site);
        $this->view->assign('memoryPeakUsage', $this->registry->get('jwtools2-solr', 'memoryPeakUsage', 0));
    }

    /**
     * Show index queue configuration action
     *
     * @param int $rootPageUid
     * @param string $configurationName
     * @param int $languageUid
     */
    public function showIndexQueueAction(int $rootPageUid, string $configurationName, int $languageUid = 0)
    {
        $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
        $solrConfiguration = $site->getSolrConfiguration()->getIndexQueueConfigurationByName($configurationName);
        $this->view->assign('site', $site);
        $this->view->assign('solrConfiguration', $solrConfiguration);
        $this->view->assign('configurationName', $configurationName);
    }

    /**
     * Index one special record by configuration name and site
     *
     * @param int $rootPageUid
     * @param string $configurationName
     * @param int $recordUid
     * @param int $languageUid
     */
    public function indexOneRecordAction(int $rootPageUid, string $configurationName, int $recordUid, int $languageUid = 0)
    {
        $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
        $item = $this->getIndexQueueItem($rootPageUid, $configurationName, $recordUid);
        if ($item instanceof Item) {
            $this->indexItem($item, $site->getSolrConfiguration());
        }

        $this->redirect(
            'showIndexQueue',
            'Solr',
            'jwtools2',
            [
                'rootPageUid' => $rootPageUid,
                'configurationName' => $configurationName,
                'languageUid' => $languageUid
            ]
        );
    }

    /**
     * Creates index queue for all sites
     */
    public function createIndexQueueForAllSitesAction()
    {
        /** @var SolrService $solrService */
        $solrService = GeneralUtility::makeInstance(SolrService::class);
        $result = $solrService->createIndexQueueForSites();

        $this->view->assign('errors', $result['errors']);
        $this->view->assign('totalItemsAddedToIndexQueue', $result['totalItemsAddedToIndexQueue']);
    }

    /**
     * @param int $rootPageUid
     */
    public function showClearIndexFormAction($rootPageUid)
    {
        $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
        if ($site instanceof Site) {
            $this->view->assign('site', $site);
            $this->view->assign('enabledConfigurationNames', $site->getSolrConfiguration()->getEnabledIndexQueueConfigurationNames());
        } else {
            $this->addFlashMessage(
                $rootPageUid . ' is no valid RootPage UID',
                'Invalid RootPage UID',
                AbstractMessage::WARNING
            );
            $this->redirect('list');
        }
    }

    /**
     * @param int $rootPageUid
     * @param array $configurationNames
     * @param array $clear
     * @validate $configurationNames NotEmpty
     * @validate $clear NotEmpty
     */
    public function clearIndexAction($rootPageUid, $configurationNames, array $clear)
    {
        /** @var SolrService $solrService */
        $solrService = GeneralUtility::makeInstance(SolrService::class);
        $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
        if ($site instanceof Site) {
            foreach ($configurationNames as $configurationName) {
                $solrService->clearIndexByType($site, $configurationName, $clear);
            }
            $this->addFlashMessage(
                'We successfully have cleared the index of Site: "' . $site->getTitle() . '"',
                'Index cleared',
                FlashMessage::OK
            );
            $this->redirect('list');
        } else {
            $this->addFlashMessage(
                'We haven\'t found a Site with RootPage UID: ' . $rootPageUid,
                'Site not found',
                AbstractMessage::WARNING
            );
        }
    }

    /**
     * Show a form to clear full index
     */
    public function showClearFullIndexFormAction()
    {
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = $this->objectManager->get(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Jwtools2/ClearFullIndex');

        $configurationNamesOfAllSites = [];
        $sites = $this->solrRepository->findAllAvailableSites();
        foreach ($sites as $site) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $configurationNamesOfAllSites,
                $site->getSolrConfiguration()->getEnabledIndexQueueConfigurationNames()
            );
        }
        $this->view->assign('sites', $sites);
        $this->view->assign('configurationNamesOfAllSites', $configurationNamesOfAllSites);
    }

    /**
     * @param int $rootPageUid
     * @param string $configurationName
     * @param int $recordUid
     * @return Item|object|null
     */
    protected function getIndexQueueItem(int $rootPageUid, string $configurationName, int $recordUid)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $indexQueueItem = $queryBuilder
            ->select('*')
            ->from('tx_solr_indexqueue_item')
            ->where(
                $queryBuilder->expr()->eq(
                    'root',
                    $queryBuilder->createNamedParameter($rootPageUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'indexing_configuration',
                    $queryBuilder->createNamedParameter($configurationName, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'item_uid',
                    $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        if ($indexQueueItem === false) {
            return null;
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($indexQueueItem['item_type']);
        $tableRecord = $queryBuilder
            ->select('*')
            ->from($indexQueueItem['item_type'])
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        if ($tableRecord === false) {
            return null;
        }

        return GeneralUtility::makeInstance(
            Item::class,
            $indexQueueItem,
            $tableRecord
        );
    }

    /**
     * Indexes an item from the Index Queue.
     *
     * @param Item $item An index queue item to index
     * @param TypoScriptConfiguration $configuration
     * @return bool TRUE if the item was successfully indexed, FALSE otherwise
     */
    protected function indexItem(Item $item, TypoScriptConfiguration $configuration)
    {
        $indexer = $this->getIndexerByItem($item->getIndexingConfigurationName(), $configuration);

        // Remember original http host value
        $originalHttpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;

        $this->initializeHttpServerEnvironment($item);
        $itemIndexed = $indexer->index($item);

        // update IQ item so that the IQ can determine what's been indexed already
        if ($itemIndexed) {
            $indexQueue = GeneralUtility::makeInstance(Queue::class);
            $indexQueue->updateIndexTimeByItem($item);
        }

        if (!is_null($originalHttpHost)) {
            $_SERVER['HTTP_HOST'] = $originalHttpHost;
        } else {
            unset($_SERVER['HTTP_HOST']);
        }

        // needed since TYPO3 7.5
        GeneralUtility::flushInternalRuntimeCaches();

        return $itemIndexed;
    }

    /**
     * A factory method to get an indexer depending on an item's configuration.
     *
     * By default all items are indexed using the default indexer
     * (ApacheSolrForTypo3\Solr\IndexQueue\Indexer) coming with EXT:solr. Pages by default are
     * configured to be indexed through a dedicated indexer
     * (ApacheSolrForTypo3\Solr\IndexQueue\PageIndexer). In all other cases a dedicated indexer
     * can be specified through TypoScript if needed.
     *
     * @param string $indexingConfigurationName Indexing configuration name.
     * @param TypoScriptConfiguration $configuration
     * @return Indexer
     */
    protected function getIndexerByItem($indexingConfigurationName, TypoScriptConfiguration $configuration)
    {
        $indexerClass = $configuration->getIndexQueueIndexerByConfigurationName($indexingConfigurationName);
        $indexerConfiguration = $configuration->getIndexQueueIndexerConfigurationByConfigurationName($indexingConfigurationName);

        $indexer = GeneralUtility::makeInstance($indexerClass, $indexerConfiguration);
        if (!($indexer instanceof Indexer)) {
            throw new \RuntimeException(
                'The indexer class "' . $indexerClass . '" for indexing configuration "' . $indexingConfigurationName . '" is not a valid indexer. Must be a subclass of ApacheSolrForTypo3\Solr\IndexQueue\Indexer.',
                1260463206
            );
        }

        return $indexer;
    }

    /**
     * Initializes the $_SERVER['HTTP_HOST'] environment variable in CLI
     * environments dependent on the Index Queue item's root page.
     *
     * When the Index Queue Worker task is executed by a cron job there is no
     * HTTP_HOST since we are in a CLI environment. RealURL needs the host
     * information to generate a proper URL though. Using the Index Queue item's
     * root page information we can determine the correct host although being
     * in a CLI environment.
     *
     * @param Item $item Index Queue item to use to determine the host.
     * @param
     */
    protected function initializeHttpServerEnvironment(Item $item)
    {
        static $hosts = [];
        $rootpageId = $item->getRootPageUid();
        $hostFound = !empty($hosts[$rootpageId]);

        if (!$hostFound) {
            $rootline = BackendUtility::BEgetRootLine($rootpageId);
            $host = BackendUtility::firstDomainRecord($rootline);
            $hosts[$rootpageId] = $host;
        }

        $_SERVER['HTTP_HOST'] = $hosts[$rootpageId];

        // needed since TYPO3 7.5
        GeneralUtility::flushInternalRuntimeCaches();
    }
}
