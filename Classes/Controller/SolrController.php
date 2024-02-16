<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller;

use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\IndexQueue\Indexer;
use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use JWeiland\Jwtools2\Backend\SolrDocHeader;
use JWeiland\Jwtools2\Service\SolrService;
use JWeiland\Jwtools2\Traits\InjectPageRendererTrait;
use JWeiland\Jwtools2\Traits\InjectRegistryTrait;
use JWeiland\Jwtools2\Traits\InjectSchedulerRepositoryTrait;
use JWeiland\Jwtools2\Traits\InjectSolrRepositoryTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\View\NotFoundView;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

#[AsController]
class SolrController extends AbstractController
{
    use InjectSolrRepositoryTrait;
    use InjectSchedulerRepositoryTrait;
    use InjectRegistryTrait;
    use InjectPageRendererTrait;

    public function initializeView($view): void
    {
        if (!$view instanceof NotFoundView) {
            parent::initializeView($view);

            //$this->pageRenderer->loadJavaScriptModule('@jweiland/jwtools2/SolrBackendModule.js');
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Jwtools2/SolrIndex');

            /** @var SolrDocHeader $docHeader */
            $docHeader = GeneralUtility::makeInstance(SolrDocHeader::class, $this->request, $this->moduleTemplate);
            $docHeader->renderDocHeader();

            if (!$this->schedulerRepository->findSolrSchedulerTask()) {
                $this->addFlashMessage(
                    'No or wrong scheduler task UID configured in ExtensionManager Configuration of jwtools2',
                    'Missing or wrong configuration',
                    ContextualFeedbackSeverity::WARNING
                );
            }
        }
    }

    public function listAction(): ResponseInterface
    {
        $this->moduleTemplate->assign('sites', $this->solrRepository->findAllAvailableSites());
        $this->moduleTemplate->assign('currentRootPageUid', $this->registry->get('jwtools2-solr', 'rootPageId', 0));

        return $this->moduleTemplate->renderResponse('List');
    }

    public function showAction(int $rootPageUid): ResponseInterface
    {
        $site = $this->solrRepository->findByRootPage($rootPageUid);
        $this->moduleTemplate->assign('site', $site);
        $this->moduleTemplate->assign('memoryPeakUsage', $this->registry->get('jwtools2-solr', 'memoryPeakUsage', 0));

        return $this->moduleTemplate->renderResponse('Show');
    }

    public function showIndexQueueAction(int $rootPageUid, string $configurationName): ResponseInterface
    {
        $site = $this->solrRepository->findByRootPage($rootPageUid);
        if ($site instanceof Site) {
            $solrConfiguration = $site->getSolrConfiguration()->getIndexQueueConfigurationByName($configurationName);
            $this->moduleTemplate->assign('site', $site);
            $this->moduleTemplate->assign('solrConfiguration', $solrConfiguration);
            $this->moduleTemplate->assign('configurationName', $configurationName);
        }

        return $this->moduleTemplate->renderResponse('showIndexQueue');
    }

