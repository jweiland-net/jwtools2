<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Tests\Functional\Database\Query;

use JWeiland\Jwtools2\Database\Query\QueryGenerator;
use JWeiland\Jwtools2\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

class QueryGeneratorTest extends AbstractFunctionalTestCase
{
    private QueryGenerator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');

        $this->subject = $this->get(QueryGenerator::class);
    }

    #[Test]
    public function getTreeListReturnsAllSubPagesIncludingStartPage(): void
    {
        self::assertSame(
            '1,2,4,3',
            $this->subject->getTreeList(1, 99),
        );
    }

    #[Test]
    public function getTreeListRespectsDepth(): void
    {
        self::assertSame(
            '1,2,3',
            $this->subject->getTreeList(1, 1),
        );
    }

    #[Test]
    public function getTreeListWithBeginSkipsStartPage(): void
    {
        self::assertSame(
            '2,4,3',
            $this->subject->getTreeList(1, 99, 1),
        );
    }

    #[Test]
    public function getTreeListWithZeroDepthReturnsStartPageOnly(): void
    {
        self::assertSame(
            '1',
            $this->subject->getTreeList(1, 0),
        );
    }

    #[Test]
    public function stripLogicalOperatorPrefixRemovesLeadingOperators(): void
    {
        self::assertSame(
            'pages.hidden = 0',
            QueryGenerator::stripLogicalOperatorPrefix(' AND OR pages.hidden = 0'),
        );
    }
}
