<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IndexerQueryTest extends TestCase
{
    private const TEST_VALUE = 'test_value';
    private const TEST_COUNT = 42;

    private Indexer&MockObject $searchIndexer;
    private Query&MockObject $innerQuery;

    private array $testElements = [1, 2, 3];

    private Criteria&MockObject $criteria;
    private IndexerQuery $query;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchIndexer = $this->createMock(Indexer::class);

        $this->innerQuery = $this->getMockBuilder(Query::class)
            ->onlyMethods(['getCriteria', 'addSelect', 'getFrom', 'from'])
            ->addMethods([
                'setFirstResult',
                'getFirstResult',
                'setMaxResults',
                'getMaxResults',
                'getOrderBy',
                'getOrderDirection',
                'getOrderings'
            ])
            ->getMock();

        $this->criteria = $this->createMock(Criteria::class);

        $this->innerQuery->expects($this->any())
            ->method('getCriteria')
            ->willReturn($this->criteria);

        $this->query = new IndexerQuery($this->searchIndexer, $this->innerQuery);
    }

    private function prepareResult(): Result
    {
        return new Result($this->innerQuery, $this->testElements, self::TEST_COUNT);
    }

    public function testCall(): void
    {
        $this->innerQuery->expects($this->once())
            ->method('getOrderDirection')
            ->willReturn(self::TEST_VALUE);

        $this->assertEquals(self::TEST_VALUE, $this->query->getOrderDirection());
    }

    public function testExecute(): void
    {
        $result = $this->prepareResult();

        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($this->innerQuery)
            ->willReturn($result);

        // two calls to assert lazy load
        $this->assertEquals($this->testElements, $this->query->execute());
        $this->assertEquals($this->testElements, $this->query->execute());
    }

    public function testSetFirstResult(): void
    {
        $this->criteria->expects($this->once())
            ->method('setFirstResult')
            ->with(self::TEST_VALUE);

        $this->assertEquals($this->query, $this->query->setFirstResult(self::TEST_VALUE));
    }

    public function testGetFirstResult(): void
    {
        $this->criteria->expects($this->once())
            ->method('getFirstResult')
            ->willReturn(self::TEST_VALUE);

        $this->assertEquals(self::TEST_VALUE, $this->query->getFirstResult());
    }

    public function testSetMaxResults(): void
    {
        $this->criteria->expects($this->once())
            ->method('setMaxResults')
            ->with(self::TEST_VALUE);

        $this->assertEquals($this->query, $this->query->setMaxResults(self::TEST_VALUE));
    }

    public function testGetMaxResults(): void
    {
        $this->criteria->expects($this->once())
            ->method('getMaxResults')
            ->willReturn(self::TEST_VALUE);

        $this->assertEquals(self::TEST_VALUE, $this->query->getMaxResults());
    }

    public function testGetTotalCount(): void
    {
        $result = $this->prepareResult();

        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($this->innerQuery)
            ->willReturn($result);

        $this->assertEquals(self::TEST_COUNT, $this->query->getTotalCount());
    }

    public function testGetSortBy(): void
    {
        $this->criteria->expects($this->once())
            ->method('getOrderings')
            ->willReturn([self::TEST_VALUE => self::TEST_VALUE]);

        $this->assertEquals(self::TEST_VALUE, $this->query->getSortBy());
    }

    public function testGetSortOrder(): void
    {
        $this->criteria->expects($this->once())
            ->method('getOrderings')
            ->willReturn([self::TEST_VALUE => 'ASC']);

        $this->assertEquals('ASC', $this->query->getSortOrder());
    }

    public function testSetWhere(): void
    {
        $expression = Criteria::expr()->eq('field', 'value');

        $this->criteria->expects($this->once())
            ->method('andWhere')
            ->with($expression);

        $this->assertEquals($this->query, $this->query->addWhere($expression));
    }

    public function testSetWhereOr(): void
    {
        $expression = Criteria::expr()->eq('field', 'value');

        $this->criteria->expects($this->once())
            ->method('orWhere')
            ->with($expression);

        $this->innerQuery->expects($this->once())
            ->method('getCriteria')
            ->willReturn($this->criteria);

        $this->assertEquals($this->query, $this->query->addWhere($expression, AbstractSearchQuery::WHERE_OR));
    }

    /**
     * @dataProvider orderByDataProvider
     */
    public function testSetOrderBy(array $arguments, array $ordering): void
    {
        $this->criteria->expects($this->once())
            ->method('orderBy')
            ->with($ordering);

        $this->assertEquals($this->query, call_user_func_array([$this->query, 'setOrderBy'], $arguments));
    }

    public function orderByDataProvider(): array
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

    /**
     * @dataProvider addOrderByDataProvider
     */
    public function testAddOrderBy(array $arguments, array $ordering): void
    {
        $this->criteria->expects(self::once())
            ->method('getOrderings')
            ->willReturn(['integer.field' => 3]);

        $this->criteria->expects(self::once())
            ->method('orderBy')
            ->with($ordering);

        $this->query->addOrderBy(...$arguments);
    }

    public function addOrderByDataProvider(): array
    {
        return [
            'only field name' => [
                'arguments' => ['field'],
                'ordering' => ['integer.field' => 3, 'text.field' => 'asc']
            ],
            'field and direction' => [
                'arguments' => ['field', 'desc'],
                'ordering'  => ['integer.field' => 3, 'text.field' => 'desc'],
            ],
            'field, direction and type' => [
                'arguments' => ['field', 'desc', 'decimal'],
                'ordering'  => ['integer.field' => 3, 'decimal.field' => 'desc'],
            ],
            'field with predefined type' => [
                'arguments' => ['decimal.field', 'desc'],
                'ordering'  => ['integer.field' => 3, 'decimal.field' => 'desc'],
            ],
            'field with prepend' => [
                'arguments' => ['field', 'desc', 'decimal', true],
                'ordering'  => ['decimal.field' => 'desc', 'integer.field' => 3],
            ],
        ];
    }

    public function testAddSelect(): void
    {
        $this->innerQuery->expects($this->once())
            ->method('addSelect')
            ->with('field', 'decimal');

        $this->assertEquals($this->query, $this->query->addSelect('field', 'decimal'));
    }

    public function testGetFromWhenItIsNotSet(): void
    {
        $this->innerQuery->expects($this->once())
            ->method('getFrom')
            ->willReturn(false);

        $this->assertNull($this->query->getFrom());
    }

    public function testGetFrom(): void
    {
        $this->innerQuery->expects($this->once())
            ->method('getFrom')
            ->willReturn(self::TEST_VALUE);

        $this->assertEquals(self::TEST_VALUE, $this->query->getFrom());
    }

    public function testSetFrom(): void
    {
        $this->innerQuery->expects($this->once())
            ->method('from')
            ->with(self::TEST_VALUE);

        $this->assertEquals($this->query, $this->query->setFrom(self::TEST_VALUE));
    }

    public function testAggregationAccessors(): void
    {
        $this->assertEquals([], $this->query->getAggregations());

        $this->query->addAggregate('test_name1', 'test_field1', 'test_function1');
        $this->query->addAggregate('test_name2', 'test_field2', 'test_function2');

        $this->assertEquals(
            [
                'test_name1' => ['field' => 'test_field1', 'function' => 'test_function1', 'parameters' => []],
                'test_name2' => ['field' => 'test_field2', 'function' => 'test_function2', 'parameters' => []],
            ],
            $this->query->getAggregations()
        );
    }

    public function testClone(): void
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
