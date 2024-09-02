<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Domain\Repository;

use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;

/**
 * Repository to find records from tx_scheduler_task table.
 * In this special case it just fetches specific solr indexing tasks.
 */
class SchedulerRepository
{
    /**
     * Get Solr Scheduler Task of this extension
     */
    public function findSolrSchedulerTask(): ?IndexQueueWorkerTask
    {
        try {
            $task = $this->getScheduler()->fetchTask(
                (int)$this->getExtensionConfiguration('solrSchedulerTaskUid'),
            );

            if (!$task instanceof IndexQueueWorkerTask) {
                return null;
            }
        } catch (\OutOfBoundsException $outOfBoundsException) {
            return null;
        }

        return $task;
    }

    protected function getExtensionConfiguration(string $path): string
    {
        try {
            return (string)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('jwtools2', $path);
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $exception) {
            return '';
        }
    }

    protected function getScheduler(): Scheduler
    {
        return GeneralUtility::makeInstance(Scheduler::class);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
