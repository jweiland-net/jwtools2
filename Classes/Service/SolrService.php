<?php
namespace JWeiland\Jwtools2\Service;

/*
 * This file is part of the TYPO3 CMS project.
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
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use ApacheSolrForTypo3\Solr\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SolrService
 *
 * @package JWeiland\Jwtools2\Service
 */
class SolrService
{
    /**
     * Creates index queue entries for all given sites
     * If no site is given, all available sites are used
     *
     * @param Site[] $sites
     * @return array
     */
    public function createIndexQueueForSites($sites = [])
    {
        if (empty($sites)) {
            $sites = $this->getAvailableSites();
        }

        $result = [];
        $result['errors'] = [];
        $result['totalItemsAddedToIndexQueue'] = 0;

        foreach ($sites as $site)
        {
            $indexingConfigurationsToReIndex = $site->getSolrConfiguration()->getEnabledIndexQueueConfigurationNames();

            $this->cleanUpIndex($site, $indexingConfigurationsToReIndex);

            $indexQueue = GeneralUtility::makeInstance(Queue::class);

            foreach ($indexingConfigurationsToReIndex as $indexingConfigurationName) {
                $result['errors'][$site->getRootPageId()] = $indexQueue->initialize($site, $indexingConfigurationName);
                $result['totalItemsAddedToIndexQueue'] += $indexQueue->getAllItemsCount();
            }
        }

        return $result;
    }

    /**
     * Gets all available TYPO3 sites with Solr configured.
     *
     * @param bool $stopOnInvalidSite
     * @return Site[] An array of available sites
     */
    protected function getAvailableSites($stopOnInvalidSite = false)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        return $siteRepository->getAvailableSites($stopOnInvalidSite);
    }

    /**
     * !!! Copied and adjusted from ApacheSolrForTypo3\Solr\Task\ReIndexTask
     * Removes documents of the selected types from the index.
     *
     * @param Site $site
     * @param string[] $indexingConfigurationsToReIndex
     * @return bool TRUE if clean up was successful, FALSE on error
     */
    protected function cleanUpIndex($site, $indexingConfigurationsToReIndex)
    {
        $cleanUpResult = true;
        $solrConfiguration = $site->getSolrConfiguration();
        $solrServers = GeneralUtility::makeInstance(ConnectionManager::class)->getConnectionsBySite($site);
        $typesToCleanUp = [];
        $enableCommitsSetting = $solrConfiguration->getEnableCommits();

        foreach ($indexingConfigurationsToReIndex as $indexingConfigurationName) {
            $type = $solrConfiguration->getIndexQueueTableNameOrFallbackToConfigurationName($indexingConfigurationName);
            $typesToCleanUp[] = $type;
        }

        foreach ($solrServers as $solrServer) {
            $deleteQuery = 'type:(' . implode(' OR ', $typesToCleanUp) . ')'
                . ' AND siteHash:' . $site->getSiteHash();
            $solrServer->deleteByQuery($deleteQuery);

            if (!$enableCommitsSetting) {
                # Do not commit
                continue;
            }

            $response = $solrServer->commit(false, false, false);
            if ($response->getHttpStatus() != 200) {
                $cleanUpResult = false;
                break;
            }
        }

        return $cleanUpResult;
    }
}
