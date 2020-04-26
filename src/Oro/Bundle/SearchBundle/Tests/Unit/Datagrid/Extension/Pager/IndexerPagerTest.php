<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Pager;

use Oro\Bundle\SearchBundle\Datagrid\Extension\Pager\IndexerPager;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\MockObject\MockObject;

class IndexerPagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var IndexerPager */
    protected $pager;

    protected function setUp(): void
    {
        $this->pager = new IndexerPager();
    }

    protected function tearDown(): void
    {
        unset($this->pager);
    }

    public function testSetQueryIsUsedLater()
    {
        /** @var SearchQueryInterface|MockObject $indexerQuery */
        $indexerQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTotalCount'])
            ->getMockForAbstractClass();

        $this->pager->setQuery($indexerQuery);

        $indexerQuery->expects(static::once())->method('getTotalCount');
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
            ->will($this->returnValue($totalCount));

        $this->pager->setQuery($indexerQuery);
        $this->assertEquals($totalCount, $this->pager->getNbResults());
    }

    /**
     * Data provider for testSetMaxPerPage
     *
     * @return array
     */
    public function maxPerPageDataProvider()
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
     * @param int $maxPerPage
     * @param int $maxResults
     * @param int $firstResult
     *
     * @dataProvider maxPerPageDataProvider
     */
    public function testSetGetMaxPerPage($maxPerPage, $maxResults, $firstResult)
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
        $page        = 2;
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
        $page         = 2;
        $maxPerPage   = 20;
        $totalCount   = 123;
        $firstPage    = 1;
        $lastPage     = 7;
        $previousPage = 1;
        $nextPage     = 3;

        $indexerQuery = $this->createMock(IndexerQuery::class);
        $indexerQuery->expects($this->any())
            ->method('getTotalCount')
            ->will($this->returnValue($totalCount));

        $this->pager->setQuery($indexerQuery);
        $this->pager->setPage($page);
        $this->pager->setMaxPerPage($maxPerPage);

        $this->assertEquals($firstPage, $this->pager->getFirstPage());
        $this->assertEquals($lastPage, $this->pager->getLastPage());
        $this->assertEquals($previousPage, $this->pager->getPreviousPage());
        $this->assertEquals($nextPage, $this->pager->getNextPage());
    }

    /**
     * Data provider for testHaveToPaginate
     *
     * @return array
     */
    public function haveToPaginateDataProvider()
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
     * @param boolean $expected
     * @param int     $page
     * @param int     $maxPerPage
     * @param int     $totalCount
     *
     * @dataProvider haveToPaginateDataProvider
     */
    public function testHaveToPaginate($expected, $page, $maxPerPage, $totalCount)
    {
        $indexerQuery = $this->createMock(IndexerQuery::class);
        $indexerQuery->expects($this->any())
            ->method('getTotalCount')
            ->will($this->returnValue($totalCount));

        $this->pager->setQuery($indexerQuery);
        $this->pager->setPage($page);
        $this->pager->setMaxPerPage($maxPerPage);

        $this->assertEquals($expected, $this->pager->haveToPaginate());
    }
}
