<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Functional\Service;

use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use JWeiland\Jwtools2\Service\SolrService;
use JWeiland\Jwtools2\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SolrServiceTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_solr_indexqueue_item.csv');
    }

    private function getSubject(): SolrService
    {
        // Same way as it is created in IndexQueueWorkerTask and the Solr controllers
        return GeneralUtility::makeInstance(SolrService::class);
    }

    private function createSiteMock(int $rootPageId): Site
    {
        $siteMock = $this->createMock(Site::class);
        $siteMock
            ->method('getRootPageId')
            ->willReturn($rootPageId);

        return $siteMock;
    }

    /**
     * @return int[]
     */
    private function getRemainingIndexQueueItemUids(): array
    {
        $uids = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_solr_indexqueue_item')
            ->select(['uid'], 'tx_solr_indexqueue_item', [], [], ['uid' => 'ASC'])
            ->fetchFirstColumn();

        return array_map(intval(...), $uids);
    }

    #[Test]
    public function getStatisticCountsItemsOfAllSites(): void
    {
        $statistic = $this->getSubject()->getStatistic();

        self::assertSame(3, $statistic->getSuccessCount());
        self::assertSame(1, $statistic->getPendingCount());
        self::assertSame(1, $statistic->getFailedCount());
        self::assertSame(5, $statistic->getTotalCount());
        self::assertSame(60.0, $statistic->getSuccessPercentage());
    }

    #[Test]
    public function getStatisticWithEmptyQueueReturnsZeroPercent(): void
    {
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_solr_indexqueue_item')
            ->truncate('tx_solr_indexqueue_item');

        self::assertSame(
            0.0,
            $this->getSubject()->getStatistic()->getSuccessPercentage(),
        );
    }

    #[Test]
    public function clearItemTableByTypeRemovesItemsOfTypeOfGivenSiteOnly(): void
    {
        $this->getSubject()->clearItemTableByType($this->createSiteMock(1), 'news');

        self::assertSame(
            [1, 2, 5],
            $this->getRemainingIndexQueueItemUids(),
        );
    }

    #[Test]
    public function clearItemTableByTypeWithoutTypeRemovesAllItemsOfGivenSite(): void
    {
        $this->getSubject()->clearItemTableByType($this->createSiteMock(1));

        self::assertSame(
            [5],
            $this->getRemainingIndexQueueItemUids(),
        );
    }

    #[Test]
    public function clearIndexByTypeWithClearItemRemovesItems(): void
    {
        $this->getSubject()->clearIndexByType($this->createSiteMock(2), ['clearItem'], 'pages');

        self::assertSame(
            [1, 2, 3, 4],
            $this->getRemainingIndexQueueItemUids(),
        );
    }

    #[Test]
    public function clearFileTableByTypeDoesNothingWithoutSolrfal(): void
    {
        // EXT:solrfal is not loaded, so the (non-existing) file queue table must not be touched
        $this->getSubject()->clearFileTableByType($this->createSiteMock(1), 'pages');

        self::assertSame(
            [1, 2, 3, 4, 5],
            $this->getRemainingIndexQueueItemUids(),
        );
    }
}
