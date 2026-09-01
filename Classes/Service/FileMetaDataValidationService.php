<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Service;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileMetaDataValidationService
{
    private ?array $requiredColumnsCache = null;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getRequiredColumns(): array
    {
        if ($this->requiredColumnsCache === null) {
            $validColumns = [];
            foreach ($this->getRequiredColumnsFromExtensionConfiguration() as $column) {
                if ($this->isValidColumn($column)) {
                    $validColumns[] = $column;
                }
            }

            $this->requiredColumnsCache = $validColumns;
        }

        return $this->requiredColumnsCache;
    }

    public function getMissingColumns(File $file): array
    {
        if ($file->getType() !== FileType::IMAGE->value) {
            return [];
        }

        $missingColumns = [];
        // Most enforceable columns (e.g. "creator", "copyright") live exclusively in
        // sys_file_metadata and are never part of File::getProperties() (which only reflects
        // the sys_file row). Merge in the metadata aspect so both kinds of columns are covered;
        // metadata wins on overlap since it is the authoritative source for these fields.
        $properties = array_merge($file->getProperties(), $file->getMetaData()->get());

        foreach ($this->getRequiredColumns() as $requiredColumn) {
            // Do not use isset() as "null" values have to be tested, too.
            if (!array_key_exists($requiredColumn, $properties)) {
                $missingColumns[] = $requiredColumn;
                continue;
            }

            $value = $properties[$requiredColumn];
            $value = is_string($value) ? trim($value) : $value;

            if ($value === null || $value === '') {
                $missingColumns[] = $requiredColumn;
            }
        }

        return $missingColumns;
    }

    public function hasValidMetaData(File $file): bool
    {
        return $this->getMissingColumns($file) === [];
    }

    protected function getRequiredColumnsFromExtensionConfiguration(): array
    {
        try {
            return GeneralUtility::trimExplode(
                ',',
                (string)$this->extensionConfiguration->get('jwtools2', 'typo3RequiredColumnsForFiles'),
                true,
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return [];
        }
    }

    protected function isValidColumn(string $column, string $table = 'sys_file'): bool
    {
        $columnExists = false;
        $connection = $this->getConnectionPool()->getConnectionForTable($table);
        $schemaManager = $connection->createSchemaManager();
        if ($schemaManager->tablesExist([$table])) {
            $columnExists = $schemaManager->introspectTable($table)->hasColumn($column);
            if ($columnExists === false && $table !== 'sys_file_metadata') {
                $columnExists = $this->isValidColumn($column, 'sys_file_metadata');
            }
        }

        return $columnExists;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return $this->connectionPool;
    }
}
