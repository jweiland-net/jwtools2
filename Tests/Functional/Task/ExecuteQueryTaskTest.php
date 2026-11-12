<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Functional\Task;

use JWeiland\Jwtools2\Task\ExecuteQueryTask;
use JWeiland\Jwtools2\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExecuteQueryTaskTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_solr_indexqueue_item.csv');
    }

    /**
     * Creates the task like the scheduler module does and restores it like the scheduler does
     * before execution (from tx_scheduler_task.serialized_task_object).
     */
    private function createRestoredTask(string $sqlQuery): ExecuteQueryTask
    {
        $task = GeneralUtility::makeInstance(ExecuteQueryTask::class);
        $task->setSqlQuery($sqlQuery);

        return $this->saveAndRestoreTask($task);
    }

    private function countIndexQueueItems(): int
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_solr_indexqueue_item')
            ->count('*', 'tx_solr_indexqueue_item', []);
    }

    #[Test]
    public function serializedTaskDoesNotContainServices(): void
    {
        $serializedTask = $this->getSerializedTaskRecord(
            GeneralUtility::makeInstance(ExecuteQueryTask::class),
        );

        self::assertStringNotContainsString('ConnectionPool', $serializedTask);
        self::assertStringNotContainsString(FlashMessageService::class, $serializedTask);
    }

    #[Test]
    public function executeOnRestoredTaskExecutesQueries(): void
    {
        $subject = $this->createRestoredTask(
            "DELETE FROM tx_solr_indexqueue_item WHERE uid = 1;\nDELETE FROM tx_solr_indexqueue_item WHERE uid = 2;",
        );

        self::assertTrue($subject->execute());
        self::assertSame(3, $this->countIndexQueueItems());
    }

    #[Test]
    public function executeOnRestoredTaskAddsFlashMessageToGlobalQueue(): void
    {
        $subject = $this->createRestoredTask('DELETE FROM tx_solr_indexqueue_item WHERE uid = 1');

        $subject->execute();

        $flashMessages = $this->get(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->getAllMessages();

        self::assertCount(1, $flashMessages);
        self::assertSame(ContextualFeedbackSeverity::OK, $flashMessages[0]->getSeverity());
    }

    #[Test]
    public function executeWithInvalidQueryReturnsFalse(): void
    {
        $subject = $this->createRestoredTask('DELETE FROM jwtools2_table_does_not_exist');

        self::assertFalse($subject->execute());
        self::assertSame(5, $this->countIndexQueueItems());
    }
}
