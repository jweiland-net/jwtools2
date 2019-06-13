<?php
declare(strict_types = 1);
namespace JWeiland\Jwtools2\Domain\Repository;

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

use JWeiland\Jwtools2\Configuration\ExtConf;
use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SchedulerRepository
 */
class SchedulerRepository
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * inject extConf
     *
     * @param ExtConf $extConf
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * Get Solr Scheduler Task of this extension
     *
     * @return IndexQueueWorkerTask|null
     */
    public function findSolrSchedulerTask()
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_scheduler_task');
        $taskRecord = $queryBuilder
            ->select('*')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($this->extConf->getSolrSchedulerTaskUid(), \PDO::PARAM_INT)
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
        $task = unserialize($taskRecord['serialized_task_object']);
        if (!$task instanceof IndexQueueWorkerTask) {
            return null;
        }

        return $task;
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
