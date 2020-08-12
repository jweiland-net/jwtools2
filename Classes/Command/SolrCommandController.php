<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Command;

use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use JWeiland\Jwtools2\Service\SolrService;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SolrCommandController
 */
class SolrCommandController extends Command
{
    /**
     * @var SolrService
     */
    protected $solrService;

    /**
     * @param SolrService $solrService
     */
    public function injectSolrService(SolrService $solrService): void
    {
        $this->solrService = $solrService;
    }

    /**
     * Resolve command method name
     *
     * @return string
     * @throws Exception
     */
    protected function resolveCommandMethodName(): string
    {
        if (GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('jwtools2', 'solrEnable')) {
            throw new Exception('Solr not enabled in jwtools2 extension configuration', 1536740638);
        }

        return parent::resolveCommandMethodName();
    }

    /**
     * Creates index for all sites
     */
    public function createIndexQueueForAllSitesCommand(): void
    {
        $result = $this->solrService->createIndexQueueForSites();

        foreach ($result as $siteResult) {
            /** @var Site $site */
            $site = $siteResult['site'];
            $statusByQueue = $siteResult['status'];

            $this->outputLine($site->getDomain());

            foreach ($statusByQueue as $status) {
                foreach ($status as $queue => $success) {
                    if ($success) {
                        $this->outputFormatted($queue . ': ' . 'success');
                    } else {
                        $this->outputFormatted($queue . ': ' . 'failed');
                    }
                }
            }

            $this->outputLine('');
        }
    }
}
