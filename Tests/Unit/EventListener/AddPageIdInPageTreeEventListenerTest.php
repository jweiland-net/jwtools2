<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\EventListener;

use JWeiland\Jwtools2\EventListener\AddPageIdInPageTreeEventListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AddPageIdInPageTreeEventListenerTest extends UnitTestCase
{
    protected ExtensionConfiguration&MockObject $extensionConfigurationMock;

    protected AddPageIdInPageTreeEventListener $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);

        $this->subject = new AddPageIdInPageTreeEventListener($this->extensionConfigurationMock);
    }

    protected function tearDown(): void
    {
        unset(
            $this->extensionConfigurationMock,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function invokeAddsUserTsConfigIfSettingIsActivated(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('jwtools2', 'typo3EnableUidInPageTree')
            ->willReturn('1');

        $event = new BeforeLoadedUserTsConfigEvent();
        ($this->subject)($event);

        self::assertSame(
            ['options.pageTree.showPageIdWithTitle = 1'],
            $event->getTsConfig(),
        );
    }

    #[Test]
    public function invokeDoesNotAddUserTsConfigIfSettingIsDeactivated(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('jwtools2', 'typo3EnableUidInPageTree')
            ->willReturn('0');

        $event = new BeforeLoadedUserTsConfigEvent();
        ($this->subject)($event);

        self::assertSame([], $event->getTsConfig());
    }

    #[Test]
    public function invokeDoesNotAddUserTsConfigIfSettingDoesNotExist(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willThrowException(new ExtensionConfigurationPathDoesNotExistException('', 1730700000));

        $event = new BeforeLoadedUserTsConfigEvent();
        ($this->subject)($event);

        self::assertSame([], $event->getTsConfig());
    }
}
