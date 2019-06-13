<?php
namespace JWeiland\Jwtools2\Task;

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

use ApacheSolrForTypo3\Solr\Domain\Index\IndexService;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\Site;
use ApacheSolrForTypo3\Solr\System\Environment\CliEnvironment;
use JWeiland\Jwtools2\Service\SolrService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A worker indexing the items in the index queue.
 */
class IndexQueueWorkerTask extends AbstractTask implements ProgressProviderInterface
{
    /**
     * @var int
     */
    protected $documentsToIndexLimit = 50;

    /**
     * @var int
     */
    protected $maxSitesPerRun = 10;

    /**
     * Works through the indexing queue and indexes the queued items into Solr.
     *
     * @return bool Returns TRUE on success, FALSE if no items were indexed or none were found.
     */
    public function execute()
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set('jwtools2-solr', 'memoryPeakUsage', 0);
        $lastSitePosition = (int)$registry->get('jwtools2-solr', 'lastSitePosition');
        $maxSitePosition = $lastSitePosition + $this->getMaxSitesPerRun();
        $cliEnvironment = null;

        // Wrapped the CliEnvironment to avoid defining TYPO3_PATH_WEB since this
        // should only be done in the case when running it from outside TYPO3 BE
        // @see #921 and #934 on https://github.com/TYPO3-Solr
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
            $cliEnvironment = GeneralUtility::makeInstance(CliEnvironment::class);
            $cliEnvironment->backup();
            $cliEnvironment->initialize(PATH_site);
        }

        $counter = 0;
        $availableSites = $this->getAvailableSites();
        foreach ($availableSites as $availableSite) {
            if ($counter < $lastSitePosition || $counter > $maxSitePosition) {
                $counter++;
                continue;
            }
            $registry->set('jwtools2-solr', 'rootPageId', $availableSite->getRootPageId());

            try {
                /** @var IndexService $indexService */
                $indexService = GeneralUtility::makeInstance(IndexService::class, $availableSite);
                $indexService->setContextTask(null); // we don't set any referenced task. They are only used for emitting signals
                $indexService->indexItems($this->documentsToIndexLimit);
                $counter++;
            } catch (\Exception $e) {
                // jump to next site
                continue;
            }
        }
        $registry->set('jwtools2-solr', 'lastSitePosition', $maxSitePosition > count($availableSites) ? 0 : $maxSitePosition);

        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
            $cliEnvironment->restore();
        }

        $executionSucceeded = true;

        return $executionSucceeded;
    }

    /**
     * Returns some additional information about indexing progress, shown in
     * the scheduler's task overview list.
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $rootPageId = (int)$registry->get('jwtools2-solr', 'rootPageId');
        $message = 'Please execute this task first to retrieve site information';
        if ($rootPageId === 0) {
            return $message;
        }

        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        $site = $siteRepository->getSiteByRootPageId($rootPageId);

        if ($site instanceof Site) {
            $message = 'Site: ' . $site->getLabel();

            /** @var $indexService IndexService */
            $indexService = $this->getInitializedIndexServiceForSite($site);
            $failedItemsCount = $indexService->getFailCount();

            if ($failedItemsCount) {
                $message .= ' Failures: ' . $failedItemsCount;
            }

            $message .= ' / Index queue UID: ' . $registry->get('jwtools2-solr', 'indexQueueUid');
            $message .= ' / Memory Peak: ' . (float)$registry->get('jwtools2-solr', 'memoryPeakUsage');
        }
        return $message;
    }

    /**
     * Gets the indexing progress.
     *
     * @return float Indexing progress as a two decimal precision float. f.e. 44.87
     */
    public function getProgress()
    {
        /** @var SolrService $solrService */
        $solrService = GeneralUtility::makeInstance(SolrService::class);
        return $solrService->getStatistic()->getSuccessPercentage();
    }

    /**
     * Gets all available TYPO3 sites with Solr configured.
     *
     * @param bool $stopOnInvalidSite
     * @return Site[] An array of available sites
     */
    public function getAvailableSites($stopOnInvalidSite = false)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        return $siteRepository->getAvailableSites($stopOnInvalidSite);
    }

    /**
     * Returns the initialize IndexService instance.
     *
     * @param Site $site
     * @return IndexService
     */
    protected function getInitializedIndexServiceForSite(Site $site)
    {
        /** @var IndexService $indexService */
        $indexService = GeneralUtility::makeInstance(IndexService::class, $site);
        $indexService->setContextTask(null); // we don't set any referenced task. They are only used for emitting signals

        return $indexService;
    }

    /**
     * @return mixed
     */
    public function getDocumentsToIndexLimit()
    {
        return $this->documentsToIndexLimit;
    }

    /**
     * @param int $limit
     * @return void
     */
    public function setDocumentsToIndexLimit($limit)
    {
        $this->documentsToIndexLimit = $limit;
    }

    /**
     * @return int $maxSitesPerRun
     */
    public function getMaxSitesPerRun()
    {
        return $this->maxSitesPerRun;
    }

    /**
     * @param int $maxSitesPerRun
     * @return void
     */
    public function setMaxSitesPerRun($maxSitesPerRun)
    {
        $this->maxSitesPerRun = (int)$maxSitesPerRun;
    }
}
