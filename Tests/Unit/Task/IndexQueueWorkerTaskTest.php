<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\Task;

use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class IndexQueueWorkerTaskTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        GeneralUtility::setSingletonInstance(Scheduler::class, $this->createMock(Scheduler::class));
    }

    #[Test]
    public function taskHasNoConstructorDependencies(): void
    {
        // Scheduler tasks are restored via unserialize(), which does not call the constructor.
        // Injected services would be serialized into the task record and tasks saved with older
        // versions would crash with uninitialized typed properties.
        $constructor = (new \ReflectionClass(IndexQueueWorkerTask::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertSame(0, $constructor->getNumberOfParameters());
    }

    #[Test]
    public function getAdditionalInformationWithoutRootPageReturnsHint(): void
    {
        $registryMock = $this->createMock(Registry::class);
        $registryMock
            ->method('get')
            ->with('jwtools2-solr', 'rootPageId')
            ->willReturn(null);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock);

        self::assertSame(
            'Please execute this task first to retrieve site information',
            (new IndexQueueWorkerTask())->getAdditionalInformation(),
        );
    }

    #[Test]
    public function getAdditionalInformationWorksOnTaskRestoredWithoutConstructor(): void
    {
        $registryMock = $this->createMock(Registry::class);
        $registryMock
            ->method('get')
            ->willReturn(0);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock);

        // This is what unserialize() does with a task saved by an older version of jwtools2
        $subject = (new \ReflectionClass(IndexQueueWorkerTask::class))->newInstanceWithoutConstructor();

        self::assertSame(
            'Please execute this task first to retrieve site information',
            $subject->getAdditionalInformation(),
        );
    }

    #[Test]
    public function documentsToIndexLimitHasDefaultValue(): void
    {
        self::assertSame(
            50,
            (new IndexQueueWorkerTask())->getDocumentsToIndexLimit(),
        );
    }

    #[Test]
    public function setDocumentsToIndexLimitSetsLimit(): void
    {
        $subject = new IndexQueueWorkerTask();
        $subject->setDocumentsToIndexLimit(123);

        self::assertSame(
            123,
            $subject->getDocumentsToIndexLimit(),
        );
    }

    #[Test]
    public function maxSitesPerRunHasDefaultValue(): void
    {
        self::assertSame(
            10,
            (new IndexQueueWorkerTask())->getMaxSitesPerRun(),
        );
    }

    #[Test]
    public function setMaxSitesPerRunSetsValue(): void
    {
        $subject = new IndexQueueWorkerTask();
        $subject->setMaxSitesPerRun(3);

        self::assertSame(
            3,
            $subject->getMaxSitesPerRun(),
        );
    }
}
