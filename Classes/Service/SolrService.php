<?php
declare(strict_types = 1);
namespace JWeiland\Jwtools2\Service;

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
use ApacheSolrForTypo3\Solr\Domain\Index\Queue\Statistic\QueueStatistic;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use ApacheSolrForTypo3\Solr\Site;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is our own SolrService, so we can merge all Solr-Tasks into ONE task.
 */
class SolrService
{
    /**
     * Instead of the Solr Statistic, this Statistic will return
     * a statistic over all sites
     *
     * @return QueueStatistic
     */
    public function getStatistic()
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $indexQueueStats = $queryBuilder
            ->selectLiteral('indexed < changed as pending, (errors not like "") as failed, COUNT(*) as count')
            ->from('tx_solr_indexqueue_item')
            ->groupBy('pending')
            ->addGroupBy('failed')
            ->execute()
            ->fetchAll();
        if (!$indexQueueStats) {
            $indexQueueStats = [];
        }

        /** @var $statistic QueueStatistic */
        $statistic = GeneralUtility::makeInstance(QueueStatistic::class);

        foreach ($indexQueueStats as $row) {
            if ($row['failed'] == 1) {
                $statistic->setFailedCount((int) $row['count']);
            } elseif ($row['pending'] == 1) {
                $statistic->setPendingCount((int) $row['count']);
            } else {
                $statistic->setSuccessCount((int) $row['count']);
            }
        }

        return $statistic;
    }

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

        /** @var Queue $indexQueue */
        $indexQueue = GeneralUtility::makeInstance(Queue::class);

        foreach ($sites as $site) {
            $indexingConfigurationsToReIndex = $site->getSolrConfiguration()->getEnabledIndexQueueConfigurationNames();

            $result[$site->getRootPageId()]['site'] = $site;
            foreach ($indexingConfigurationsToReIndex as $indexingConfigurationName) {
                $status = $indexQueue->getInitializationService()->initializeBySiteAndIndexConfiguration(
                    $site,
                    $indexingConfigurationName
                );

                $result[$site->getRootPageId()]['status'][] = $status;
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
        /** @var \ApacheSolrForTypo3\Solr\System\Solr\SolrConnection[] $solrServers */
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
            $solrServer->getWriteService()->deleteByQuery($deleteQuery);

            if (!$enableCommitsSetting) {
                // Do not commit
                continue;
            }

            $response = $solrServer->getWriteService()->commit(false, false, false);
            if ($response->getHttpStatus() !== 200) {
                $cleanUpResult = false;
                break;
            }
        }

        return $cleanUpResult;
    }

    /**
     * Clear various indexes by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     *
     * @param Site $site
     * @param string $type TableName of the configuration
     * @param array $clear
     * @return void
     */
    public function clearIndexByType(Site $site, $type = '', array $clear)
    {
        // clear local tx_solr_indexqueue_item table
        if (in_array('clearItem', $clear)) {
            $this->clearItemTableByType($site, $type);
        }

        // clear local tx_solr_indexqueue_file table
        if (in_array('clearFile', $clear)) {
            $this->clearItemTableByType($site, $type);
        }

        // clear external Solr Server
        if (in_array('clearSolr', $clear)) {
            $this->clearSolrIndexByType($site, $type);
        }
    }

    /**
     * Clear item table by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     *
     * @param Site $site
     * @param string $type TableName of the configuration
     */
    public function clearItemTableByType(Site $site, string $type = '')
    {
        $identifier = [
            'root' => (int)$site->getRootPageId()
        ];
        if ($type) {
            $identifier['indexing_configuration'] = $type;
        }
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_solr_indexqueue_item');
        $connection->delete(
            'tx_solr_indexqueue_item',
            $identifier
        );
    }

    /**
     * Clear file table by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     *
     * @param Site $site
     * @param string $type TableName of the configuration
     */
    public function clearFileTableByType(Site $site, string $type = '')
    {
        if (ExtensionManagementUtility::isLoaded('solrfal')) {
            $identifier = [
                'context_site' => (int)$site->getRootPageId()
            ];
            if ($type) {
                $identifier['context_record_indexing_configuration'] = $type;
            }

            $connection = $this->getConnectionPool()->getConnectionForTable('tx_solr_indexqueue_file');
            $connection->delete(
                'tx_solr_indexqueue_file',
                $identifier
            );
        }
    }

    /**
     * Clear Solr Index by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     *
     * @param Site $site
     * @param string $type TableName of the configuration
     * @return void
     */
    public function clearSolrIndexByType(Site $site, $type = '')
    {
        $tableName = $site->getSolrConfiguration()->getIndexQueueTableNameOrFallbackToConfigurationName($type);
        /** @var \ApacheSolrForTypo3\Solr\System\Solr\SolrConnection[] $solrServers */
        $solrServers = GeneralUtility::makeInstance(ConnectionManager::class)
            ->getConnectionsBySite($site);
        foreach ($solrServers as $solrServer) {
            $solrServer->getWriteService()->deleteByType($tableName); // Document
            $solrServer->getWriteService()->deleteByQuery('fileReferenceType:' . $tableName); // tx_solr_file
        }
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
