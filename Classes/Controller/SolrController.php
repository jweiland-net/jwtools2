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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\NotFoundView;
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
     * @return void
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
     *
     * @return void
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
     * @param int $rootPageUid
     * @return void
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
                AbstractMessage::WARNING);
            $this->redirect('list');
        }
    }

    /**
     * @param int $rootPageUid
     * @param array $configurationNames
     * @param array $clear
     * @validate $configurationNames NotEmpty
     * @validate $clear NotEmpty
     * @return void
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
     * @return void
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
}
