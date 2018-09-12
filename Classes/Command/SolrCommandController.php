<?php
namespace JWeiland\Jwtools2\Command;

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

use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use ApacheSolrForTypo3\Solr\Site;
use JWeiland\Jwtools2\Configuration\ExtConf;
use JWeiland\Jwtools2\Service\SolrService;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class SolrCommandController
 */
class SolrCommandController extends CommandController
{
    /**
     * Ext conf
     *
     * @var ExtConf
     */
    protected $extConf;

    /**
     * Solr service
     *
     * @var SolrService
     */
    protected $solrService;

    /**
     * injects extConf
     *
     * @param ExtConf $extConf
     * @return void
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * injects solrService
     *
     * @param SolrService $solrService
     * @return void
     */
    public function injectSolrService(SolrService $solrService)
    {
        $this->solrService = $solrService;
    }

    /**
     * Resolve command method name
     *
     * @return string
     * @throws Exception
     */
    protected function resolveCommandMethodName()
    {
        if (!$this->extConf->getSolrEnable()) {
            throw new Exception('Solr not enabled in jwtools2 extension configuration', 1536740638);
        }

        return parent::resolveCommandMethodName();
    }

    /**
     * Creates index for all sites
     *
     * TODO: Rework this to support list of sites and all if none are given
     *
     * @return void
     */
    public function createIndexQueueForAllSitesCommand()
    {
        $result = $this->solrService->createIndexQueueForSites();
        /** @var Queue $indexQueue */
        $indexQueue = $result['indexQueue'];

        $this->outputLine('Affected sites: ');

        /** @var Site $site */
        foreach ($result['sitesToIndex'] as $site) {
            $this->outputLine($site->getDomain());
        }

        $this->outputLine();
        $this->outputLine('Added ' . $indexQueue->getAllItemsCount() . ' items in total to index queue');
    }
}
