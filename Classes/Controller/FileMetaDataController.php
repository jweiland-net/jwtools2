<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller;

use Doctrine\DBAL\Exception;
use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use JWeiland\Jwtools2\Service\FileReferenceResolverService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileMetaDataController extends AbstractController
{
    private const ITEMS_PER_PAGE = 50;

    private const CANDIDATE_LIMIT = 1000;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        private readonly FileMetaDataValidationService $fileMetaDataValidationService,
        private readonly FileReferenceResolverService $fileReferenceResolverService,
        private readonly ConnectionPool $connectionPool,
        private readonly ResourceFactory $resourceFactory,
    ) {
        parent::__construct($moduleTemplateFactory);
    }

    /**
     * @param array<string, string> $filters
     * @throws Exception
     */
    public function listAction(array $filters = []): ResponseInterface
    {
        $filters = $this->getFilters($filters);
        $currentPage = max(1, (int)($filters['page'] ?? 1));
        $requiresPhpFiltering = $filters['status'] !== 'all'
            || $filters['missingColumn'] !== ''
            || $filters['referenceScope'] !== 'all';

        $queryBuilder = $this->buildBaseQueryBuilder($filters);
        $truncated = false;

        if ($requiresPhpFiltering) {
            $candidateUids = (clone $queryBuilder)
                ->select('sys_file.uid')
                ->orderBy('sys_file.name')
                ->setMaxResults(self::CANDIDATE_LIMIT)
                ->executeQuery()
                ->fetchFirstColumn();

            $truncated = count($candidateUids) >= self::CANDIDATE_LIMIT;

            $allRows = $this->buildRows(array_map(intval(...), $candidateUids), $filters);
            $totalCount = count($allRows);
            $pageRows = array_slice($allRows, ($currentPage - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
        } else {
            $totalCount = (int)(clone $queryBuilder)->count('sys_file.uid')->executeQuery()->fetchOne();

            $pageUids = (clone $queryBuilder)
                ->select('sys_file.uid')
                ->orderBy('sys_file.name')
                ->setFirstResult(($currentPage - 1) * self::ITEMS_PER_PAGE)
                ->setMaxResults(self::ITEMS_PER_PAGE)
                ->executeQuery()
                ->fetchFirstColumn();

            $pageRows = $this->buildRows(array_map(intval(...), $pageUids), $filters);
        }

        $requiredColumns = $this->fileMetaDataValidationService->getRequiredColumns();
        $extensionOptions = $this->getExtensionOptions();

        $this->moduleTemplate->assignMultiple([
            'rows' => $pageRows,
            'filters' => $filters,
            'requiredColumns' => $requiredColumns,
            'requiredColumnOptions' => array_combine($requiredColumns, $requiredColumns),
            'storageOptions' => $this->getStorageOptions(),
            'extensionOptions' => array_combine($extensionOptions, $extensionOptions),
            'currentPage' => $currentPage,
            'totalPages' => $totalPages = max(1, (int)ceil($totalCount / self::ITEMS_PER_PAGE)),
            'totalCount' => $totalCount,
            'truncated' => $truncated,
            'candidateLimit' => self::CANDIDATE_LIMIT,
            'previousPageFilters' => [...$filters, 'page' => (string)max(1, $currentPage - 1)],
            'nextPageFilters' => [...$filters, 'page' => (string)min($totalPages, $currentPage + 1)],
        ]);

        return $this->moduleTemplate->renderResponse('FileMetaData/List');
    }

    /**
     * @param array<int, int> $fileUids
     * @return array<int, array<string, mixed>>
     */
    protected function buildRows(array $fileUids, array $filters): array
    {
        $requiredColumns = $this->fileMetaDataValidationService->getRequiredColumns();
        $rows = [];

        foreach ($fileUids as $fileUid) {
            $file = $this->getFile($fileUid);
            if (!$file instanceof File) {
                continue;
            }

            $missingColumns = $this->fileMetaDataValidationService->getMissingColumns($file);
            $status = $this->getStatus($missingColumns, $requiredColumns);

            if ($filters['status'] !== 'all' && $filters['status'] !== $status) {
                continue;
            }

            if ($filters['missingColumn'] !== '' && !in_array($filters['missingColumn'], $missingColumns, true)) {
                continue;
            }

            $references = $this->fileReferenceResolverService->getReferenceIndexRowsForFile($fileUid);
            $referenceScope = $this->getReferenceScope($references);

            if ($filters['referenceScope'] !== 'all' && $filters['referenceScope'] !== $referenceScope) {
                continue;
            }

            $rows[] = [
                'file' => $file,
                'status' => $status,
                'missingColumns' => $missingColumns,
                'references' => $references,
                'referenceScope' => $referenceScope,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, string> $missingColumns
     * @param array<int, string> $requiredColumns
     */
    protected function getStatus(array $missingColumns, array $requiredColumns): string
    {
        if ($missingColumns === []) {
            return 'valid';
        }

        if ($requiredColumns !== [] && count($missingColumns) === count($requiredColumns)) {
            return 'missing';
        }

        return 'partial';
    }

    protected function getReferenceScope(array $references): string
    {
        if ($references === []) {
            return 'orphaned';
        }

        $hasContentElement = false;
        $hasOther = false;

        foreach ($references as $reference) {
            if ($reference['tablename'] === 'tt_content') {
                $hasContentElement = true;
            } else {
                $hasOther = true;
            }
        }

        return match (true) {
            $hasContentElement && $hasOther => 'both',
            $hasContentElement => 'tt_content',
            default => 'other',
        };
    }

    protected function buildBaseQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_file.type',
                    $queryBuilder->createNamedParameter(FileType::IMAGE->value, Connection::PARAM_INT),
                ),
            );

        if ($filters['storage'] !== '') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_file.storage',
                    $queryBuilder->createNamedParameter((int)$filters['storage'], Connection::PARAM_INT),
                ),
            );
        }

        if ($filters['extension'] !== '') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_file.extension',
                    $queryBuilder->createNamedParameter($filters['extension']),
                ),
            );
        }

        if ($filters['search'] !== '') {
            $connection = $this->connectionPool->getConnectionForTable('sys_file');
            $queryBuilder->andWhere(
                $queryBuilder->expr()->like(
                    'sys_file.name',
                    $queryBuilder->createNamedParameter(
                        '%' . $connection->escapeLikeWildcards($filters['search']) . '%',
                    ),
                ),
            );
        }

        return $queryBuilder;
    }

    /**
     * @param array<string, string> $requestFilters
     * @return array<string, string>
     */
    protected function getFilters(array $requestFilters): array
    {
        return [
            'status' => (string)($requestFilters['status'] ?? 'all'),
            'missingColumn' => (string)($requestFilters['missingColumn'] ?? ''),
            'referenceScope' => (string)($requestFilters['referenceScope'] ?? 'all'),
            'storage' => (string)($requestFilters['storage'] ?? ''),
            'extension' => (string)($requestFilters['extension'] ?? ''),
            'search' => trim((string)($requestFilters['search'] ?? '')),
            'page' => (string)($requestFilters['page'] ?? '1'),
        ];
    }

    /**
     * @return array<int, array{uid: int, name: string}>
     * @throws Exception
     */
    protected function getStorageOptions(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_storage');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->select('uid', 'name')
            ->from('sys_file_storage')
            ->orderBy('name')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => ['uid' => (int)$row['uid'], 'name' => (string)$row['name']],
            $rows,
        );
    }

    /**
     * @return array<int, string>
     * @throws Exception
     */
    protected function getExtensionOptions(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->selectLiteral('DISTINCT ' . $queryBuilder->quoteIdentifier('extension'))
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_file.type',
                    $queryBuilder->createNamedParameter(\TYPO3\CMS\Core\Resource\FileType::IMAGE->value, Connection::PARAM_INT),
                ),
            )
            ->orderBy('extension')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_values(array_filter(array_map(strval(...), $rows)));
    }

    protected function getFile(int $fileUid): ?File
    {
        try {
            return $this->resourceFactory->getFileObject($fileUid);
        } catch (FileDoesNotExistException) {
            return null;
        }
    }
}
