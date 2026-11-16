<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Functional\DependencyInjection;

use JWeiland\Jwtools2\Controller\FileMetaDataController;
use JWeiland\Jwtools2\Controller\SolrController;
use JWeiland\Jwtools2\Controller\ToolsController;
use JWeiland\Jwtools2\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;

/**
 * Backend module controllers are built by the DI container when the module is called.
 *
 * Do not re-declare promoted (readonly) properties of AbstractController in the constructor
 * of a child controller. Pass them to parent::__construct() as plain arguments instead.
 * Otherwise the parent constructor fails with "Cannot modify readonly property".
 */
class BackendControllerTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Extbase ConfigurationManager, injected into each ActionController, needs a request
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    public static function backendControllerDataProvider(): array
    {
        return [
            'ToolsController' => [ToolsController::class],
            'SolrController' => [SolrController::class],
            'FileMetaDataController' => [FileMetaDataController::class],
        ];
    }

    /**
     * @param class-string $className
     */
    #[Test]
    #[DataProvider('backendControllerDataProvider')]
    public function containerCreatesBackendController(string $className): void
    {
        self::assertInstanceOf(
            $className,
            $this->get($className),
        );
    }
}
