<?php
declare(strict_types=1);
namespace JWeiland\Jwtools2\Tca;

/*
 * This file is part of the jwtools2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reduce category tree to categories of PIDs within current page tree
 */
class ReduceCategoryTreeToPageTree
{
    /**
     * @var string
     */
    protected $categoryTableName = 'sys_category';

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUserAuthentication;

    /**
     * @var int
     */
    protected $pageUid = 0;

    /**
     * @var string
     */
    protected $listOfCategoryUids = '';

    /**
     * @param BackendUserAuthentication|null $backendUserAuthentication
     */
    public function __construct($backendUserAuthentication = null)
    {
        $this->backendUserAuthentication = $backendUserAuthentication ?: $GLOBALS['BE_USER'];
    }

    /**
     * The slot for the signal in DatabaseTreeDataProvider.
     *
     * @param DatabaseTreeDataProvider $dataProvider
     * @param TreeNode $treeData
     */
    public function reduceCategoriesToPageTree(DatabaseTreeDataProvider $dataProvider, $treeData) {
        if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE) && !$this->backendUserAuthentication->isAdmin() && $dataProvider->getTableName() === $this->categoryTableName) {
            $this->removePageTreeForeignCategories($treeData);
        }
    }

    /**
     * Remove all categories which are not in current page tree
     *
     * @param TreeNode $treeNode
     */
    protected function removePageTreeForeignCategories(TreeNode $treeNode)
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
                } else {
                    if (
                        $childNode->getChildNodes() instanceof TreeNodeCollection
                        && $childNode->getChildNodes()->count()
                    ) {
                        $this->removePageTreeForeignCategories($treeNode->getChildNodes()[$key]);
                    }
                }
            }
        }
    }

    /**
     * Get current page UID
     *
     * @return int
     */
    protected function getPageUid(): int
    {
        if (empty($this->pageUid)) {
            $command = GeneralUtility::_GET('command');
            if ($command === 'edit') {
                $record = BackendUtility::getRecordWSOL(
                    GeneralUtility::_GET('tableName'),
                    (int)GeneralUtility::_GET('uid'),
                    'pid'
                );
                if (empty($record)) {
                    $pid = 0;
                } else {
                    $pid = (int)$record['pid'];
                }
            } else {
                // in case of command==new given uid is pid of current page
                $pid = (int)GeneralUtility::_GET('uid');
            }

            $this->pageUid = $pid;
        }

        return $this->pageUid;
    }

    /**
     * Get comma separated list of category UIDs
     *
     * @param int $pageUid
     * @return string
     */
    protected function getListOfAllowedCategoryUids(int $pageUid): string
    {
        if (empty($this->listOfCategoryUids)) {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
            $categories = $queryBuilder
                ->select('uid')
                ->from('sys_category')
                ->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter(
                            $this->getPagesOfCurrentRootPage($pageUid),
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                )
                ->execute()
                ->fetchAll();

            if (empty($categories)) {
                return '0';
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
     *
     * @param int $pageUid
     * @return array
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
     *
     * @param int $uid
     * @return int
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

    /**
     * Get QueryGenerator
     *
     * @return QueryGenerator
     */
    protected function getQueryGenerator(): QueryGenerator
    {
        return GeneralUtility::makeInstance(QueryGenerator::class);
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }


}
