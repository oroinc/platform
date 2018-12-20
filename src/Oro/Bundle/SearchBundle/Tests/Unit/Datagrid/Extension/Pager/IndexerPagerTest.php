<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Pager;

use Oro\Bundle\SearchBundle\Datagrid\Extension\Pager\IndexerPager;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class IndexerPagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IndexerPager
     */
    protected $pager;

    protected function setUp()
    {
        $this->pager = new IndexerPager();
    }

    protected function tearDown()
    {
        unset($this->pager);
    }

    public function testSetQuery()
    {
        $indexerQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pager->setQuery($indexerQuery);
        $this->assertAttributeEquals($indexerQuery, 'query', $this->pager);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Indexer query must be set
     */
    public function testInit()
    {
        $this->pager->init();
    }

    public function testGetNbResults()
    {
        $totalCount = 123;

        $indexerQuery = $this->createMock(
            IndexerQuery::class,
            ['getTotalCount'],
            [],
            '',
            false
        );
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
        $indexerQuery = $this->createMock(
            IndexerQuery::class,
            ['setMaxResults', 'setFirstResult'],
            [],
            '',
            false
        );
        $indexerQuery->expects($this->once())
            ->method('setMaxResults')
            ->with($maxResults);
        $indexerQuery->expects($this->once())
            ->method('setFirstResult')
            ->with($firstResult);

        $this->pager->setQuery($indexerQuery);

        $this->pager->setMaxPerPage($maxPerPage);
        $this->assertAttributeEquals($maxPerPage, 'maxPerPage', $this->pager);
        $this->assertEquals($maxPerPage, $this->pager->getMaxPerPage());
    }

    public function testSetGetPage()
    {
        $page        = 2;
        $firstResult = 10;

        $indexerQuery = $this->createMock(
            IndexerQuery::class,
            ['setFirstResult'],
            [],
            '',
            false
        );
        $indexerQuery->expects($this->once())
            ->method('setFirstResult')
            ->with($firstResult);

        $this->pager->setQuery($indexerQuery);

        $this->pager->setPage($page);
        $this->assertAttributeEquals($page, 'page', $this->pager);
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

        $indexerQuery = $this->createMock(
            IndexerQuery::class,
            ['getTotalCount', 'setMaxResults', 'setFirstResult'],
            [],
            '',
            false
        );
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
        $indexerQuery = $this->createMock(
            IndexerQuery::class,
            ['getTotalCount', 'setMaxResults', 'setFirstResult'],
            [],
            '',
            false
        );
        $indexerQuery->expects($this->any())
            ->method('getTotalCount')
            ->will($this->returnValue($totalCount));

        $this->pager->setQuery($indexerQuery);
        $this->pager->setPage($page);
        $this->pager->setMaxPerPage($maxPerPage);

        $this->assertEquals($expected, $this->pager->haveToPaginate());
    }
}
