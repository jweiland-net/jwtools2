<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use JWeiland\Jwtools2\Database\Query\QueryGenerator;
use JWeiland\Jwtools2\Traits\RequestArgumentsTrait;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Tree\Event\ModifyTreeDataEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reduce category tree to categories of PIDs within current page tree
 */
class ReduceCategoryTreeToPageTree
{
    use RequestArgumentsTrait;

    protected string $categoryTableName = 'sys_category';

    protected int $pageUid = 0;

    protected string $listOfCategoryUids = '';

    protected ExtensionConfiguration $extensionConfiguration;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function __invoke(ModifyTreeDataEvent $event): void
    {
        try {
            if (
                (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend())
                && $this->extensionConfiguration->get('jwtools2', 'reduceCategoriesToPageTree') === '1'
                && !$this->getBackendUserAuthentication()->isAdmin()
                && $event->getProvider()->getTableName() === $this->categoryTableName
            ) {
                $this->removePageTreeForeignCategories($event->getTreeData());
            }
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException $exception) {
        }
    }

    /**
     * Remove all categories which are not in current page tree
     */
    protected function removePageTreeForeignCategories(TreeNode $treeNode): void
    {
        if (
            $treeNode->getChildNodes() instanceof TreeNodeCollection
            && $treeNode->getChildNodes()->count()
        ) {
            $backupChildNodes = clone $treeNode->getChildNodes();

            /** @var TreeNode $childNode */
            foreach ($backupChildNodes as $key => $childNode) {
                if (!GeneralUtility::inList(
                    $this->getListOfAllowedCategoryUids($this->getPageUid()),
                    $childNode->getId()
                )) {
                    unset($treeNode->getChildNodes()[$key]);
                } elseif (
                    $childNode->getChildNodes() instanceof TreeNodeCollection
                    && $childNode->getChildNodes()->count()
                ) {
                    $this->removePageTreeForeignCategories($treeNode->getChildNodes()[$key]);
                }
            }
        }
    }

    /**
     * Get current page UID
     */
    protected function getPageUid(): int
    {
        if (!isset($this->pageUid) || $this->pageUid === 0) {
            $command = $this->getGetArguments()['command'] ?? '';
            if ($command === 'edit') {
                $record = BackendUtility::getRecordWSOL(
                    $this->getGetArguments()['tableName'] ?? '',
                    (int)$this->getGetArguments()['uid'],
                    'pid'
                );
                if (empty($record)) {
                    $pid = 0;
                } else {
                    $pid = (int)$record['pid'];
                }
            } else {
                // in case of command==new given uid is pid of current page
                $pid = (int)$this->getGetArguments()['uid'];
            }

            $this->pageUid = $pid;
        }

        return $this->pageUid;
    }

    /**
     * Get comma separated list of category UIDs
     */
    protected function getListOfAllowedCategoryUids(int $pageUid): string
    {
        if ($this->listOfCategoryUids === '') {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
            $categories = $queryBuilder
                ->select('uid')
                ->from('sys_category')
                ->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter(
                            $this->getPagesOfCurrentRootPage($pageUid),
                            ArrayParameterType::INTEGER
                        )
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();

            // If there are no categories we have to make sure $this->listOfCategoryUids will be filled with 0,
            // to prevent that this method will be called again and again
            if ($categories === []) {
                $categories = [
                    0 => [
                        'uid' => 0,
                    ],
                ];
            }

            $listOfCategories = [];
            foreach ($categories as $category) {
                $listOfCategories[] = $category['uid'];
            }

            $this->listOfCategoryUids = implode(',', $listOfCategories);
        }

        return $this->listOfCategoryUids;
    }

    /**
     * Get all page UIDs of current page tree
     */
    public function getPagesOfCurrentRootPage(int $pageUid): array
    {
        $queryGenerator = $this->getQueryGenerator();

        return GeneralUtility::trimExplode(
            ',',
            (string)$queryGenerator->getTreeList(
                $this->getRootPageUid($pageUid),
                10,
                0,
                '1=1'
            )
        );
    }

    /**
     * Slide up through RootLine and return UID of page which is configured with is_siteroot
     */
    protected function getRootPageUid(int $uid): int
    {
        $rootLine = BackendUtility::BEgetRootLine($uid);
        $rootPage = reset($rootLine);
        foreach ($rootLine as $page) {
            if ($page['is_siteroot']) {
                $rootPage = $page;
                break;
            }
        }

        return (int)$rootPage['uid'];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getQueryGenerator(): QueryGenerator
    {
        return GeneralUtility::makeInstance(QueryGenerator::class);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
