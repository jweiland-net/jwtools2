<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\EventListener;

use JWeiland\Jwtools2\EventListener\ReplaceInvalidMetaDataImageEventListener;
use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use JWeiland\Jwtools2\Service\PlaceholderImageService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ReplaceInvalidMetaDataImageEventListenerTest extends UnitTestCase
{
    /**
     * @var ExtensionConfiguration|MockObject
     */
    protected $extensionConfigurationMock;

    /**
     * @var FileMetaDataValidationService|MockObject
     */
    protected $fileMetaDataValidationServiceMock;

    /**
     * @var PlaceholderImageService|MockObject
     */
    protected $placeholderImageServiceMock;

    protected ReplaceInvalidMetaDataImageEventListener $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->fileMetaDataValidationServiceMock = $this->createMock(FileMetaDataValidationService::class);
        $this->placeholderImageServiceMock = $this->createMock(PlaceholderImageService::class);

        $this->subject = new ReplaceInvalidMetaDataImageEventListener(
            $this->extensionConfigurationMock,
            $this->fileMetaDataValidationServiceMock,
            $this->placeholderImageServiceMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TYPO3_REQUEST'],
            $this->extensionConfigurationMock,
            $this->fileMetaDataValidationServiceMock,
            $this->placeholderImageServiceMock,
            $this->subject,
        );

        parent::tearDown();
    }

    protected function enable(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('jwtools2', 'typo3ReplaceFrontendImagesWithInvalidMetaData')
            ->willReturn(true);
    }

    protected function disable(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('jwtools2', 'typo3ReplaceFrontendImagesWithInvalidMetaData')
            ->willReturn(false);
    }

    protected function setFrontendRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    protected function setBackendRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    protected function createEvent(File $file, array $configuration = [], string $taskType = 'Image.CropScaleMask'): BeforeFileProcessingEvent
    {
        return new BeforeFileProcessingEvent(
            $this->createMock(DriverInterface::class),
            $this->createMock(ProcessedFile::class),
            $file,
            $taskType,
            $configuration,
        );
    }

    protected function createImageFile(string $name = 'broken-image.jpg'): File&MockObject
    {
        $file = $this->createMock(File::class);
        $file->method('getType')->willReturn(FileType::IMAGE->value);
        $file->method('getName')->willReturn($name);

        return $file;
    }

    #[Test]
    public function invokeDoesNothingWhenFeatureIsDisabled(): void
    {
        $this->disable();
        $this->setFrontendRequest();

        $this->fileMetaDataValidationServiceMock->expects(self::never())->method('hasValidMetaData');
        $this->placeholderImageServiceMock->expects(self::never())->method('getPlaceholderFile');

        $event = $this->createEvent($this->createImageFile());
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }

    #[Test]
    public function invokeDoesNothingOnBackendRequests(): void
    {
        $this->enable();
        $this->setBackendRequest();

        $this->fileMetaDataValidationServiceMock->expects(self::never())->method('hasValidMetaData');

        $event = $this->createEvent($this->createImageFile());
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }

    #[Test]
    public function invokeDoesNothingWhenNoRequestIsAvailableAtAll(): void
    {
        $this->enable();
        unset($GLOBALS['TYPO3_REQUEST']);

        $this->fileMetaDataValidationServiceMock->expects(self::never())->method('hasValidMetaData');

        $event = $this->createEvent($this->createImageFile());
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }

    #[Test]
    public function invokeDoesNothingForNonImageFiles(): void
    {
        $this->enable();
        $this->setFrontendRequest();

        $file = $this->createMock(File::class);
        $file->method('getType')->willReturn(FileType::TEXT->value);

        $this->fileMetaDataValidationServiceMock->expects(self::never())->method('hasValidMetaData');

        $event = $this->createEvent($file);
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }

    #[Test]
    public function invokeDoesNothingForThePlaceholderFileItselfToAvoidRecursion(): void
    {
        $this->enable();
        $this->setFrontendRequest();

        $file = $this->createImageFile('placeholder_300x300.png');

        $this->fileMetaDataValidationServiceMock->expects(self::never())->method('hasValidMetaData');

        $event = $this->createEvent($file);
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }

    #[Test]
    public function invokeDoesNothingWhenMetaDataIsAlreadyValid(): void
    {
        $this->enable();
        $this->setFrontendRequest();

        $file = $this->createImageFile();
        $this->fileMetaDataValidationServiceMock->method('hasValidMetaData')->with($file)->willReturn(true);

        $this->placeholderImageServiceMock->expects(self::never())->method('getPlaceholderFile');

        $event = $this->createEvent($file);
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }

    #[Test]
    public function invokeDoesNothingWhenNoPlaceholderCouldBeBuilt(): void
    {
        $this->enable();
        $this->setFrontendRequest();

        $file = $this->createImageFile();
        $this->fileMetaDataValidationServiceMock->method('hasValidMetaData')->willReturn(false);
        $this->placeholderImageServiceMock->method('getPlaceholderFile')->willReturn(null);

        $event = $this->createEvent($file);
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }

    #[Test]
    public function invokeSetsProcessedFileFromPlaceholderWhenMetaDataIsInvalid(): void
    {
        $this->enable();
        $this->setFrontendRequest();

        $file = $this->createImageFile();
        $this->fileMetaDataValidationServiceMock->method('hasValidMetaData')->willReturn(false);

        $configuration = ['width' => '300m', 'height' => '150c'];

        $placeholderFileMock = $this->createMock(File::class);
        $this->placeholderImageServiceMock
            ->expects(self::once())
            ->method('getPlaceholderFile')
            ->with(300, 150)
            ->willReturn($placeholderFileMock);

        $newProcessedFileMock = $this->createMock(ProcessedFile::class);
        $placeholderFileMock->expects(self::once())
            ->method('process')
            ->with('Image.CropScaleMask', $configuration)
            ->willReturn($newProcessedFileMock);

        $event = $this->createEvent($file, $configuration);

        ($this->subject)($event);

        self::assertSame($newProcessedFileMock, $event->getProcessedFile());
    }

    #[Test]
    public function invokeUsesDefaultDimensionsWhenConfigurationHasNone(): void
    {
        $this->enable();
        $this->setFrontendRequest();

        $file = $this->createImageFile();
        $this->fileMetaDataValidationServiceMock->method('hasValidMetaData')->willReturn(false);

        $placeholderFileMock = $this->createMock(File::class);
        $this->placeholderImageServiceMock
            ->expects(self::once())
            ->method('getPlaceholderFile')
            ->with(300, 300)
            ->willReturn($placeholderFileMock);

        $placeholderFileMock->method('process')->willReturn($this->createMock(ProcessedFile::class));

        $event = $this->createEvent($file, []);

        ($this->subject)($event);
    }

    #[Test]
    public function invokeFailsOpenWhenProcessingThePlaceholderThrows(): void
    {
        $this->enable();
        $this->setFrontendRequest();

        $file = $this->createImageFile();
        $this->fileMetaDataValidationServiceMock->method('hasValidMetaData')->willReturn(false);

        $placeholderFileMock = $this->createMock(File::class);
        $this->placeholderImageServiceMock->method('getPlaceholderFile')->willReturn($placeholderFileMock);
        $placeholderFileMock->method('process')->willThrowException(new \RuntimeException('processor unavailable'));

        $event = $this->createEvent($file);
        $initialProcessedFile = $event->getProcessedFile();

        ($this->subject)($event);

        self::assertSame($initialProcessedFile, $event->getProcessedFile());
    }
}
