<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\Service;

use JWeiland\Jwtools2\Service\PlaceholderImageService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * PlaceholderImageService is a "final" class, so its protected helper methods are exercised
 * here via reflection rather than an overriding test subclass. The public getPlaceholderFile()
 * entry point is additionally tested end-to-end with fully mocked collaborators.
 */
class PlaceholderImageServiceTest extends UnitTestCase
{
    protected const API_URL_KEY = 'typo3PlaceholderImageApiUrl';

    protected const STORAGE_FOLDER_KEY = 'typo3PlaceholderImageStorageFolder';

    /**
     * @var ExtensionConfiguration|MockObject
     */
    protected $extensionConfigurationMock;

    /**
     * @var RequestFactory|MockObject
     */
    protected $requestFactoryMock;

    /**
     * @var ResourceFactory|MockObject
     */
    protected $resourceFactoryMock;

    protected PlaceholderImageService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->resourceFactoryMock = $this->createMock(ResourceFactory::class);

        $this->subject = new PlaceholderImageService(
            $this->extensionConfigurationMock,
            $this->requestFactoryMock,
            $this->resourceFactoryMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->extensionConfigurationMock,
            $this->requestFactoryMock,
            $this->resourceFactoryMock,
            $this->subject,
        );

        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $configuration
     */
    protected function configureExtensionConfiguration(array $configuration): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturnCallback(
                static fn(string $extension, string $path = '') => $configuration[$path] ?? '',
            );
    }

    protected function invokeProtectedMethod(string $method, array $arguments = []): mixed
    {
        $reflectionMethod = new \ReflectionMethod($this->subject, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($this->subject, $arguments);
    }

    // -- getExtensionForContentType() -----------------------------------------------------

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function contentTypeDataProvider(): array
    {
        return [
            'png' => ['image/png', 'png'],
            'jpeg' => ['image/jpeg', 'jpg'],
            'jpg alias' => ['image/jpg', 'jpg'],
            'gif' => ['image/gif', 'gif'],
            'webp' => ['image/webp', 'webp'],
            'svg is not rasterizable' => ['image/svg+xml', null],
            'unknown type' => ['text/html', null],
            'empty string' => ['', null],
            'charset suffix is ignored' => ['image/png; charset=binary', 'png'],
            'case insensitive' => ['IMAGE/PNG', 'png'],
            'surrounding whitespace' => ['  image/gif  ', 'gif'],
        ];
    }

    #[Test]
    #[DataProvider('contentTypeDataProvider')]
    public function getExtensionForContentTypeMapsKnownRasterTypes(string $contentType, ?string $expected): void
    {
        self::assertSame(
            $expected,
            $this->invokeProtectedMethod('getExtensionForContentType', [$contentType]),
        );
    }

    // -- buildApiUrl() ---------------------------------------------------------------------

    #[Test]
    public function buildApiUrlReturnsNullWhenTemplateIsEmpty(): void
    {
        $this->configureExtensionConfiguration([self::API_URL_KEY => '']);

        self::assertNull(
            $this->invokeProtectedMethod('buildApiUrl', [300, 300]),
        );
    }

    #[Test]
    public function buildApiUrlReplacesWidthAndHeightPlaceholders(): void
    {
        $this->configureExtensionConfiguration([
            self::API_URL_KEY => 'https://placehold.co/{width}x{height}/eeeeee/999999.png?text=Metadata+missing',
        ]);

        self::assertSame(
            'https://placehold.co/300x150/eeeeee/999999.png?text=Metadata+missing',
            $this->invokeProtectedMethod('buildApiUrl', [300, 150]),
        );
    }

    #[Test]
    public function buildApiUrlTrimsTemplate(): void
    {
        $this->configureExtensionConfiguration([
            self::API_URL_KEY => '  https://example.org/{width}x{height}.png  ',
        ]);

        self::assertSame(
            'https://example.org/300x300.png',
            $this->invokeProtectedMethod('buildApiUrl', [300, 300]),
        );
    }

    // -- getCacheFolder() edge cases (no real storage needed) -------------------------------

    #[Test]
    public function getCacheFolderReturnsNullWhenIdentifierIsEmpty(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => '']);

        $this->resourceFactoryMock->expects(self::never())->method('getStorageObject');

        self::assertNull(
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    #[Test]
    public function getCacheFolderReturnsNullWhenIdentifierHasNoColon(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => 'placeholder_images']);

        $this->resourceFactoryMock->expects(self::never())->method('getStorageObject');

        self::assertNull(
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    #[Test]
    public function getCacheFolderReturnsNullWhenStorageUidPartIsEmpty(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => ':/placeholder_images/']);

        $this->resourceFactoryMock->expects(self::never())->method('getStorageObject');

        self::assertNull(
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    #[Test]
    public function getCacheFolderReturnsNullWhenFolderPathPartIsEmpty(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => '1:']);

        $this->resourceFactoryMock->expects(self::never())->method('getStorageObject');

        self::assertNull(
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    #[Test]
    public function getCacheFolderReturnsNullWhenStorageDoesNotExist(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => '1:/placeholder_images/']);

        $this->resourceFactoryMock
            ->method('getStorageObject')
            ->willThrowException(new \InvalidArgumentException('Storage does not exist', 1000));

        self::assertNull(
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    #[Test]
    public function getCacheFolderReturnsExistingFolder(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => '1:/placeholder_images/']);

        $folderMock = $this->createMock(Folder::class);
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('hasFolder')->with('/placeholder_images/')->willReturn(true);
        $storageMock->expects(self::never())->method('createFolder');
        $storageMock->method('getFolder')->with('/placeholder_images/')->willReturn($folderMock);

        $this->resourceFactoryMock->method('getStorageObject')->with(1)->willReturn($storageMock);

        self::assertSame(
            $folderMock,
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    #[Test]
    public function getCacheFolderCreatesFolderWhenItDoesNotExistYet(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => '1:/placeholder_images/']);

        $folderMock = $this->createMock(Folder::class);
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('hasFolder')->willReturn(false);
        $storageMock->expects(self::once())
            ->method('createFolder')
            ->with('/placeholder_images/')
            ->willReturn($folderMock);

        $this->resourceFactoryMock->method('getStorageObject')->willReturn($storageMock);

        self::assertSame(
            $folderMock,
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    #[Test]
    public function getCacheFolderReturnsNullWhenCreateFolderFails(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => '1:/placeholder_images/']);

        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('hasFolder')->willReturn(false);
        $storageMock->method('createFolder')->willThrowException(new \RuntimeException('cannot create'));

        $this->resourceFactoryMock->method('getStorageObject')->willReturn($storageMock);

        self::assertNull(
            $this->invokeProtectedMethod('getCacheFolder'),
        );
    }

    // -- getPlaceholderFile() end-to-end with mocked collaborators --------------------------

    protected function configureValidCacheFolder(): Folder&MockObject
    {
        $this->configureExtensionConfiguration([
            self::STORAGE_FOLDER_KEY => '1:/placeholder_images/',
            self::API_URL_KEY => 'https://placehold.co/{width}x{height}.png',
        ]);

        $folderMock = $this->createMock(Folder::class);
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('hasFolder')->willReturn(true);
        $storageMock->method('getFolder')->willReturn($folderMock);
        $this->resourceFactoryMock->method('getStorageObject')->willReturn($storageMock);

        return $folderMock;
    }

    #[Test]
    public function getPlaceholderFileReturnsNullWhenStorageFolderIsNotConfigured(): void
    {
        $this->configureExtensionConfiguration([self::STORAGE_FOLDER_KEY => '']);

        self::assertNull(
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileReturnsAlreadyCachedFileWithoutCallingTheApi(): void
    {
        $folderMock = $this->configureValidCacheFolder();
        $cachedFileMock = $this->createMock(File::class);

        $folderMock->method('hasFile')->with('placeholder_300x300.png')->willReturn(true);
        $folderMock->method('getFile')->with('placeholder_300x300.png')->willReturn($cachedFileMock);

        $this->requestFactoryMock->expects(self::never())->method('request');

        self::assertSame(
            $cachedFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileFindsCachedFileWithNonDefaultExtension(): void
    {
        // Only a ".gif" was cached for this dimension (e.g. from a differently configured
        // API in the past) - the cache lookup must still find it instead of re-fetching.
        $folderMock = $this->configureValidCacheFolder();
        $cachedFileMock = $this->createMock(File::class);

        $folderMock->method('hasFile')->willReturnCallback(
            static fn(string $fileName) => $fileName === 'placeholder_300x300.gif',
        );
        $folderMock->method('getFile')->with('placeholder_300x300.gif')->willReturn($cachedFileMock);

        $this->requestFactoryMock->expects(self::never())->method('request');

        self::assertSame(
            $cachedFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileFetchesAndStoresNewPngFile(): void
    {
        $folderMock = $this->configureValidCacheFolder();
        $folderMock->method('hasFile')->willReturn(false);

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn('binary-image-data');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getHeaderLine')->with('Content-Type')->willReturn('image/png');
        $responseMock->method('getBody')->willReturn($streamMock);

        $this->requestFactoryMock
            ->expects(self::once())
            ->method('request')
            ->with('https://placehold.co/300x300.png')
            ->willReturn($responseMock);

        $storedFileMock = $this->createMock(File::class);
        $folderMock->expects(self::once())
            ->method('addFile')
            ->with(self::isType('string'), 'placeholder_300x300.png')
            ->willReturn($storedFileMock);

        self::assertSame(
            $storedFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileFallsBackWhenApiReturnsNonRasterizableContentType(): void
    {
        $folderMock = $this->configureValidCacheFolder();

        $fallbackFileMock = $this->createMock(File::class);
        $folderMock->method('hasFile')->willReturnCallback(
            static fn(string $fileName) => $fileName === 'placeholder_fallback.png',
        );
        $folderMock->method('getFile')->with('placeholder_fallback.png')->willReturn($fallbackFileMock);

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn('<svg></svg>');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getHeaderLine')->willReturn('image/svg+xml');
        $responseMock->method('getBody')->willReturn($streamMock);

        $this->requestFactoryMock->method('request')->willReturn($responseMock);

        $folderMock->expects(self::never())->method('addFile');

        self::assertSame(
            $fallbackFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileFallsBackWhenApiReturnsErrorStatus(): void
    {
        $folderMock = $this->configureValidCacheFolder();

        $fallbackFileMock = $this->createMock(File::class);
        $folderMock->method('hasFile')->willReturnCallback(
            static fn(string $fileName) => $fileName === 'placeholder_fallback.png',
        );
        $folderMock->method('getFile')->with('placeholder_fallback.png')->willReturn($fallbackFileMock);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(500);

        $this->requestFactoryMock->method('request')->willReturn($responseMock);

        self::assertSame(
            $fallbackFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileFallsBackWhenRequestThrows(): void
    {
        $folderMock = $this->configureValidCacheFolder();

        $fallbackFileMock = $this->createMock(File::class);
        $folderMock->method('hasFile')->willReturnCallback(
            static fn(string $fileName) => $fileName === 'placeholder_fallback.png',
        );
        $folderMock->method('getFile')->with('placeholder_fallback.png')->willReturn($fallbackFileMock);

        $this->requestFactoryMock
            ->method('request')
            ->willThrowException(new \RuntimeException('Connection refused'));

        self::assertSame(
            $fallbackFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileFallsBackWhenNoApiUrlIsConfigured(): void
    {
        $folderMock = $this->createMock(Folder::class);
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('hasFolder')->willReturn(true);
        $storageMock->method('getFolder')->willReturn($folderMock);
        $this->resourceFactoryMock->method('getStorageObject')->willReturn($storageMock);

        $this->configureExtensionConfiguration([
            self::STORAGE_FOLDER_KEY => '1:/placeholder_images/',
            self::API_URL_KEY => '',
        ]);

        $fallbackFileMock = $this->createMock(File::class);
        $folderMock->method('hasFile')->willReturnCallback(
            static fn(string $fileName) => $fileName === 'placeholder_fallback.png',
        );
        $folderMock->method('getFile')->with('placeholder_fallback.png')->willReturn($fallbackFileMock);

        $this->requestFactoryMock->expects(self::never())->method('request');

        self::assertSame(
            $fallbackFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileFallsBackWhenStoringTheDownloadedFileFails(): void
    {
        $folderMock = $this->configureValidCacheFolder();

        $fallbackFileMock = $this->createMock(File::class);
        $folderMock->method('hasFile')->willReturnCallback(
            static fn(string $fileName) => $fileName === 'placeholder_fallback.png',
        );
        $folderMock->method('getFile')->with('placeholder_fallback.png')->willReturn($fallbackFileMock);

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn('binary-image-data');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getHeaderLine')->willReturn('image/png');
        $responseMock->method('getBody')->willReturn($streamMock);

        $this->requestFactoryMock->method('request')->willReturn($responseMock);

        $folderMock->method('addFile')->willThrowException(new \RuntimeException('disk full'));

        self::assertSame(
            $fallbackFileMock,
            $this->subject->getPlaceholderFile(300, 300),
        );
    }

    #[Test]
    public function getPlaceholderFileReturnsNullWhenNothingIsCachedApiIsNotConfiguredAndBundledAssetCannotBeResolved(): void
    {
        // GeneralUtility::getFileAbsFileName('EXT:jwtools2/...') can only resolve to a real path
        // when the extension is registered as an active package - that is only guaranteed in a
        // Functional test (see Tests/Functional/Service/PlaceholderImageServiceTest.php, which
        // covers the successful "bundled asset" path end-to-end). In this Unit test context the
        // package is not registered, so resolution fails and getFallbackFile() must degrade to
        // null rather than throwing.
        $folderMock = $this->createMock(Folder::class);
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('hasFolder')->willReturn(true);
        $storageMock->method('getFolder')->willReturn($folderMock);
        $this->resourceFactoryMock->method('getStorageObject')->willReturn($storageMock);

        $this->configureExtensionConfiguration([
            self::STORAGE_FOLDER_KEY => '1:/placeholder_images/',
            self::API_URL_KEY => '',
        ]);

        $folderMock->method('hasFile')->willReturn(false);
        $folderMock->expects(self::never())->method('addFile');

        self::assertNull($this->subject->getPlaceholderFile(300, 300));
    }
}
