<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Unit\Service;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FileMetaDataValidationServiceTest extends UnitTestCase
{
    protected FileMetaDataValidationService $subject;

    /**
     * @var ExtensionConfiguration|MockObject
     */
    protected $extensionConfigurationMock;

    /**
     * @var ConnectionPool|MockObject
     */
    protected $connectionPoolMock;

    /**
     * @var Table|MockObject
     */
    protected $sysFileTableMock;

    /**
     * @var Table|MockObject
     */
    protected $sysFileMetadataTableMock;

    /**
     * @var ResourceStorage|MockObject
     */
    protected $storageMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);

        // isValidColumn() introspects sys_file first and, if the column is not found there,
        // falls back to sys_file_metadata. Both tables are backed by the same mocked
        // connection/schema manager here; only the two Table doubles differ in which
        // columns they report as existing.
        $this->sysFileTableMock = $this->createMock(Table::class);
        $this->sysFileMetadataTableMock = $this->createMock(Table::class);

        $schemaManagerMock = $this->createMock(AbstractSchemaManager::class);
        $schemaManagerMock->method('tablesExist')->willReturn(true);
        $schemaManagerMock->method('introspectTable')->willReturnCallback(
            fn(string $table) => $table === 'sys_file_metadata' ? $this->sysFileMetadataTableMock : $this->sysFileTableMock,
        );

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('createSchemaManager')->willReturn($schemaManagerMock);

        $this->connectionPoolMock = $this->createMock(ConnectionPool::class);
        $this->connectionPoolMock->method('getConnectionForTable')->willReturn($connectionMock);

        $this->storageMock = $this->createMock(ResourceStorage::class);

        $this->subject = new FileMetaDataValidationService(
            $this->extensionConfigurationMock,
            $this->connectionPoolMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->extensionConfigurationMock,
            $this->connectionPoolMock,
            $this->sysFileTableMock,
            $this->sysFileMetadataTableMock,
            $this->storageMock,
        );

        parent::tearDown();
    }

    protected function configureRequiredColumnsConfiguration(string $value): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('jwtools2', 'typo3RequiredColumnsForFiles')
            ->willReturn($value);
    }

    /**
     * @param array<int, string> $existingColumns
     */
    protected function makeColumnsExistOnSysFileMetadata(array $existingColumns): void
    {
        $this->sysFileTableMock->method('hasColumn')->willReturn(false);
        $this->sysFileMetadataTableMock->method('hasColumn')->willReturnCallback(
            static fn(string $column) => in_array($column, $existingColumns, true),
        );
    }

    /**
     * @param array<string, mixed> $metaData
     */
    protected function createImageFile(array $metaData): File
    {
        $file = new File(
            [
                'identifier' => '/foo.jpg',
                'name' => 'foo.jpg',
                'type' => FileType::IMAGE->value,
            ],
            $this->storageMock,
        );

        // File's constructor only forwards $metaData to MetaDataAspect::add() when it is
        // non-empty, so an empty array here would leave the aspect "not loaded" and any
        // later ->get() call would try to lazy-load from the (unavailable) MetaDataRepository.
        // Calling add() directly marks it as loaded regardless of emptiness.
        $file->getMetaData()->add($metaData);

        return $file;
    }

    #[Test]
    public function getRequiredColumnsReturnsEmptyArrayWhenConfigurationIsEmpty(): void
    {
        $this->configureRequiredColumnsConfiguration('');

        self::assertSame(
            [],
            $this->subject->getRequiredColumns(),
        );
    }

    #[Test]
    public function getRequiredColumnsFiltersOutColumnsThatDoNotExistInTheDatabase(): void
    {
        $this->configureRequiredColumnsConfiguration('creator, copyright ,this_column_does_not_exist');
        $this->makeColumnsExistOnSysFileMetadata(['creator', 'copyright']);

        self::assertSame(
            ['creator', 'copyright'],
            $this->subject->getRequiredColumns(),
        );
    }

    #[Test]
    public function getRequiredColumnsResultIsCachedAfterFirstCall(): void
    {
        // Dedicated, strictly-counted mocks: isValidColumn() checks "sys_file" first and
        // falls back to "sys_file_metadata", so a single required column causes exactly
        // one introspectTable() call per table. If the result were not cached, calling
        // getRequiredColumns() twice would double that to 4 - so exactly(2) proves caching.
        $sysFileTableMock = $this->createMock(Table::class);
        $sysFileTableMock->method('hasColumn')->willReturn(false);

        $sysFileMetadataTableMock = $this->createMock(Table::class);
        $sysFileMetadataTableMock->method('hasColumn')->willReturn(true);

        $schemaManagerMock = $this->createMock(AbstractSchemaManager::class);
        $schemaManagerMock->method('tablesExist')->willReturn(true);
        $schemaManagerMock->expects(self::exactly(2))
            ->method('introspectTable')
            ->willReturnCallback(
                fn(string $table) => $table === 'sys_file_metadata' ? $sysFileMetadataTableMock : $sysFileTableMock,
            );

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('createSchemaManager')->willReturn($schemaManagerMock);

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->willReturn($connectionMock);

        $this->configureRequiredColumnsConfiguration('creator');
        $subject = new FileMetaDataValidationService($this->extensionConfigurationMock, $connectionPoolMock);

        self::assertSame(['creator'], $subject->getRequiredColumns());
        self::assertSame(['creator'], $subject->getRequiredColumns());
    }

    #[Test]
    public function getMissingColumnsReturnsEmptyArrayWhenNoColumnsAreRequired(): void
    {
        $this->configureRequiredColumnsConfiguration('');
        $file = $this->createImageFile(['creator' => 'John Doe']);

        self::assertSame(
            [],
            $this->subject->getMissingColumns($file),
        );
    }

    #[Test]
    public function getMissingColumnsReturnsEmptyArrayForNonImageFile(): void
    {
        $this->configureRequiredColumnsConfiguration('creator,copyright');
        $this->makeColumnsExistOnSysFileMetadata(['creator', 'copyright']);

        $file = new File(
            [
                'identifier' => '/foo.pdf',
                'name' => 'foo.pdf',
                'type' => FileType::TEXT->value,
            ],
            $this->storageMock,
            [],
        );

        self::assertSame(
            [],
            $this->subject->getMissingColumns($file),
        );
    }

    #[Test]
    public function getMissingColumnsDetectsColumnThatIsNotSetAtAll(): void
    {
        $this->configureRequiredColumnsConfiguration('creator,copyright');
        $this->makeColumnsExistOnSysFileMetadata(['creator', 'copyright']);

        $file = $this->createImageFile(['creator' => 'John Doe']);

        self::assertSame(
            ['copyright'],
            $this->subject->getMissingColumns($file),
        );
    }

    #[Test]
    public function getMissingColumnsDetectsEmptyStringAsMissing(): void
    {
        $this->configureRequiredColumnsConfiguration('creator');
        $this->makeColumnsExistOnSysFileMetadata(['creator']);

        $file = $this->createImageFile(['creator' => '']);

        self::assertSame(
            ['creator'],
            $this->subject->getMissingColumns($file),
        );
    }

    #[Test]
    public function getMissingColumnsDetectsWhitespaceOnlyStringAsMissing(): void
    {
        $this->configureRequiredColumnsConfiguration('creator');
        $this->makeColumnsExistOnSysFileMetadata(['creator']);

        $file = $this->createImageFile(['creator' => '   ']);

        self::assertSame(
            ['creator'],
            $this->subject->getMissingColumns($file),
        );
    }

    #[Test]
    public function getMissingColumnsDetectsNullAsMissing(): void
    {
        $this->configureRequiredColumnsConfiguration('creator');
        $this->makeColumnsExistOnSysFileMetadata(['creator']);

        $file = $this->createImageFile(['creator' => null]);

        self::assertSame(
            ['creator'],
            $this->subject->getMissingColumns($file),
        );
    }

    #[Test]
    public function getMissingColumnsReturnsEmptyArrayWhenAllRequiredColumnsAreFilled(): void
    {
        $this->configureRequiredColumnsConfiguration('creator,copyright');
        $this->makeColumnsExistOnSysFileMetadata(['creator', 'copyright']);

        $file = $this->createImageFile([
            'creator' => 'John Doe',
            'copyright' => '(c) City of Pforzheim',
        ]);

        self::assertSame(
            [],
            $this->subject->getMissingColumns($file),
        );
    }

    #[Test]
    public function hasValidMetaDataReturnsFalseWhenColumnsAreMissing(): void
    {
        $this->configureRequiredColumnsConfiguration('creator');
        $this->makeColumnsExistOnSysFileMetadata(['creator']);

        $file = $this->createImageFile([]);

        self::assertFalse(
            $this->subject->hasValidMetaData($file),
        );
    }

    #[Test]
    public function hasValidMetaDataReturnsTrueWhenNoColumnsAreRequired(): void
    {
        $this->configureRequiredColumnsConfiguration('');

        $file = $this->createImageFile([]);

        self::assertTrue(
            $this->subject->hasValidMetaData($file),
        );
    }

    #[Test]
    public function hasValidMetaDataReturnsTrueWhenAllRequiredColumnsAreFilled(): void
    {
        $this->configureRequiredColumnsConfiguration('creator');
        $this->makeColumnsExistOnSysFileMetadata(['creator']);

        $file = $this->createImageFile(['creator' => 'John Doe']);

        self::assertTrue(
            $this->subject->hasValidMetaData($file),
        );
    }
}
