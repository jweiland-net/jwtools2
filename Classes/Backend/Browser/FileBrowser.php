<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend\Browser;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use JWeiland\Jwtools2\Traits\RequestArgumentsTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\ElementBrowserController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Modified version of TYPO3's ElementBrowserController.
 * We have modified the templates to allow showing the upload form on top of the file/folder list
 */
class FileBrowser extends \TYPO3\CMS\Backend\ElementBrowser\FileBrowser
{
    use RequestArgumentsTrait;

    /**
     * Only load additional JavaScript, if in file or folder context
     */
    protected function initialize(): void
    {
        // We have to prevent, that __construct() of AbstractElementBrowser will call initialize()
        // of TYPO3's FileBrowser where additional JavaScript will be loaded. That would break the selection and
        // transfer of chosen records into the parent form. Error: file_undefined insteadof file_123.
        // JS module "TYPO3/CMS/Recordlist/BrowseFiles" should NOT be loaded, if we are not in file or folder context!!!
        if (in_array($this->getGPValue('mode'), ['file', 'folder'])) {
            parent::initialize();
        }
    }

    /**
     * Return "true". Else complete rendering will not start.
     */
    public function isValid(string $mode, ElementBrowserController $elementBrowserController): bool
    {
        if ($mode === 'file' || $mode === 'folder') {
            if ($this->getRequiredColumnsFromExtensionConfiguration()) {
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier(
                    FlashMessageQueue::NOTIFICATION_QUEUE
                );
                $flashMessageQueue->enqueue(
                    GeneralUtility::makeInstance(
                        FlashMessage::class,
                        LocalizationUtility::translate(
                            'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.description',
                            null,
                            [
                                implode(
                                    ', ',
                                    $this->getTranslatedColumnNames(
                                        $this->getRequiredColumnsFromExtensionConfiguration()
                                    )
                                )
                            ]
                        ),
                        LocalizationUtility::translate(
                            'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.title'
                        ),
                        ContextualFeedbackSeverity::INFO
                    )
                );
            }

            return true;
        }

        return false;
    }

    protected function getTranslatedColumnNames(array $requiredColumns): array
    {
        if (!$this->getLanguageService() instanceof LanguageService) {
            return $requiredColumns;
        }

        foreach ($requiredColumns as $key => $requiredColumn) {
            $label = BackendUtility::getItemLabel('sys_file', $requiredColumn);
            if ($label === '' || $label === null) {
                $label = BackendUtility::getItemLabel('sys_file_metadata', $requiredColumn);
            }

            if ($label === '' || $label === null) {
                continue;
            }

            $translatedLabel = $this->getLanguageService()->sL($label);
            if ($translatedLabel === '' || $translatedLabel === null) {
                continue;
            }

            $requiredColumns[$key] = $translatedLabel;
        }

        return $requiredColumns;
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
        if (is_callable([$this, 'setRequest'])) {
            $this->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        if (!$this->isShowUploadFieldsInTopOfEB()) {
            return parent::render();
        }

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if (version_compare($typo3Version->getBranch(), '11.5', '=')) {
            // <h4> is the first header. It's before the three following forms: searchForm, uploadForm and createForm
            // Do not remove empty values. In same cases $top contains just spaces/tabs.
            [$top, $content] = GeneralUtility::trimExplode('<h4 class="text-truncate', parent::render(), false, 2);
            $pattern = '/(?P<header><h4 class="text-truncate.*?<\/h4>)(?P<searchForm><div class="mt-4 mb-4"><form.*?<\/form>\v<\/div>)(?P<fileList><div id="filelist">.*<\/table> <\/div>)(?P<uploadForm><form.*?<\/form>)(?P<createForm><form.*?<\/form>)/usm';
            if (preg_match($pattern, '<h4 class="text-truncate' . $content, $matches)) {
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

            return $top . '<h4 class="text-truncate' . $content;
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
