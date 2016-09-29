<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

class IndexerQueryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_VALUE = 'test_value';
    const TEST_COUNT = 42;

    /** @var IndexerQuery */
    protected $query;

    /** @var Indexer|\PHPUnit_Framework_MockObject_MockObject */
    protected $searchIndexer;

    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    protected $innerQuery;

    /** @var array */
    protected $testElements = [1, 2, 3];

    /** @var Criteria|\PHPUnit_Framework_MockObject_MockObject */
    protected $criteria;

    protected function setUp()
    {
        $this->searchIndexer = $this->getMock(
            Indexer::class,
            ['query'],
            [],
            '',
            false
        );

        $this->innerQuery = $this->getMock(
            Query::class,
            [
                'setFirstResult',
                'getFirstResult',
                'setMaxResults',
                'getMaxResults',
                'getOrderBy',
                'getOrderDirection',
                'getCriteria',
                'getOrderings'
            ],
            [],
            '',
            false
        );

        $this->criteria = $this->getMock(Criteria::class);

        $this->innerQuery->method('getCriteria')
            ->willReturn($this->criteria);

        $this->query = new IndexerQuery($this->searchIndexer, $this->innerQuery);
    }

    protected function tearDown()
    {
        unset($this->searchIndexer);
        unset($this->innerQuery);
        unset($this->query);
    }

    /**
     * @return Result
     */
    protected function prepareResult()
    {
        return new Result($this->innerQuery, $this->testElements, self::TEST_COUNT);
    }

    public function testCall()
    {
        $this->innerQuery->expects($this->once())
            ->method('getOrderDirection')
            ->will($this->returnValue(self::TEST_VALUE));

        $this->assertEquals(self::TEST_VALUE, $this->query->getOrderDirection());
    }

    public function testExecute()
    {
        $result = $this->prepareResult();

        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($this->innerQuery)
            ->will($this->returnValue($result));

        // two calls to assert lazy load
        $this->assertEquals($this->testElements, $this->query->execute());
        $this->assertEquals($this->testElements, $this->query->execute());
    }

    public function testSetFirstResult()
    {
        $this->criteria->expects($this->once())
            ->method('setFirstResult')
            ->with(self::TEST_VALUE);

        $this->query->setFirstResult(self::TEST_VALUE);
    }

    public function testGetFirstResult()
    {
        $this->criteria->expects($this->once())
            ->method('getFirstResult')
            ->will($this->returnValue(self::TEST_VALUE));

        $this->assertEquals(self::TEST_VALUE, $this->query->getFirstResult());
    }

    public function testSetMaxResults()
    {
        $this->criteria->expects($this->once())
            ->method('setMaxResults')
            ->with(self::TEST_VALUE);

        $this->query->setMaxResults(self::TEST_VALUE);
    }

    public function testGetMaxResults()
    {
        $this->criteria->expects($this->once())
            ->method('getMaxResults')
            ->will($this->returnValue(self::TEST_VALUE));

        $this->assertEquals(self::TEST_VALUE, $this->query->getMaxResults());
    }

    public function testGetTotalCount()
    {
        $result = $this->prepareResult();

        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($this->innerQuery)
            ->will($this->returnValue($result));

        $this->assertEquals(self::TEST_COUNT, $this->query->getTotalCount());
    }

    public function testGetSortBy()
    {
        $this->criteria->expects($this->once())
            ->method('getOrderings')
            ->will($this->returnValue([self::TEST_VALUE => self::TEST_VALUE]));

        $this->assertEquals(self::TEST_VALUE, $this->query->getSortBy());
    }

    public function testGetSortOrder()
    {
        $this->criteria->expects($this->once())
            ->method('getOrderings')
            ->will($this->returnValue([self::TEST_VALUE => 'ASC']));

        $this->assertEquals('ASC', $this->query->getSortOrder());
    }

    public function testSetWhere()
    {
        $expression = Criteria::expr()->eq('field', 'value');

        $this->criteria->expects($this->once())
            ->method('andWhere')
            ->with($expression);

        $this->query->addWhere($expression);
    }

    public function testSetWhereOr()
    {
        $expression = Criteria::expr()->eq('field', 'value');

        $this->criteria->expects($this->once())
            ->method('orWhere')
            ->with($expression);

        $this->innerQuery->expects($this->once())
            ->method('getCriteria')
            ->will($this->returnValue($this->criteria));

        $this->query->addWhere($expression, AbstractSearchQuery::WHERE_OR);
    }
}
