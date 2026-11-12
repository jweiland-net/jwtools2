<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\Task;

use JWeiland\Jwtools2\Task\ExecuteQueryTask;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExecuteQueryTaskTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private FlashMessageQueue&MockObject $flashMessageQueueMock;

    protected function setUp(): void
    {
        parent::setUp();

        GeneralUtility::setSingletonInstance(Scheduler::class, $this->createMock(Scheduler::class));

        // execute() always ends up calling addMessage(), which fetches the FlashMessageService
        // at runtime via GeneralUtility::makeInstance().
        $this->flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);
        $flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $flashMessageServiceMock
            ->method('getMessageQueueByIdentifier')
            ->willReturn($this->flashMessageQueueMock);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageServiceMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();

        parent::tearDown();
    }

    private function addConnectionPoolWithConnection(Connection $connection): void
    {
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->expects(self::once())
            ->method('getConnectionByName')
            ->with('Default')
            ->willReturn($connection);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
    }

    #[Test]
    public function taskHasNoConstructorDependencies(): void
    {
        // Scheduler tasks are restored via unserialize(), which does not call the constructor.
        // Injected services would be serialized into the task record and tasks saved with older
        // versions would crash with uninitialized typed properties.
        $constructor = (new \ReflectionClass(ExecuteQueryTask::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertSame(0, $constructor->getNumberOfParameters());
    }

    #[Test]
    public function executeWithSingleQueryWillReturnTrue(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::once())
            ->method('executeStatement')
            ->with(self::equalTo('UPDATE what_ever;'))
            ->willReturn(1);
        $this->addConnectionPoolWithConnection($connectionMock);

        $subject = new ExecuteQueryTask();
        $subject->setSqlQuery('UPDATE what_ever;');

        self::assertTrue(
            $subject->execute(),
        );
    }

    #[Test]
    public function executeWithQueryWithoutSemicolonWillAddSemicolon(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::once())
            ->method('executeStatement')
            ->with(self::equalTo('UPDATE what_ever;'))
            ->willReturn(1);
        $this->addConnectionPoolWithConnection($connectionMock);

        $subject = new ExecuteQueryTask();
        $subject->setSqlQuery('UPDATE what_ever');

        self::assertTrue(
            $subject->execute(),
        );
    }

    #[Test]
    public function executeWithMultipleQueriesWillReturnTrue(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::exactly(3))
            ->method('executeStatement')
            ->willReturn(1);
        $this->addConnectionPoolWithConnection($connectionMock);

        $subject = new ExecuteQueryTask();
        $subject->setSqlQuery("UPDATE what_ever;\nUPDATE that;\nUPDATE else;");

        self::assertTrue(
            $subject->execute(),
        );
    }

    #[Test]
    public function executeWithNoQueriesWillReturnFalse(): void
    {
        $this->addConnectionPoolWithConnection($this->createMock(Connection::class));

        $subject = new ExecuteQueryTask();
        $subject->setSqlQuery('');

        self::assertFalse(
            $subject->execute(),
        );
    }

    #[Test]
    public function executeWithFailingStatementWillReturnFalseAndAddErrorMessage(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('broken query'));
        $this->addConnectionPoolWithConnection($connectionMock);

        $this->flashMessageQueueMock
            ->expects(self::once())
            ->method('enqueue')
            ->with(self::callback(
                static fn(FlashMessage $flashMessage): bool
                    => $flashMessage->getSeverity() === ContextualFeedbackSeverity::ERROR
                    && str_contains($flashMessage->getMessage(), 'broken query'),
            ));

        $subject = new ExecuteQueryTask();
        $subject->setSqlQuery('UPDATE broken;');

        self::assertFalse(
            $subject->execute(),
        );
    }
}
