<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Service;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class FileReferenceResolverService
{
    public function __construct(private ConnectionPool $connectionPool) {}

    /**
     * Direct, FAL-specific lookup: all sys_file_reference rows pointing at the given file.
     *
     * @return array<int, array{uid: int, tablenames: string, uid_foreign: int, fieldname: string, hidden: bool}>
     * @throws Exception
     */
    public function getFileReferencesForFile(int $fileUid, bool $includeHidden = true): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->select('uid', 'tablenames', 'uid_foreign', 'fieldname', 'hidden')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT),
                ),
            );

        if (!$includeHidden) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            );
        }

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'uid' => (int)$row['uid'],
                'tablenames' => (string)$row['tablenames'],
                'uid_foreign' => (int)$row['uid_foreign'],
                'fieldname' => (string)$row['fieldname'],
                'hidden' => (bool)$row['hidden'],
            ],
            $rows,
        );
    }

    /**
     * Broader lookup via sys_refindex, resolving "sys_file_reference" bridge rows to their
     * owning record. Used for reporting (the backend module), where we want to show every
     * table/field a file is used in, not just decide whether to hide something.
     *
     * @return array<int, array{tablename: string, recuid: int, field: string}>
     * @throws Exception
     */
    public function getReferenceIndexRowsForFile(int $fileUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter('sys_file', Connection::PARAM_STR),
                ),
                $queryBuilder->expr()->eq(
                    'ref_uid',
                    $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $references = [];
        foreach ($rows as $row) {
            if ($row['tablename'] === 'sys_file_reference') {
                $resolved = $this->resolveFileReferenceToOwningRecord((int)$row['recuid']);
                if ($resolved === null) {
                    // Orphaned sys_file_reference row (owning record removed without cleanup) - skip it.
                    continue;
                }

                $references[] = $resolved;
                continue;
            }

            $references[] = [
                'tablename' => (string)$row['tablename'],
                'recuid' => (int)$row['recuid'],
                'field' => (string)$row['field'],
            ];
        }

        return $references;
    }

    /**
     * @return array{tablename: string, recuid: int, field: string}|null
     * @throws Exception
     */
    protected function resolveFileReferenceToOwningRecord(int $referenceUid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $fileReference = $queryBuilder
            ->select('tablenames', 'uid_foreign', 'fieldname')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($referenceUid, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($fileReference === false) {
            return null;
        }

        return [
            'tablename' => (string)$fileReference['tablenames'],
            'recuid' => (int)$fileReference['uid_foreign'],
            'field' => (string)$fileReference['fieldname'],
        ];
    }
}
