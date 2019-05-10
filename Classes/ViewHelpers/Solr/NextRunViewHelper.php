<?php

namespace JWeiland\Jwtools2\ViewHelpers\Solr;

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
use ApacheSolrForTypo3\Solr\Site;
use JWeiland\Jwtools2\Configuration\ExtConf;
use JWeiland\Jwtools2\Domain\Repository\SchedulerRepository;
use JWeiland\Jwtools2\Domain\Repository\SolrRepository;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class NextRunViewHelper
 */
class NextRunViewHelper extends AbstractViewHelper
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
     * Calculate next run for given site
     *
     * @param Site $site
     *
     * @return int
     */
    public function render(Site $site)
    {
        $task = $this->schedulerRepository->findSolrSchedulerTask();
        if (!$task || empty($task->getExecution()->getInterval())) {
            return 0;
        }

        try {
            $currentSite = $this->solrRepository->findByRootPage(
                $this->registry->get('jwtools2-solr', 'rootPageId', 0)
            );
            if (!$currentSite instanceof Site) {
                return 0;
            }
        } catch (\Exception $e) {
            return 0;
        }

        $requestedKey = $this->getKeyOfAllAvailableSites($site);
        $currentKey = $this->getKeyOfAllAvailableSites($currentSite);

        if ($currentKey <= $requestedKey) {
            $diff = $requestedKey - $currentKey;
        } else {
            $amountOfSites = count($this->solrRepository->findAllAvailableSites());
            $diff = $amountOfSites - ($currentKey - $requestedKey);
        }

        // diff / indexed sites per run * 300 seconds (task interval)
        return ceil(($diff / $task->getMaxSitesPerRun()) * (int)$task->getExecution()->getInterval());
    }

    /**
     * available sites is an array with increasing numeric keys.
     * Return array key of matching site
     *
     * @param Site $site
     *
     * @return int
     */
    protected function getKeyOfAllAvailableSites(Site $site)
    {
        /** @var Site[] $sites */
        $sites = array_values($this->solrRepository->findAllAvailableSites());
        foreach ($sites as $key => $availableSite) {
            if ($availableSite->getRootPageId() === $site->getRootPageId()) {
                return (int)$key;
            }
        }
        // normally this will not be reached
        return 0;
    }
}
