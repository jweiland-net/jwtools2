<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend\Browser;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Modified version of TYPO3's ElementBrowserController.
 * We have modified the templates to allow showing the upload form on top of the file/folder list
 */
class FileBrowser extends \TYPO3\CMS\Recordlist\Browser\FileBrowser
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * Return "true". Else complete rendering will not start.
     */
    public function isValid(): bool
    {
        if ($this->getRequiredColumnsFromExtensionConfiguration()) {
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $flashMessageQueue->addMessage(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    LocalizationUtility::translate(
                        'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.description',
                        null,
                        [implode(', ', $this->getRequiredColumnsFromExtensionConfiguration())]
                    ),
                    LocalizationUtility::translate(
                        'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.title'
                    ),
                    AbstractMessage::INFO
                )
            );
        }

        return true;
    }

    protected function fileIsSelectableInFileList(FileInterface $file, array $imgInfo): bool
    {
        // Return true, if there are no columns to skip.
        if ($this->getRequiredColumnsFromExtensionConfiguration() === []) {
            return true;
        }

        // Do not process folders or processed files
        if (!$file instanceof File) {
            return true;
        }

        // Process only images
        if ($file->getType() !== 2) {
            return true;
        }

        foreach ($this->getRequiredColumnsForFileMetaData() as $requiredColumn) {
            $properties = $file->getProperties();

            // Do not use isset() as "null" values have to be tested, too.
            if (!array_key_exists($requiredColumn, $properties)) {
                return false;
            }

            $value = is_string($properties[$requiredColumn])
                ? trim($properties[$requiredColumn])
                : $properties[$requiredColumn];

            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * This method must exist, as this method will be checked by method_exists in ElementBrowserController
     */
    public function render(): string
    {
        if (!$this->isShowUploadFieldsInTopOfEB()) {
            return parent::render();
        }

        // <h3> is the first header. It's before the three following forms: searchForm, uploadForm and createForm
        [$top, $content] = GeneralUtility::trimExplode('<h3>', parent::render(), true, 2);
        $pattern = '/(?P<header><h3>.*?<\/h3>)(?P<searchForm><form.*?<\/form>)(?P<fileList><div id="filelist">.*<\/div>)(?P<uploadForm><form.*?<\/form>)(?P<createForm><form.*?<\/form>)/usm';
        if (preg_match($pattern, '<h3>' . $content, $matches)) {
            return sprintf(
                '%s%s%s%s%s%s',
                $top,
                $matches['uploadForm'],
                $matches['createForm'],
                $matches['header'],
                $matches['searchForm'],
                $matches['fileList']
            );
        }

        return $top . '<h3>' . $content;
    }

    protected function getRequiredColumnsForFileMetaData(): array
    {
        // Cache result, is this method will be called from within a loop
        static $requiredColumns = null;

        if ($requiredColumns === null) {
            $validColumns = [];
            foreach ($this->getRequiredColumnsFromExtensionConfiguration() as $column) {
                if ($this->isValidColumn($column)) {
                    $validColumns[] = $column;
                }
            }
            $requiredColumns = $validColumns;
        }

        return $requiredColumns;
    }

    protected function getRequiredColumnsFromExtensionConfiguration(): array
    {
        static $requiredColumns = null;

        if ($requiredColumns === null) {
            try {
                $requiredColumns = GeneralUtility::trimExplode(
                    ',',
                    $this->extensionConfiguration->get('jwtools2', 'typo3RequiredColumnsForFiles'),
                    true
                );
            } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $exception) {
            }
        }

        return $requiredColumns;
    }

    protected function isShowUploadFieldsInTopOfEB(): bool
    {
        try {
            return (bool)$this->extensionConfiguration->get('jwtools2', 'typo3UploadFieldsInTopOfEB');
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $exception) {
        }

        return false;
    }

    protected function isValidColumn(string $column, string $table = 'sys_file'): bool
    {
        $columnExists = false;
        $connection = $this->getConnectionPool()->getConnectionForTable($table);
        $schemaManager = $connection->getSchemaManager();
        if (
            $schemaManager instanceof AbstractSchemaManager
            && $schemaManager->tablesExist($table)
        ) {
            $columnExists = $schemaManager->listTableDetails($table)->hasColumn($column);
            if ($columnExists === false && $table !== 'sys_file_metadata') {
                $columnExists = $this->isValidColumn($column, 'sys_file_metadata');
            }
        }

        return $columnExists;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
