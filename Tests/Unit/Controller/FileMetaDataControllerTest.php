<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\Controller;

use JWeiland\Jwtools2\Controller\FileMetaDataController;
use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use JWeiland\Jwtools2\Service\FileReferenceResolverService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Covers the pure, side-effect-free helper methods of FileMetaDataController
 * (status/reference-scope classification and filter normalisation). The actual
 * listAction(), which needs a real request/view and database access, is covered
 * by a Functional test instead.
 */
class FileMetaDataControllerTest extends UnitTestCase
{
    protected FileMetaDataController $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // ModuleTemplateFactory is a "final class" with several heavy dependencies of its own,
        // and none of its methods are exercised by the pure helper methods under test here -
        // an uninitialised instance obtained via Reflection is sufficient and avoids having to
        // construct (or attempt to mock) a final class.
        $moduleTemplateFactory = (new \ReflectionClass(ModuleTemplateFactory::class))
            ->newInstanceWithoutConstructor();

        $this->subject = new FileMetaDataController(
            $moduleTemplateFactory,
            $this->createMock(FileMetaDataValidationService::class),
            $this->createMock(FileReferenceResolverService::class),
            $this->createMock(ConnectionPool::class),
            $this->createMock(ResourceFactory::class),
        );
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    protected function invokeProtectedMethod(string $method, array $arguments = []): mixed
    {
        $reflectionMethod = new \ReflectionMethod($this->subject, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($this->subject, $arguments);
    }

    // -- getStatus() -------------------------------------------------------------------

    #[Test]
    public function getStatusReturnsValidWhenNoColumnsAreMissing(): void
    {
        self::assertSame(
            'valid',
            $this->invokeProtectedMethod('getStatus', [[], ['creator', 'copyright']]),
        );
    }

    #[Test]
    public function getStatusReturnsValidWhenNoColumnsAreRequiredAtAll(): void
    {
        self::assertSame(
            'valid',
            $this->invokeProtectedMethod('getStatus', [[], []]),
        );
    }

    #[Test]
    public function getStatusReturnsMissingWhenAllRequiredColumnsAreMissing(): void
    {
        self::assertSame(
            'missing',
            $this->invokeProtectedMethod('getStatus', [['creator', 'copyright'], ['creator', 'copyright']]),
        );
    }

    #[Test]
    public function getStatusReturnsPartialWhenSomeButNotAllColumnsAreMissing(): void
    {
        self::assertSame(
            'partial',
            $this->invokeProtectedMethod('getStatus', [['copyright'], ['creator', 'copyright']]),
        );
    }

    // -- getReferenceScope() -----------------------------------------------------------

    #[Test]
    public function getReferenceScopeReturnsOrphanedWhenThereAreNoReferences(): void
    {
        self::assertSame(
            'orphaned',
            $this->invokeProtectedMethod('getReferenceScope', [[]]),
        );
    }

    #[Test]
    public function getReferenceScopeReturnsTtContentWhenOnlyReferencedByContentElements(): void
    {
        $references = [
            ['tablename' => 'tt_content', 'recuid' => 1, 'field' => 'image'],
            ['tablename' => 'tt_content', 'recuid' => 2, 'field' => 'image'],
        ];

        self::assertSame(
            'tt_content',
            $this->invokeProtectedMethod('getReferenceScope', [$references]),
        );
    }

    #[Test]
    public function getReferenceScopeReturnsOtherWhenOnlyReferencedByNonContentElementRecords(): void
    {
        $references = [
            ['tablename' => 'tx_news_domain_model_news', 'recuid' => 1, 'field' => 'image'],
        ];

        self::assertSame(
            'other',
            $this->invokeProtectedMethod('getReferenceScope', [$references]),
        );
    }

    #[Test]
    public function getReferenceScopeReturnsBothWhenReferencedByContentElementsAndOtherRecords(): void
    {
        $references = [
            ['tablename' => 'tt_content', 'recuid' => 1, 'field' => 'image'],
            ['tablename' => 'tx_news_domain_model_news', 'recuid' => 2, 'field' => 'image'],
        ];

        self::assertSame(
            'both',
            $this->invokeProtectedMethod('getReferenceScope', [$references]),
        );
    }

    // -- getFilters() -------------------------------------------------------------------

    #[Test]
    public function getFiltersReturnsAllDefaultsForEmptyInput(): void
    {
        self::assertSame(
            [
                'status' => 'all',
                'missingColumn' => '',
                'referenceScope' => 'all',
                'storage' => '',
                'extension' => '',
                'search' => '',
                'page' => '1',
            ],
            $this->invokeProtectedMethod('getFilters', [[]]),
        );
    }

    #[Test]
    public function getFiltersKeepsProvidedValuesAndTrimsSearch(): void
    {
        self::assertSame(
            [
                'status' => 'missing',
                'missingColumn' => 'creator',
                'referenceScope' => 'orphaned',
                'storage' => '1',
                'extension' => 'jpg',
                'search' => 'forest',
                'page' => '3',
            ],
            $this->invokeProtectedMethod('getFilters', [[
                'status' => 'missing',
                'missingColumn' => 'creator',
                'referenceScope' => 'orphaned',
                'storage' => '1',
                'extension' => 'jpg',
                'search' => '  forest  ',
                'page' => '3',
            ]]),
        );
    }
}