    public function indexOneRecordAction(
        int $rootPageUid,
        string $configurationName,
        ?int $recordUid,
        int $languageUid = 0
    ): ResponseInterface {
        if ($recordUid === null) {
            $this->addFlashMessage(
                'Please enter a record UID before submitting the form',
                'Record UID empty',
                ContextualFeedbackSeverity::ERROR
            );
        } else {
            $site = $this->solrRepository->findByRootPage($rootPageUid);
            if ($site instanceof Site) {
                $item = $this->getIndexQueueItem($rootPageUid, $configurationName, $recordUid);
                if ($item instanceof Item) {
                    if ($this->indexItem($item, $site->getSolrConfiguration())) {
                        $this->addFlashMessage(
                            'Solr Index Queue Item was successfully indexed to Solr Server',
                            'Item Indexed'
                        );
                    } else {
                        $this->addFlashMessage(
                            'Indexing Solr Queue Item object failed. Please check logs. Record UID: ' . $recordUid,
                            'Indexing Failed',
                            ContextualFeedbackSeverity::ERROR
                        );
                    }
                } else {
                    $this->addFlashMessage(
                        'Solr Index Queue Item could not be found by Record UID: ' . $recordUid,
                        'Indexing Failed',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            } else {
                $this->addFlashMessage(
                    'Solr Site object could not be retrieved by RootPageUid: ' . $rootPageUid,
                    'Indexing Failed',
                    ContextualFeedbackSeverity::ERROR
                );
            }
        }

        return $this->redirect(
            'showIndexQueue',
            'Solr',
            'jwtools2',
            [
                'rootPageUid' => $rootPageUid,
                'configurationName' => $configurationName,
                'languageUid' => $languageUid,
            ]
        );
    }

    public function showClearIndexFormAction(int $rootPageUid): ResponseInterface
    {
        $site = $this->solrRepository->findByRootPage($rootPageUid);
        if ($site instanceof Site) {
            $this->moduleTemplate->assign('site', $site);
            $this->moduleTemplate->assign(
                'enabledConfigurationNames',
                $site->getSolrConfiguration()->getEnabledIndexQueueConfigurationNames()
            );
        } else {
            $this->addFlashMessage(
                $rootPageUid . ' is no valid RootPage UID',
                'Invalid RootPage UID',
                ContextualFeedbackSeverity::WARNING
            );
            return $this->redirect('list');
        }

        return $this->moduleTemplate->renderResponse('showClearIndexForm');
    }

    #[Extbase\Validate(['validator' => 'NotEmpty', 'param' => 'configurationNames'])]
    #[Extbase\Validate(['validator' => 'NotEmpty', 'param' => 'clear'])]
    public function clearIndexAction(int $rootPageUid, array $configurationNames, array $clear): ResponseInterface
    {
        /** @var SolrService $solrService */
        $solrService = GeneralUtility::makeInstance(SolrService::class);
        $site = $this->solrRepository->findByRootPage($rootPageUid);
        if ($site instanceof Site) {
            foreach ($configurationNames as $configurationName) {
                $solrService->clearIndexByType($site, $clear, $configurationName);
            }
            $this->addFlashMessage(
                'We successfully have cleared the index of Site: "' . $site->getTitle() . '"',
                'Index cleared'
            );
            $this->redirect('list');
        } else {
            $this->addFlashMessage(
                'We haven\'t found a Site with RootPage UID: ' . $rootPageUid,
                'Site not found',
                ContextualFeedbackSeverity::WARNING
            );
        }

        return $this->htmlResponse();
    }

    /**
     * Show a form to clear full index
     */
    public function showClearFullIndexFormAction(): ResponseInterface
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
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

        return $this->htmlResponse();
    }

    protected function getIndexQueueItem(int $rootPageUid, string $configurationName, int $recordUid): ?Item
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $indexQueueItem = $queryBuilder
            ->select('*')
            ->from('tx_solr_indexqueue_item')
            ->where(
                $queryBuilder->expr()->eq(
                    'root',
                    $queryBuilder->createNamedParameter($rootPageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'indexing_configuration',
                    $queryBuilder->createNamedParameter($configurationName)
                ),
                $queryBuilder->expr()->eq(
                    'item_uid',
                    $queryBuilder->createNamedParameter($recordUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

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
                    $queryBuilder->createNamedParameter($recordUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($tableRecord === false) {
            return null;
        }

        return GeneralUtility::makeInstance(
            Item::class,
            $indexQueueItem,
            $tableRecord
        );
    }

    protected function indexItem(Item $item, TypoScriptConfiguration $configuration): bool
    {
        $indexer = $this->getIndexerByItem($item->getIndexingConfigurationName(), $configuration);

        $itemIndexed = $indexer->index($item);

        // update IQ item so that the IQ can determine what's been indexed already
        if ($itemIndexed) {
            $indexQueue = GeneralUtility::makeInstance(Queue::class);
            $indexQueue->updateIndexTimeByItem($item);
        }

        // needed since TYPO3 7.5
        GeneralUtility::flushInternalRuntimeCaches();

        return $itemIndexed;
    }

    /**
     * A factory method to get an indexer depending on an item's configuration.
     * By default, all items are indexed using the default indexer
     * (ApacheSolrForTypo3\Solr\IndexQueue\Indexer) coming with EXT:solr. Pages by default are
     * configured to be indexed through a dedicated indexer
     * (ApacheSolrForTypo3\Solr\IndexQueue\PageIndexer). In all other cases a dedicated indexer
     * can be specified through TypoScript if needed.
     */
    protected function getIndexerByItem(string $indexingConfigurationName, TypoScriptConfiguration $configuration): Indexer
    {
        $indexerClass = $configuration->getIndexQueueIndexerByConfigurationName($indexingConfigurationName);
        $indexerConfiguration = $configuration->getIndexQueueIndexerConfigurationByConfigurationName(
            $indexingConfigurationName
        );

        $indexer = GeneralUtility::makeInstance($indexerClass, $indexerConfiguration);
        if (!($indexer instanceof Indexer)) {
            throw new \RuntimeException(
                'The indexer class "' . $indexerClass . '" for indexing configuration "' . $indexingConfigurationName . '" is not a valid indexer. Must be a subclass of ApacheSolrForTypo3\Solr\IndexQueue\Indexer.',
                1260463206
            );
        }

        return $indexer;
    }
}
