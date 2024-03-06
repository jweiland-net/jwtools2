<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use JWeiland\Jwtools2\Query\QueryGenerator;
use JWeiland\Jwtools2\Traits\RequestArgumentsTrait;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
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

    /**
     * @var string
     */
    protected $categoryTableName = 'sys_category';

    /**
     * @var int
     */
    protected $pageUid = 0;

    /**
     * @var string
     */
    protected $listOfCategoryUids = '';

    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

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
     *
     * @param TreeNode $treeNode
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
     *
     * @return int
     */
    protected function getPageUid(): int
    {
        if (empty($this->pageUid)) {
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
     *
     * @param int $pageUid
     * @return string
     * @throws Exception
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
                            ArrayParameterType::INTEGER
                        )
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();

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