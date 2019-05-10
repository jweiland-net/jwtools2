<?php
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
     * @return void
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * Get Solr Scheduler Task of this extension
     *
     * @return IndexQueueWorkerTask
     */
    public function findSolrSchedulerTask()
    {
        $taskRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'tx_scheduler_task',
            sprintf('uid=%d AND disable=0', $this->extConf->getSolrSchedulerTaskUid())
        );
        if (empty($taskRecord)) {
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
     * Get TYPO3s Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
