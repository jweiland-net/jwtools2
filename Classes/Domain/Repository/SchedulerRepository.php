<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Domain\Repository;

use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SchedulerRepository
 */
class SchedulerRepository
{
    /**
     * Get Solr Scheduler Task of this extension
     *
     * @return IndexQueueWorkerTask|null
     */
    public function findSolrSchedulerTask(): ?IndexQueueWorkerTask
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_scheduler_task');
        $taskRecord = $queryBuilder
            ->select('*')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $this->getExtensionConfiguration('solrSchedulerTaskUid'),
                        \PDO::PARAM_INT
                    )
                ),
                $queryBuilder->expr()->eq(
                    'disable',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        if (!$taskRecord) {
            return null;
        }

        /** @var IndexQueueWorkerTask $task */
        $task = unserialize($taskRecord['serialized_task_object'], ['allowed_classes' => [IndexQueueWorkerTask::class]]
        );
        if (!$task instanceof IndexQueueWorkerTask) {
            return null;
        }

        return $task;
    }

    protected function getExtensionConfiguration(string $path)
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('jwtools2', $path);
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
