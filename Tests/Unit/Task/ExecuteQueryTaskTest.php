<?php

namespace JWeiland\Jwtools2\Tests\Unit\Task;

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
class AjaxTest extends UnitTestCase
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
    public function executeWithErrorWillReturnFalse()
    {
        /** @var Statement|ObjectProphecy $statementProphecy */
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy
            ->execute()
            ->shouldBeCalled()
            ->willReturn(false);

        /** @var Connection|ObjectProphecy $connectionProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy
            ->query('')
            ->shouldBeCalled()
            ->willReturn($statementProphecy->reveal());

        /** @var ConnectionPool|ObjectProphecy $connectionPoolProphecy */
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy
            ->getConnectionByName('Default')
            ->shouldBeCalled()
            ->willReturn($connectionProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        $this->assertFalse(
            $this->subject->execute()
        );
    }

    /**
     * @test
     */
    public function executeWithoutErrorWillReturnTrue()
    {
        /** @var Statement|ObjectProphecy $statementProphecy */
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy
            ->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var Connection|ObjectProphecy $connectionProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy
            ->query('')
            ->shouldBeCalled()
            ->willReturn($statementProphecy->reveal());

        /** @var ConnectionPool|ObjectProphecy $connectionPoolProphecy */
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy
            ->getConnectionByName('Default')
            ->shouldBeCalled()
            ->willReturn($connectionProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        $this->assertTrue(
            $this->subject->execute()
        );
    }

    /**
     * @test
     */
    public function executeWithQueryWillReturnTrue()
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

        $this->assertTrue(
            $this->subject->execute()
        );
    }
}
