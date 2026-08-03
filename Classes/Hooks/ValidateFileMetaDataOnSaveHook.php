<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

readonly class ValidateFileMetaDataOnSaveHook
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private FileMetaDataValidationService $fileMetaDataValidationService,
        private ResourceFactory $resourceFactory,
        private ConnectionPool $connectionPool,
    ) {}

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        foreach ($dataHandler->datamap as $table => $records) {
            foreach (array_keys($records) as $submittedId) {
                $uid = $this->resolveRealUid((string)$submittedId, $dataHandler);
                if ($uid === null) {
                    continue;
                }

                $this->validateFileReferences($table, $uid, $dataHandler);
            }
        }
    }

    protected function resolveRealUid(string $submittedId, DataHandler $dataHandler): ?int
    {
        if (MathUtility::canBeInterpretedAsInteger($submittedId)) {
            return (int)$submittedId;
        }

        $realId = $dataHandler->substNEWwithIDs[$submittedId] ?? null;

        return $realId !== null ? (int)$realId : null;
    }

    protected function validateFileReferences(string $table, int $uid, DataHandler $dataHandler): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $references = $queryBuilder
            ->select('uid', 'uid_local', 'fieldname')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($references as $reference) {
            $file = $this->getFile((int)$reference['uid_local']);
            if (!$file instanceof File || $this->fileMetaDataValidationService->hasValidMetaData($file)) {
                continue;
            }

            $this->disableFileReference((int)$reference['uid']);

            $dataHandler->log(
                $table,
                $uid,
                SystemLogDatabaseAction::UPDATE,
                null,
                SystemLogErrorClassification::USER_ERROR,
                $this->translate('dataHandler.log.invalidFileMetaData'),
                null,
                ['field' => $reference['fieldname'], 'file' => $file->getName()],
            );
        }
    }

    protected function disableFileReference(int $referenceUid): void
    {
        $this->connectionPool
            ->getConnectionForTable('sys_file_reference')
            ->update(
                'sys_file_reference',
                ['hidden' => 1],
                ['uid' => $referenceUid],
            );
    }

    protected function getFile(int $fileUid): ?File
    {
        try {
            return $this->resourceFactory->getFileObject($fileUid);
        } catch (FileDoesNotExistException) {
            return null;
        }
    }

    protected function isEnabled(): bool
    {
        try {
            return (bool)$this->extensionConfiguration->get(
                'jwtools2',
                'typo3PreventSavingContentWithInvalidFileMetaData',
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return false;
        }
    }

    protected function translate(string $key): string
    {
        return LocalizationUtility::translate(
            'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:' . $key,
        ) ?? $key;
    }
}
