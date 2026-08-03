<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use JWeiland\Jwtools2\Service\FileReferenceResolverService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

final readonly class DisableRecordsWithInvalidFileMetaDataEventListener
{
    private const REGISTRY_NAMESPACE = 'jwtools2';

    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private FileMetaDataValidationService $fileMetaDataValidationService,
        private FileReferenceResolverService $fileReferenceResolverService,
        private Registry $registry,
        private FlashMessageService $flashMessageService,
        private CacheManager $cacheManager,
        private ResourceFactory $resourceFactory,
        private ConnectionPool $connectionPool,
    ) {}

    public function onMetaDataUpdated(AfterFileMetaDataUpdatedEvent $event): void
    {
        $this->handle($event->getFileUid());
    }

    public function onMetaDataCreated(AfterFileMetaDataCreatedEvent $event): void
    {
        $this->handle($event->getFileUid());
    }

    private function handle(int $fileUid): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $file = $this->getFile($fileUid);
        if (!$file instanceof File) {
            return;
        }

        if ($this->fileMetaDataValidationService->hasValidMetaData($file)) {
            $this->reEnablePreviouslyDisabledRecords($file);

            return;
        }

        $this->disableReferencingRecords($file);
    }

    private function disableReferencingRecords(File $file): void
    {
        $disabledRecords = [];

        foreach ($this->fileReferenceResolverService->getFileReferencesForFile($file->getUid()) as $reference) {
            $table = $reference['tablenames'];

            if ($this->disableRecord($table, $reference['uid_foreign'], $file)) {
                $disabledRecords[] = ['table' => $table, 'uid' => $reference['uid_foreign']];
            }
        }

        if ($disabledRecords !== []) {
            $this->registry->set(
                self::REGISTRY_NAMESPACE,
                $this->getRegistryKey($file->getUid()),
                $disabledRecords
            );
            $this->flushPageCache();
        }
    }

    private function reEnablePreviouslyDisabledRecords(File $file): void
    {
        $disabledRecords = $this->registry
            ->get(self::REGISTRY_NAMESPACE, $this->getRegistryKey($file->getUid()), []);
        if ($disabledRecords === []) {
            return;
        }

        $reEnabledAny = false;
        foreach ($disabledRecords as $disabledRecord) {
            if ($this->reEnableRecord($disabledRecord['table'], (int)$disabledRecord['uid'], $file)) {
                $reEnabledAny = true;
            }
        }

        $this->registry->remove(self::REGISTRY_NAMESPACE, $this->getRegistryKey($file->getUid()));

        if ($reEnabledAny) {
            $this->flushPageCache();
        }
    }

    private function disableRecord(string $table, int $uid, File $file): bool
    {
        $enableColumn = $this->getDisabledColumn($table);
        if ($enableColumn === null) {
            return false;
        }

        if ($this->isRecordHidden($table, $uid, $enableColumn)) {
            return false;
        }

        $this->connectionPool->getConnectionForTable($table)->update($table, [$enableColumn => 1], ['uid' => $uid]);

        $this->addMessage(
            $table,
            $uid,
            str_replace('{fileName}', $file->getName(), $this->translate('metaData.log.recordDisabled')),
        );

        return true;
    }

    private function reEnableRecord(string $table, int $uid, File $file): bool
    {
        $enableColumn = $this->getDisabledColumn($table);
        if ($enableColumn === null) {
            return false;
        }

        if (!$this->isRecordHidden($table, $uid, $enableColumn)) {
            return false;
        }

        $this->connectionPool->getConnectionForTable($table)->update(
            $table,
            [$enableColumn => 0],
            ['uid' => $uid]
        );

        $this->addMessage(
            $table,
            $uid,
            str_replace('{fileName}', $file->getName(), $this->translate('metaData.log.fileReferenceReenabled')),
        );

        return true;
    }

    private function isRecordHidden(string $table, int $uid, string $enableColumn): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $value = $queryBuilder
            ->select($enableColumn)
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();

        return (bool)$value;
    }

    private function getDisabledColumn(string $table): ?string
    {
        $column = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] ?? null;

        return is_string($column) && $column !== '' ? $column : null;
    }

    private function addMessage(string $table, int $uid, string $message): void
    {
        if (($GLOBALS['BE_USER'] ?? null) instanceof BackendUserAuthentication) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], []);
            $dataHandler->log(
                $table,
                $uid,
                SystemLogDatabaseAction::UPDATE,
                null,
                SystemLogErrorClassification::WARNING,
                $message
            );
        }

        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $flashMessageQueue->addMessage(
            GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                LocalizationUtility::translate(
                    'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.title',
                ) ?? '',
                ContextualFeedbackSeverity::WARNING,
            ),
        );
    }

    private function flushPageCache(): void
    {
        $this->cacheManager->flushCachesInGroup('pages');
    }

    private function getRegistryKey(int $fileUid): string
    {
        return 'disabledRecordsForFile_' . $fileUid;
    }

    private function getFile(int $fileUid): ?File
    {
        try {
            return $this->resourceFactory->getFileObject($fileUid);
        } catch (FileDoesNotExistException) {
            return null;
        }
    }

    private function isEnabled(): bool
    {
        try {
            return (bool)$this->extensionConfiguration->get(
                'jwtools2',
                'typo3DisableRecordsOnInvalidFileMetaData'
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return false;
        }
    }

    private function translate(string $key): string
    {
        return LocalizationUtility::translate(
            'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:' . $key
        ) ?? $key;
    }
}
