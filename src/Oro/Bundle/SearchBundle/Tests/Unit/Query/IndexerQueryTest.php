<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IndexerQueryTest extends \PHPUnit\Framework\TestCase
{
    const TEST_VALUE = 'test_value';
    const TEST_COUNT = 42;

    /** @var IndexerQuery */
    protected $query;

    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    protected $searchIndexer;

    /** @var Query|\PHPUnit\Framework\MockObject\MockObject */
    protected $innerQuery;

    /** @var array */
    protected $testElements = [1, 2, 3];

    /** @var Criteria|\PHPUnit\Framework\MockObject\MockObject */
    protected $criteria;

    protected function setUp()
    {
        $this->searchIndexer = $this->createPartialMock(
            Indexer::class,
            ['query']
        );

        $this->innerQuery = $this->createPartialMock(
            Query::class,
            [
                'setFirstResult',
                'getFirstResult',
                'setMaxResults',
                'setFrom',
                'getMaxResults',
                'getOrderBy',
                'getOrderDirection',
                'getCriteria',
                'getOrderings',
                'addSelect'
            ]
        );

        $this->criteria = $this->createMock(Criteria::class);

        $this->innerQuery->method('getCriteria')
            ->willReturn($this->criteria);

        $this->query = new IndexerQuery($this->searchIndexer, $this->innerQuery);
    }

    protected function tearDown()
    {
        unset($this->searchIndexer, $this->innerQuery, $this->query);
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

        $this->assertEquals($this->query, $this->query->setFirstResult(self::TEST_VALUE));
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

        $this->assertEquals($this->query, $this->query->setMaxResults(self::TEST_VALUE));
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

        $this->assertEquals($this->query, $this->query->addWhere($expression));
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

        $this->assertEquals($this->query, $this->query->addWhere($expression, AbstractSearchQuery::WHERE_OR));
    }

    /**
     * @param array $arguments
     * @param array $ordering
     * @dataProvider orderByDataProvider
     */
    public function testSetOrderBy(array $arguments, array $ordering)
    {
        $this->criteria->expects($this->once())
            ->method('orderBy')
            ->with($ordering);

        $this->assertEquals($this->query, call_user_func_array([$this->query, 'setOrderBy'], $arguments));
    }

    /**
     * @return array
     */
    public function orderByDataProvider()
    {
        return [
            'only field name' => [
                'arguments' => ['field'],
                'ordering'  => ['text.field' => 'asc'],
            ],
            'field and direction' => [
                'arguments' => ['field', 'desc'],
                'ordering'  => ['text.field' => 'desc'],
            ],
            'field, direction and type' => [
                'arguments' => ['field', 'desc', 'decimal'],
                'ordering'  => ['decimal.field' => 'desc'],
            ],
            'field with predefined type' => [
                'arguments' => ['decimal.field', 'desc'],
                'ordering'  => ['decimal.field' => 'desc'],
            ],
        ];
    }

    public function testAddSelect()
    {
        $this->innerQuery->expects($this->once())
            ->method('addSelect')
            ->with('field', 'decimal');

        $this->assertEquals($this->query, $this->query->addSelect('field', 'decimal'));
    }

    public function setFrom()
    {
        $this->innerQuery->expects($this->once())
            ->method('setFrom')
            ->with(self::TEST_VALUE);

        $this->assertEquals($this->query, $this->query->setFrom(self::TEST_VALUE));
    }

    public function testAggregationAccessors()
    {
        $this->assertEquals([], $this->query->getAggregations());

        $this->query->addAggregate('test_name1', 'test_field1', 'test_function1');
        $this->query->addAggregate('test_name2', 'test_field2', 'test_function2');

        $this->assertEquals(
            [
                'test_name1' => ['field' => 'test_field1', 'function' => 'test_function1'],
                'test_name2' => ['field' => 'test_field2', 'function' => 'test_function2'],
            ],
            $this->query->getAggregations()
        );
    }

    public function testClone()
    {
        $result1 = $this->prepareResult();
        $result2 = $this->prepareResult();

        $this->searchIndexer->expects($this->exactly(2))
            ->method('query')
            ->with($this->innerQuery)
            ->willReturnOnConsecutiveCalls($result1, $result2);

        $this->assertSame($result1, $this->query->getResult());
        $this->assertSame($this->innerQuery, $this->query->getQuery());

        $newQuery = clone $this->query;

        $this->assertSame($result2, $newQuery->getResult());
        $this->assertNotSame($this->innerQuery, $newQuery->getQuery());
        $this->assertEquals($this->innerQuery, $newQuery->getQuery());
    }
}
