<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use TYPO3\CMS\Backend\ElementBrowser\Event\IsFileSelectableEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Reduce category tree to categories of PIDs within current page tree
 */
final class IsFileSelectableEventListener
{
    public function __construct(protected readonly ExtensionConfiguration $extensionConfiguration) {}

    public function __invoke(IsFileSelectableEvent $event): void
    {
        if ($requiredColumns = $this->getRequiredColumnsFromExtensionConfiguration()) {
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $message = $this->getFlashMessageDescription($requiredColumns);

            if (!$this->checkMessageExists($flashMessageQueue, $message)) {
                $this->addFlashMessage($flashMessageQueue, $message);
            }

            if (!$event->getFile() instanceof File) {
                // Do not process folders or processed files
                return;
            }

            if ($event->getFile()->getType() !== 2) {
                // Process only images
                return;
            }

            foreach ($this->getRequiredColumnsForFileMetaData() as $requiredColumn) {
                $properties = $event->getFile()->getProperties();

                // Do not use isset() as "null" values have to be tested, too.
                if (!array_key_exists($requiredColumn, $properties)) {
                    $event->denyFileSelection();
                }

                $value = is_string($properties[$requiredColumn])
                    ? trim($properties[$requiredColumn])
                    : $properties[$requiredColumn];

                if (!isset($value) || trim($value) === null || trim($value) === '') {
                    $event->denyFileSelection();
                }
            }
        }
    }

    protected function addFlashMessage(FlashMessageQueue $flashMessageQueue, string $message): void
    {
        $flashMessageQueue->addMessage(
            GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                LocalizationUtility::translate(
                    'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.title',
                ),
                ContextualFeedbackSeverity::INFO,
            ),
        );
    }

    protected function getFlashMessageDescription(array $requiredColumns): string
    {
        return LocalizationUtility::translate(
            'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.description',
            null,
            [
                implode(
                    ', ',
                    $this->getTranslatedColumnNames(
                        $requiredColumns,
                    ),
                ),
            ],
        );
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
                    true,
                );
            } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException $exception) {
            }
        }

        return $requiredColumns;
    }

    protected function isValidColumn(string $column, string $table = 'sys_file'): bool
    {
        $columnExists = false;
        $connection = $this->getConnectionPool()->getConnectionForTable($table);
        $schemaManager = $connection->createSchemaManager();
        if (
            $schemaManager->tablesExist($table)
        ) {
            $columnExists = $schemaManager->introspectTable($table)->hasColumn($column);
            if ($columnExists === false && $table !== 'sys_file_metadata') {
                $columnExists = $this->isValidColumn($column, 'sys_file_metadata');
            }
        }

        return $columnExists;
    }

    protected function checkMessageExists(FlashMessageQueue $flashMessageQueue, string $message): bool
    {
        $messageExists = false;
        foreach ($flashMessageQueue as $flashMessage) {
            if ($flashMessage->getMessage() === $message) {
                $messageExists = true;
                break;
            }
        }

        return $messageExists;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
