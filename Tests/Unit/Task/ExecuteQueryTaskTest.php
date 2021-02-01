<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\Task;

use Doctrine\DBAL\Driver\Statement;
use JWeiland\Jwtools2\Task\ExecuteQueryTask;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;

/**
 * Test case.
 */
class ExecuteQueryTaskTest extends UnitTestCase
{
    /**
     * @var ExecuteQueryTask
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        // Because of AbstractTask __construct we have to set our own Scheduler class
        /** @var Scheduler|ObjectProphecy $schedulerProphecy */
        $schedulerProphecy = $this->prophesize(Scheduler::class);
        GeneralUtility::setSingletonInstance(Scheduler::class, $schedulerProphecy->reveal());

        $this->subject = new ExecuteQueryTask();
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function executeWithEmptyQueryWillReturnFalse()
    {
        self::assertFalse(
            $this->subject->execute()
        );
    }

    /**
     * @test
     */
    public function executeWithSingleQueryWillReturnTrue()
    {
        $this->subject->setSqlQuery('UPDATE what_ever;');

        /** @var Statement|ObjectProphecy $statementProphecy */
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy
            ->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var Connection|ObjectProphecy $connectionProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy
            ->query('UPDATE what_ever;')
            ->shouldBeCalled()
            ->willReturn($statementProphecy->reveal());

        /** @var ConnectionPool|ObjectProphecy $connectionPoolProphecy */
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy
            ->getConnectionByName('Default')
            ->shouldBeCalled()
            ->willReturn($connectionProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        self::assertTrue(
            $this->subject->execute()
        );
    }

    /**
     * @test
     */
    public function executeWithMultipleQueriesWillReturnTrue()
    {
        $this->subject->setSqlQuery("UPDATE this;\nUPDATE that;\nUPDATE else;");

        /** @var Statement|ObjectProphecy $statementProphecy */
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy
            ->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var Connection|ObjectProphecy $connectionProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy
            ->query('UPDATE this')
            ->shouldBeCalled()
            ->willReturn($statementProphecy->reveal());
        $connectionProphecy
            ->query('UPDATE that')
            ->shouldBeCalled()
            ->willReturn($statementProphecy->reveal());
        $connectionProphecy
            ->query('UPDATE else;')
            ->shouldBeCalled()
            ->willReturn($statementProphecy->reveal());

        /** @var ConnectionPool|ObjectProphecy $connectionPoolProphecy */
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy
            ->getConnectionByName('Default')
            ->shouldBeCalled()
            ->willReturn($connectionProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        self::assertTrue(
            $this->subject->execute()
        );
    }
}
