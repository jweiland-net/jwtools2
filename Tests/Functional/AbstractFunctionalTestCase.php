<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Functional;

use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Boots a TYPO3 instance with jwtools2 and all its (optional) dependencies, so services are
 * built by the real, compiled DI container - exactly as it happens in production.
 */
abstract class AbstractFunctionalTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-filelist',
        'typo3/cms-filemetadata',
        'typo3/cms-install',
        'typo3/cms-reports',
        'typo3/cms-scheduler',
        'typo3/cms-tstemplate',
    ];

    protected array $testExtensionsToLoad = [
        'apache-solr-for-typo3/solr',
        'jweiland/jwtools2',
    ];

    /**
     * Stores the task in tx_scheduler_task and loads it again - exactly like the scheduler does
     * between saving a task in the backend module and executing it via cron.
     * The task gets restored via unserialize(), so its constructor is NOT called.
     *
     * @template T of AbstractTask
     * @param T $task
     * @return T
     */
    protected function saveAndRestoreTask(AbstractTask $task): AbstractTask
    {
        $schedulerTaskRepository = $this->get(SchedulerTaskRepository::class);
        self::assertTrue($schedulerTaskRepository->add($task));

        $restoredTask = $schedulerTaskRepository->findByUid($task->getTaskUid());
        self::assertInstanceOf($task::class, $restoredTask);

        return $restoredTask;
    }

    protected function getSerializedTaskRecord(AbstractTask $task): string
    {
        $schedulerTaskRepository = $this->get(SchedulerTaskRepository::class);
        self::assertTrue($schedulerTaskRepository->add($task));

        return (string)$schedulerTaskRepository->findRecordByUid($task->getTaskUid())['serialized_task_object'];
    }
}
