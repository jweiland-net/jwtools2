<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3\CMS\Fluid\Tests\Functional\Backend\Browser;

use JWeiland\Jwtools2\Backend\Browser\FileBrowser;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Test case
 */
class FileBrowserTest extends FunctionalTestCase
{
    /**
     * @var FileBrowser
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/jwtools2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $this->subject = new FileBrowser();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $GLOBALS['BE_USER']
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function isValidReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->isValid()
        );
    }
}
