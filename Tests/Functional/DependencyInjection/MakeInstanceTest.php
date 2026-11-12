<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Functional\DependencyInjection;

use JWeiland\Jwtools2\Controller\Ajax\SysFileController;
use JWeiland\Jwtools2\Domain\Repository\SolrRepository;
use JWeiland\Jwtools2\EventListener\ReduceCategoryTreeToPageTree;
use JWeiland\Jwtools2\Hooks\CachingFrameworkLoggerHook;
use JWeiland\Jwtools2\Hooks\MoveTranslatedContentElementsHook;
use JWeiland\Jwtools2\Hooks\ValidateFileMetaDataOnSaveHook;
use JWeiland\Jwtools2\Service\SolrService;
use JWeiland\Jwtools2\Task\ExecuteQueryTask;
use JWeiland\Jwtools2\Task\ExecuteQueryTaskAdditionalFieldProvider;
use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use JWeiland\Jwtools2\Task\IndexQueueWorkerTaskAdditionalFieldProvider;
use JWeiland\Jwtools2\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * GeneralUtility::makeInstance() only uses the DI container for public services. For private
 * services it silently falls back to "new $className()" without any constructor arguments.
 *
 * So every class with constructor dependencies, which is created by makeInstance() (by jwtools2
 * itself or by TYPO3 core: hooks, scheduler, AJAX routes, reports, ...), must be public in
 * Configuration/Services.yaml. Otherwise it crashes with "Too few arguments to function
 * ::__construct()" at runtime.
 *
 * If you add a constructor to one of these classes or create a new class via makeInstance(),
 * add it to the data provider below.
 */
class MakeInstanceTest extends AbstractFunctionalTestCase
{
    public static function classesCreatedByMakeInstanceDataProvider(): array
    {
        return [
            // jwtools2: IndexQueueWorkerTask, SolrController, AjaxSolrController
            'SolrService' => [SolrService::class],
            // jwtools2: AjaxSolrController
            'SolrRepository' => [SolrRepository::class],
            // TYPO3: SchedulerModuleController
            'IndexQueueWorkerTask' => [IndexQueueWorkerTask::class],
            'IndexQueueWorkerTaskAdditionalFieldProvider' => [IndexQueueWorkerTaskAdditionalFieldProvider::class],
            'ExecuteQueryTask' => [ExecuteQueryTask::class],
            'ExecuteQueryTaskAdditionalFieldProvider' => [ExecuteQueryTaskAdditionalFieldProvider::class],
            // TYPO3: AJAX route target
            'SysFileController' => [SysFileController::class],
            // TYPO3: DataHandler hooks
            'MoveTranslatedContentElementsHook' => [MoveTranslatedContentElementsHook::class],
            'ValidateFileMetaDataOnSaveHook' => [ValidateFileMetaDataOnSaveHook::class],
            // TYPO3: VariableFrontend "set" hook
            'CachingFrameworkLoggerHook' => [CachingFrameworkLoggerHook::class],
        ];
    }

    /**
     * @param class-string $className
     */
    #[Test]
    #[DataProvider('classesCreatedByMakeInstanceDataProvider')]
    public function makeInstanceCreatesClassWithAllDependencies(string $className): void
    {
        self::assertInstanceOf(
            $className,
            GeneralUtility::makeInstance($className),
        );
    }

    #[Test]
    public function eventListenerWithQueryGeneratorCanBeBuilt(): void
    {
        // QueryGenerator was created by makeInstance() inside the listener before,
        // which crashed as QueryGenerator has constructor dependencies.
        self::assertInstanceOf(
            ReduceCategoryTreeToPageTree::class,
            $this->get(ReduceCategoryTreeToPageTree::class),
        );
    }
}
