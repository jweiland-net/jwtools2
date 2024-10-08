<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\Task;

use Doctrine\DBAL\Driver\Statement;
use JWeiland\Jwtools2\Task\ExecuteQueryTask;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExecuteQueryTaskTest extends UnitTestCase
{
    protected ExecuteQueryTask $subject;

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

        $this->subject = new ExecuteQueryTask();
    }

    /**
     * @test
     */
    public function executeWithEmptyQueryWillReturnFalse(): void
    {
        self::assertFalse(
            $this->subject->execute(),
        );
    }

    /**
     * @test
     */
    public function executeWithSingleQueryWillReturnTrue(): void
    {
        $this->subject->setSqlQuery('UPDATE what_ever;');

        $statementMock = $this->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::once())
            ->method('executeStatement')
            ->with(
                self::equalTo('UPDATE what_ever;'),
                self::equalTo([]),
            )
            ->willReturn($statementMock);

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->expects(self::once())
            ->method('getConnectionByName')
            ->with('Default')
            ->willReturn($connectionMock);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        self::assertTrue(
            $this->subject->execute(),
        );
    }

    /**
     * @test
     */
    public function executeWithMultipleQueriesWillReturnTrue(): void
    {
        $this->subject->setSqlQuery("UPDATE what_ever;\nUPDATE that;\nUPDATE else;");

        $statementMock = $this->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::exactly(3))
            ->method('executeStatement')
            ->withConsecutive(
                [self::equalTo('UPDATE what_ever;'), self::equalTo([])],
                [self::equalTo('UPDATE that;'), self::equalTo([])],
                [self::equalTo('UPDATE else;'), self::equalTo([])],
            )
            ->willReturn($statementMock);

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->expects(self::once())
            ->method('getConnectionByName')
            ->with('Default')
            ->willReturn($connectionMock);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        self::assertTrue(
            $this->subject->execute(),
        );
    }
}
