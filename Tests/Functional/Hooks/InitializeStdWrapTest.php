<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace TYPO3\CMS\Fluid\Tests\Functional\Hooks;

use JWeiland\Jwtools2\Hooks\InitializeStdWrap;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class InitializeStdWrapTest extends FunctionalTestCase
{
    protected InitializeStdWrap $subject;

    protected ExtensionConfiguration | MockObject $extensionConfigurationMock;

    protected array $testExtensionsToLoad = [
        'jweiland/jwtools2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->getAccessibleMock(ExtensionConfiguration::class);
        $this->subject = new InitializeStdWrap(
            $this->extensionConfigurationMock
        );
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

        $this->extensionConfigurationMock
            ->expects(self::any())
            ->method('get')
            ->with('jwtools2')
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

        $this->extensionConfigurationMock
            ->expects(self::any())
            ->method('get')
            ->with('jwtools2')
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

        $this->extensionConfigurationMock
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('jwtools2')
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
            'colPos' => 1,
            'title' => 'Hello'
        ];

        $parentRecordData = $data;
        $parentRecordData[$contentObject->currentValKey] = 'Welcome';

        $this->extensionConfigurationMock
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('jwtools2')
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
