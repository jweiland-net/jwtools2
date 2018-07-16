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

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Site;
use ApacheSolrForTypo3\Solr\System\Configuration\ConfigurationPageResolver;
use ApacheSolrForTypo3\Solr\System\Configuration\ExtensionConfiguration;
use ApacheSolrForTypo3\Solr\Util;
use JWeiland\Jwtools2\Backend\SolrDocHeader;
use JWeiland\Jwtools2\Domain\Repository\SchedulerRepository;
use JWeiland\Jwtools2\Domain\Repository\SolrRepository;
use JWeiland\Jwtools2\Service\SolrService;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class SolrController
 *
 * @package JWeiland\Jwtools2\Controller
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
     *
     * @return void
     */
    public function injectSolrRepository(SolrRepository $solrRepository)
    {
        $this->solrRepository = $solrRepository;
    }

    /**
     * inject schedulerRepository
     *
     * @param SchedulerRepository $schedulerRepository
     *
     * @return void
     */
    public function injectSchedulerRepository(SchedulerRepository $schedulerRepository)
    {
        $this->schedulerRepository = $schedulerRepository;
    }

    /**
     * inject registry
     *
     * @param Registry $registry
     *
     * @return void
     */
    public function injectRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Pre-Execute some scripts
     *
     * @param ViewInterface $view
     *
     * @return void
     */
    public function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);

        /** @var SolrDocHeader $docHeader */
        $docHeader = $this->objectManager->get(SolrDocHeader::class, $this->request, $view);
        $docHeader->renderDocHeader();

        if (!$this->schedulerRepository->findSolrSchedulerTask()) {
            $this->addFlashMessage('No or wrong scheduler task UID configured in ExtensionManager Configuration of jwtools2', 'Missing or wrong configuration', FlashMessage::WARNING);
        }
    }

    /**
     * List action
     *
     * @param string $rootPageUid
     *
     * @return void
     */
    public function listAction($rootPageUid = null)
    {
        try {
            if (MathUtility::canBeInterpretedAsInteger($rootPageUid) && $rootPageUid > 0) {
                $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
                $this->view->assign('sites', [$site]);
            } elseif ($rootPageUid === '') {
                $this->view->assign('sites', $this->solrRepository->findAllAvailableSites());
            }
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), 'Error', FlashMessage::WARNING);
        }
        $this->view->assign('currentRootPageUid', $this->registry->get('jwtools2-solr', 'rootPageId', 0));
    }

    /**
     * Show action
     *
     * @param int $rootPageUid
     * @param int $languageUid
     *
     * @return void
     */
    public function showAction($rootPageUid, $languageUid = 0)
    {
        $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
        $this->view->assign('site', $site);
        $this->view->assign('memoryPeakUsage', $this->registry->get('jwtools2-solr', 'memoryPeakUsage', 0));
    }

    /**
     * Creates index queue for all sites
     *
     * @return void
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
     * CleanUp Solr Index
     * locally and external
     *
     * @param string $rootPageUid
     * @param string $type TableName of the configuration
     * @param bool $cleanUpItem
     * @param bool $cleanUpFile
     * @param bool $cleanUpSolr
     * @param bool $confirmed
     * @return void
     */
    public function cleanUpSolrIndexAction($rootPageUid = '', $type = '', $cleanUpItem = false, $cleanUpFile = false, $cleanUpSolr = false, $confirmed = false)
    {
        $sites = $this->solrRepository->findAllAvailableSites(true);
        $showConfirmation = false;

        if ($rootPageUid) {
            if ($confirmed) {
                /** @var SolrService $solrService */
                $solrService = GeneralUtility::makeInstance(SolrService::class);
                if (MathUtility::canBeInterpretedAsInteger($rootPageUid)) {
                    $site = $this->solrRepository->findByRootPage((int)$rootPageUid);
                    if ($site instanceof Site) {
                        $solrService->deleteByType($site, $type, $cleanUpItem, $cleanUpFile, $cleanUpSolr);
                        $this->addFlashMessage(
                            'Given type was successfully removed from "' . $site->getTitle() . '"',
                            'Delete successful',
                            FlashMessage::INFO
                        );
                    }
                } elseif ($rootPageUid === 'all') {
                    foreach ($sites as $site) {
                        $solrService->deleteByType($site, $type, $cleanUpItem, $cleanUpFile, $cleanUpSolr);
                    }
                    $this->addFlashMessage(
                        'Given type was successfully removed from ' . count($sites) . ' Sites',
                        'Delete successful',
                        FlashMessage::INFO
                    );
                }
            } else {
                $showConfirmation = true;
            }
        }

        $this->view->assign('sites', $sites);
        $this->view->assign('rootPageUid', $rootPageUid);
        $this->view->assign('type', $type);
        $this->view->assign('showConfirmation', $showConfirmation);
    }
}
