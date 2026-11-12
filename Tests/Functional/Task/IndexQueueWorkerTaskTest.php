<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Functional\Task;

use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use JWeiland\Jwtools2\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexQueueWorkerTaskTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_solr_indexqueue_item.csv');
    }

    /**
     * Same way the scheduler module creates a new task
     */
    private function createTask(): IndexQueueWorkerTask
    {
        return GeneralUtility::makeInstance(IndexQueueWorkerTask::class);
    }

    #[Test]
    public function getProgressReturnsSuccessPercentageOfIndexQueue(): void
    {
        // This is called by the scheduler module for each listed task
        self::assertSame(
            60.0,
            $this->createTask()->getProgress(),
        );
    }

    #[Test]
    public function getProgressWorksOnRestoredTask(): void
    {
        self::assertSame(
            60.0,
            $this->saveAndRestoreTask($this->createTask())->getProgress(),
        );
    }

    #[Test]
    public function serializedTaskDoesNotContainServices(): void
    {
        $serializedTask = $this->getSerializedTaskRecord($this->createTask());

        self::assertStringNotContainsString(Registry::class, $serializedTask);
        self::assertStringNotContainsString('ConnectionPool', $serializedTask);
    }

    #[Test]
    public function restoredTaskKeepsConfiguration(): void
    {
        $task = $this->createTask();
        $task->setDocumentsToIndexLimit(25);
        $task->setMaxSitesPerRun(4);

        $restoredTask = $this->saveAndRestoreTask($task);

        self::assertSame(25, $restoredTask->getDocumentsToIndexLimit());
        self::assertSame(4, $restoredTask->getMaxSitesPerRun());
    }

    #[Test]
    public function getAdditionalInformationWithoutIndexedSiteReturnsHint(): void
    {
        self::assertSame(
            'Please execute this task first to retrieve site information',
            $this->saveAndRestoreTask($this->createTask())->getAdditionalInformation(),
        );
    }

    #[Test]
    public function executeWithoutSolrSitesResetsSitePosition(): void
    {
        $registry = $this->get(Registry::class);
        $registry->set('jwtools2-solr', 'lastSitePosition', 20);

        self::assertTrue(
            $this->saveAndRestoreTask($this->createTask())->execute(),
        );
        self::assertSame(0, $registry->get('jwtools2-solr', 'lastSitePosition'));
        self::assertSame(0, $registry->get('jwtools2-solr', 'memoryPeakUsage'));
    }
}
