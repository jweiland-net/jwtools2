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
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExecuteQueryTaskTest extends UnitTestCase
{
    /**
     * @var FlashMessageService|MockObject
     */
    protected $flashMessageServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS'] = [
            'SYS' => [
                'trustedHostsPattern' => '.*',
                'devIPmask' => '*',
            ],
            'DB' => [
                'Connections' => [
                    'Default' => [
                        'driver' => 'pdo_sqlite',
                        'url' => 'sqlite::memory:',
                    ],
                ],
            ],
        ];

        $this->resetSingletonInstances = true;

        // Mock the Scheduler class
        GeneralUtility::setSingletonInstance(Scheduler::class, $this->createMock(Scheduler::class));

        // execute() always ends up calling addMessage(), which pulls a FlashMessageQueue
        // from the injected FlashMessageService and enqueues into it - stub that chain
        // once here so every test path (success, no queries, exception) works without
        // repeating this in each test.
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->flashMessageServiceMock
            ->method('getMessageQueueByIdentifier')
            ->willReturn($this->createMock(FlashMessageQueue::class));
    }

    protected function tearDown(): void
    {
        unset($this->flashMessageServiceMock);

        parent::tearDown();
    }

    /**
     * ExecuteQueryTask now receives its FlashMessageService and ConnectionPool via
     * constructor injection (it is instantiated through the DI container / GeneralUtility::
     * makeInstance() at runtime, see the "public: true" entry in Services.yaml). Since the
     * ConnectionPool is a real constructor dependency now - not fetched lazily via
     * GeneralUtility::makeInstance() inside execute() anymore - tests build the subject with
     * a per-test-configured ConnectionPool mock directly, instead of the previous
     * GeneralUtility::addInstance() approach, which no longer has any effect on this class.
     */
    protected function createSubject(ConnectionPool $connectionPool): ExecuteQueryTask
    {
        return new ExecuteQueryTask(
            $this->flashMessageServiceMock,
            $connectionPool,
        );
    }

    #[Test]
    public function executeWithSingleQueryWillReturnTrue(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::once())
            ->method('executeStatement')
            ->with(self::equalTo('UPDATE what_ever;'))
            ->willReturn(1);

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->expects(self::once())
            ->method('getConnectionByName')
            ->with('Default')
            ->willReturn($connectionMock);

        $subject = $this->createSubject($connectionPoolMock);
        $subject->setSqlQuery('UPDATE what_ever;');

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
            ->willReturnOnConsecutiveCalls(
                1, // Return value for the first call (success)
                1, // Return value for the second call (success)
                1, // Return value for the third call (success)
            );

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->expects(self::once())
            ->method('getConnectionByName')
            ->with('Default')
            ->willReturn($connectionMock);

        $subject = $this->createSubject($connectionPoolMock);
        $subject->setSqlQuery("UPDATE what_ever;\nUPDATE that;\nUPDATE else;");

        self::assertTrue(
            $subject->execute(),
        );
    }

    #[Test]
    public function executeWithNoQueriesWillReturnFalse(): void
    {
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->expects(self::once())
            ->method('getConnectionByName')
            ->with('Default')
            ->willReturn($this->createMock(Connection::class));

        $subject = $this->createSubject($connectionPoolMock);
        $subject->setSqlQuery('');

        self::assertFalse(
            $subject->execute(),
        );
    }

    #[Test]
    public function executeWithFailingStatementWillReturnFalse(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('broken query'));

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->expects(self::once())
            ->method('getConnectionByName')
            ->with('Default')
            ->willReturn($connectionMock);

        $subject = $this->createSubject($connectionPoolMock);
        $subject->setSqlQuery('UPDATE broken;');

        self::assertFalse(
            $subject->execute(),
        );
    }
}
