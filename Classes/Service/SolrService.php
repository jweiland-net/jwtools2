<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Service;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Index\Queue\Statistic\QueueStatistic;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use Doctrine\DBAL\DBALException;
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
     */
    public function getStatistic(): QueueStatistic
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $statement = $queryBuilder
            ->selectLiteral('indexed < changed as pending, (errors not like "") as failed, COUNT(*) as count')
            ->from('tx_solr_indexqueue_item')
            ->groupBy('pending')
            ->addGroupBy('failed')
            ->executeQuery();

        $indexQueueStats = [];
        while ($indexQueueStat = $statement->fetchAssociative()) {
            $indexQueueStats[] = $indexQueueStat;
        }

        $statistic = GeneralUtility::makeInstance(QueueStatistic::class);

        foreach ($indexQueueStats as $row) {
            if ((int)($row['failed']) === 1) {
                $statistic->setFailedCount((int)$row['count']);
            } elseif ((int)($row['pending']) === 1) {
                $statistic->setPendingCount((int)$row['count']);
            } else {
                $statistic->setSuccessCount((int)$row['count']);
            }
        }

        return $statistic;
    }

    /**
     * Clear various indexes by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     */
    public function clearIndexByType(Site $site, array $clear, string $type = ''): void
    {
        // clear local tx_solr_indexqueue_item table
        if (in_array('clearItem', $clear, true)) {
            $this->clearItemTableByType($site, $type);
        }

        // clear local tx_solr_indexqueue_file table
        if (in_array('clearFile', $clear, true)) {
            $this->clearFileTableByType($site, $type);
        }

        // clear external Solr Server
        if (in_array('clearSolr', $clear, true)) {
            $this->clearSolrIndexByType($site, $type);
        }
    }

    /**
     * Clear item table by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     */
    public function clearItemTableByType(Site $site, string $type = ''): void
    {
        $identifier = [
            'root' => $site->getRootPageId(),
        ];

        if ($type !== '') {
            $identifier['indexing_configuration'] = $type;
        }

        $this
            ->getConnectionPool()
            ->getConnectionForTable('tx_solr_indexqueue_item')
            ->delete(
                'tx_solr_indexqueue_item',
                $identifier
            );
    }

    /**
     * Clear file table by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     */
    public function clearFileTableByType(Site $site, string $type = ''): void
    {
        if (ExtensionManagementUtility::isLoaded('solrfal')) {
            $identifier = [
                'context_site' => $site->getRootPageId(),
            ];

            if ($type !== '') {
                $identifier['context_record_indexing_configuration'] = $type;
            }

            $this
                ->getConnectionPool()
                ->getConnectionForTable('tx_solr_indexqueue_file')
                ->delete(
                    'tx_solr_indexqueue_file',
                    $identifier
                );
        }
    }

    /**
     * Clear Solr Index by type
     * Be careful: If type is empty, it will delete EVERYTHING from given $site
     */
    public function clearSolrIndexByType(Site $site, $type = ''): void
    {
        $tableName = $site->getSolrConfiguration()->getIndexQueueTableNameOrFallbackToConfigurationName($type);

        $solrServers = GeneralUtility::makeInstance(ConnectionManager::class)
            ->getConnectionsBySite($site);

        foreach ($solrServers as $solrServer) {
            $solrServer->getWriteService()->deleteByType($tableName); // Document
            $solrServer->getWriteService()->deleteByQuery('fileReferenceType:' . $tableName); // tx_solr_file
        }
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
