<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Pager;

use Oro\Bundle\SearchBundle\Datagrid\Extension\Pager\IndexerPager;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class IndexerPagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var IndexerPager */
    private $pager;

    protected function setUp(): void
    {
        $this->pager = new IndexerPager();
    }

    public function testSetQueryIsUsedLater()
    {
        $indexerQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTotalCount'])
            ->getMockForAbstractClass();

        $this->pager->setQuery($indexerQuery);

        $indexerQuery->expects(self::once())
            ->method('getTotalCount');
        $this->pager->getNbResults();
    }

    public function testInit()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Indexer query must be set');

        $this->pager->init();
    }

    public function testGetNbResults()
    {
        $totalCount = 123;

        $indexerQuery = $this->createMock(IndexerQuery::class);
        $indexerQuery->expects($this->once())
            ->method('getTotalCount')
            ->willReturn($totalCount);

        $this->pager->setQuery($indexerQuery);
        $this->assertEquals($totalCount, $this->pager->getNbResults());
    }

    public function maxPerPageDataProvider(): array
    {
        return [
            'fixed'    => [
                '$maxPerPage'  => 12,
                '$maxResults'  => 12,
                '$firstResult' => 0,
            ],
            'infinite' => [
                '$maxPerPage'  => 0,
                '$maxResults'  => Query::INFINITY,
                '$firstResult' => 0,
            ],
        ];
    }

    /**
     * @dataProvider maxPerPageDataProvider
     */
    public function testSetGetMaxPerPage(int $maxPerPage, int $maxResults, int $firstResult)
    {
        $indexerQuery = $this->createMock(IndexerQuery::class);
        $indexerQuery->expects($this->once())
            ->method('setMaxResults')
            ->with($maxResults);
        $indexerQuery->expects($this->once())
            ->method('setFirstResult')
            ->with($firstResult);

        $this->pager->setQuery($indexerQuery);

        $this->pager->setMaxPerPage($maxPerPage);
        $this->assertEquals($maxPerPage, $this->pager->getMaxPerPage());
    }

    public function testSetGetPage()
    {
        $page = 2;
        $firstResult = 10;

        $indexerQuery = $this->createMock(IndexerQuery::class);
        $indexerQuery->expects($this->once())
            ->method('setFirstResult')
            ->with($firstResult);

        $this->pager->setQuery($indexerQuery);

        $this->pager->setPage($page);
        $this->assertEquals($page, $this->pager->getPage());
    }

    public function testGetFirstPreviousNextLastPage()
    {
        $page = 2;
        $maxPerPage = 20;
        $totalCount = 123;
        $firstPage = 1;
        $lastPage = 7;
        $previousPage = 1;
        $nextPage = 3;

        $indexerQuery = $this->createMock(IndexerQuery::class);
        $indexerQuery->expects($this->any())
            ->method('getTotalCount')
            ->willReturn($totalCount);

        $this->pager->setQuery($indexerQuery);
        $this->pager->setPage($page);
        $this->pager->setMaxPerPage($maxPerPage);

        $this->assertEquals($firstPage, $this->pager->getFirstPage());
        $this->assertEquals($lastPage, $this->pager->getLastPage());
        $this->assertEquals($previousPage, $this->pager->getPreviousPage());
        $this->assertEquals($nextPage, $this->pager->getNextPage());
    }

    public function haveToPaginateDataProvider(): array
    {
        return [
            'no_data'      => [
                '$expected'   => false,
                '$page'       => 1,
                '$maxPerPage' => 0,
                '$totalCount' => 0
            ],
            'one_page'     => [
                '$expected'   => false,
                '$page'       => 1,
                '$maxPerPage' => 10,
                '$totalCount' => 5
            ],
            'several_page' => [
                '$expected'   => true,
                '$page'       => 1,
                '$maxPerPage' => 10,
                '$totalCount' => 15
            ],
        ];
    }

    /**
     * @dataProvider haveToPaginateDataProvider
     */
    public function testHaveToPaginate(bool $expected, int $page, int $maxPerPage, int $totalCount)
    {
        $indexerQuery = $this->createMock(IndexerQuery::class);
        $indexerQuery->expects($this->any())
            ->method('getTotalCount')
            ->willReturn($totalCount);

        $this->pager->setQuery($indexerQuery);
        $this->pager->setPage($page);
        $this->pager->setMaxPerPage($maxPerPage);

        $this->assertEquals($expected, $this->pager->haveToPaginate());
    }
}
