<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Task;

use ApacheSolrForTypo3\Solr\Domain\Index\IndexService;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\System\Environment\CliEnvironment;
use Doctrine\DBAL\Driver\Exception;
use JWeiland\Jwtools2\Service\SolrService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A worker indexing the items in the index queue.
 */
class IndexQueueWorkerTask extends AbstractTask implements ProgressProviderInterface
{
    protected int $documentsToIndexLimit = 50;

    protected int $maxSitesPerRun = 10;

    /**
     * Works through the indexing queue and indexes the queued items into Solr.
     */
    public function execute(): bool
    {
        $registry = $this->getRegistry();
        $registry->set('jwtools2-solr', 'memoryPeakUsage', 0);
        $lastSitePosition = (int)$registry->get('jwtools2-solr', 'lastSitePosition');
        $maxSitePosition = $lastSitePosition + $this->getMaxSitesPerRun();
        $cliEnvironment = null;

        // Wrapped the CliEnvironment to avoid defining TYPO3_PATH_WEB since this
        // should only be done in the case when running it from outside TYPO3 BE
        // @see #921 and #934 on https://github.com/TYPO3-Solr
        if (Environment::isCli()) {
            $cliEnvironment = GeneralUtility::makeInstance(CliEnvironment::class);
            $cliEnvironment->backup();
            $cliEnvironment->initialize(Environment::getPublicPath() . '/');
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
                $indexService = GeneralUtility::makeInstance(IndexService::class, $availableSite);
                $indexService->indexItems($this->documentsToIndexLimit);
                $counter++;
            } catch (\Exception $e) {
                // jump to next site
                continue;
            }
        }

        $registry->set(
            'jwtools2-solr',
            'lastSitePosition',
            $maxSitePosition > count($availableSites) ? 0 : $maxSitePosition
        );

        if (Environment::isCli()) {
            $cliEnvironment->restore();
        }

        return true;
    }

    /**
     * Returns some additional information about indexing progress, shown in
     * the scheduler's task overview list.
     */
    public function getAdditionalInformation(): string
    {
        $registry = $this->getRegistry();
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
     */
    public function getProgress(): float
    {
        return GeneralUtility::makeInstance(SolrService::class)
            ->getStatistic()
            ->getSuccessPercentage();
    }

    /**
     * Gets all available TYPO3 sites with Solr configured.
     *
     * @return Site[] An array of available sites
     * @throws Exception
     * @throws \Throwable
     */
    public function getAvailableSites(bool $stopOnInvalidSite = false): array
    {
        return GeneralUtility::makeInstance(SiteRepository::class)
            ->getAvailableSites($stopOnInvalidSite);
    }

    /**
     * Returns the initialized IndexService instance.
     */
    protected function getInitializedIndexServiceForSite(Site $site): IndexService
    {
        return GeneralUtility::makeInstance(IndexService::class, $site);
    }

    public function getDocumentsToIndexLimit(): int
    {
        return $this->documentsToIndexLimit;
    }

    public function setDocumentsToIndexLimit(int $limit): void
    {
        $this->documentsToIndexLimit = $limit;
    }

    public function getMaxSitesPerRun(): int
    {
        return $this->maxSitesPerRun;
    }

    public function setMaxSitesPerRun(int $maxSitesPerRun): void
    {
        $this->maxSitesPerRun = $maxSitesPerRun;
    }

    protected function getRegistry(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
    }
}
