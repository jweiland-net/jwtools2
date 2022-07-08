<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3\CMS\Fluid\Tests\Functional\Hooks;

use JWeiland\Jwtools2\Hooks\InitializeStdWrap;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case
 */
class InitializeStdWrapTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var InitializeStdWrap
     */
    protected $subject;

    /**
     * @var ExtensionConfiguration|ObjectProphecy
     */
    protected $extensionConfigurationProphecy;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/jwtools2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationProphecy = $this->prophesize(ExtensionConfiguration::class);
        $this->subject = new InitializeStdWrap(
            $this->extensionConfigurationProphecy->reveal()
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->extensionConfigurationProphecy
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function postProcessWillNotModifyContentObjectBecauseOfNonArray(): void
    {
        $data = [
            'uid' => 123,
            'title' => 'Hello'
        ];

        $this->extensionConfigurationProphecy
            ->get('jwtools2')
            ->willReturn([
                'typo3TransferTypoScriptCurrent' => '1'
            ]);

        $contentObject = new ContentObjectRenderer();
        $contentObject->parentRecord = null;
        $contentObject->data = $data;

        $this->subject->postProcessContentObjectInitialization($contentObject);

        self::assertSame(
            $data,
            $contentObject->data
        );
    }

    /**
     * @test
     */
    public function postProcessWillNotModifyContentObjectBecauseOfEmptyArray(): void
    {
        $data = [
            'uid' => 123,
            'title' => 'Hello'
        ];

        $this->extensionConfigurationProphecy
            ->get('jwtools2')
            ->willReturn([
                'typo3TransferTypoScriptCurrent' => '1'
            ]);

        $contentObject = new ContentObjectRenderer();
        $contentObject->parentRecord = [];
        $contentObject->data = $data;

        $this->subject->postProcessContentObjectInitialization($contentObject);

        self::assertSame(
            $data,
            $contentObject->data
        );
    }

    /**
     * @test
     */
    public function postProcessWillNotModifyContentObjectBecauseFeatureIsDisabled(): void
    {
        $contentObject = new ContentObjectRenderer();

        $data = [
            'uid' => 123,
            'title' => 'Hello'
        ];

        $parentRecordData = $data;
        $parentRecordData[$contentObject->currentValKey] = 'Welcome';

        $this->extensionConfigurationProphecy
            ->get('jwtools2')
            ->willReturn([
                'typo3TransferTypoScriptCurrent' => '0'
            ]);

        $contentObject->parentRecord['data'] = $parentRecordData;
        $contentObject->data = $data;

        $this->subject->postProcessContentObjectInitialization($contentObject);

        self::assertSame(
            $data,
            $contentObject->data
        );
    }

    /**
     * @test
     */
    public function postProcessWillModifyContentObject(): void
    {
        $contentObject = new ContentObjectRenderer();

        $data = [
            'uid' => 123,
            'title' => 'Hello'
        ];

        $parentRecordData = $data;
        $parentRecordData[$contentObject->currentValKey] = 'Welcome';

        $this->extensionConfigurationProphecy
            ->get('jwtools2')
            ->willReturn([
                'typo3TransferTypoScriptCurrent' => '1'
            ]);

        $contentObject->parentRecord['data'] = $parentRecordData;
        $contentObject->data = $data;

        $this->subject->postProcessContentObjectInitialization($contentObject);

        self::assertSame(
            $parentRecordData,
            $contentObject->data
        );
    }
}
