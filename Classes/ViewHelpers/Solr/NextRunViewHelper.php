<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\ViewHelpers\Solr;

use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use JWeiland\Jwtools2\Domain\Repository\SchedulerRepository;
use JWeiland\Jwtools2\Domain\Repository\SolrRepository;
use TYPO3\CMS\Core\Registry;
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
     * Initialize all arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'site',
            Site::class,
            'Solr Site object to get the next run from',
            true
        );
    }

    /**
     * Calculate next run for given site
     *
     * @return float
     */
    public function render(): float
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

        $requestedKey = $this->getKeyOfAllAvailableSites($this->arguments['site']);
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
     * @return int
     */
    protected function getKeyOfAllAvailableSites(Site $site): int
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
