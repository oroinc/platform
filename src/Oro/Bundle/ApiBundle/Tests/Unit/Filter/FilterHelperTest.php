<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterHelper;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class FilterHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var FilterCollection */
    protected $filters;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $filterValues;

    /** @var FilterHelper */
    protected $filterHelper;

    public function setUp()
    {
        $this->filters = new FilterCollection();
        $this->filterValues = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface');

        $this->filterHelper = new FilterHelper($this->filters, $this->filterValues);
    }

    public function testEmptyFilters()
    {
        $this->assertNull($this->filterHelper->getFilterValue('test'));
        $this->assertNull($this->filterHelper->getPageNumber());
        $this->assertNull($this->filterHelper->getPageSize());
        $this->assertNull($this->filterHelper->getOrderBy());
        $this->assertNull($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testWithoutFilterValuesAndWithoutDefaultValues()
    {
        $this->filters->add(
            'page[number]',
            new PageNumberFilter(DataType::UNSIGNED_INTEGER, 'page number')
        );
        $this->filters->add(
            'page[size]',
            new PageSizeFilter(DataType::INTEGER, 'page size')
        );
        $this->filters->add(
            'sorting',
            new SortFilter(DataType::ORDER_BY, 'sorting')
        );
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filters->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValues->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $this->assertNull($this->filterHelper->getFilterValue('test'));
        $this->assertNull($this->filterHelper->getPageNumber());
        $this->assertNull($this->filterHelper->getPageSize());
        $this->assertNull($this->filterHelper->getOrderBy());
        $this->assertNull($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testWithoutFilterValues()
    {
        $this->filters->add(
            'page[number]',
            new PageNumberFilter(DataType::UNSIGNED_INTEGER, 'page number', 1)
        );
        $this->filters->add(
            'page[size]',
            new PageSizeFilter(DataType::INTEGER, 'page size', 10)
        );
        $this->filters->add(
            'sorting',
            new SortFilter(DataType::ORDER_BY, 'sorting', ['id' => 'ASC'])
        );
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filters->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValues->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $this->assertNull($this->filterHelper->getFilterValue('test'));
        $this->assertSame(1, $this->filterHelper->getPageNumber());
        $this->assertSame(10, $this->filterHelper->getPageSize());
        $this->assertEquals(['id' => 'ASC'], $this->filterHelper->getOrderBy());
        $this->assertNull($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testWithFilterValues()
    {
        $this->filters->add(
            'page[number]',
            new PageNumberFilter(DataType::UNSIGNED_INTEGER, 'page number', 1)
        );
        $this->filters->add(
            'page[size]',
            new PageSizeFilter(DataType::INTEGER, 'page size', 10)
        );
        $this->filters->add(
            'sorting',
            new SortFilter(DataType::ORDER_BY, 'sorting', ['id' => 'ASC'])
        );
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filters->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValues->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['page[number]', new FilterValue('page[number]', 2)],
                    ['page[size]', new FilterValue('page[size]', 20)],
                    ['sorting', new FilterValue('sorting', ['id' => 'DESC'])],
                    ['filter[test]', new FilterValue('filter[test]', true)],
                ]
            );

        $this->assertEquals(
            new FilterValue('filter[test]', true),
            $this->filterHelper->getFilterValue('test')
        );
        $this->assertSame(2, $this->filterHelper->getPageNumber());
        $this->assertSame(20, $this->filterHelper->getPageSize());
        $this->assertEquals(['id' => 'DESC'], $this->filterHelper->getOrderBy());
        $this->assertTrue($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testBooleanWithEqOperator()
    {
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filters->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValues->expects($this->once())
            ->method('get')
            ->with('filter[test]')
            ->willReturn(new FilterValue('filter[test]', true, ComparisonFilter::EQ));

        $this->assertEquals(
            new FilterValue('filter[test]', true, ComparisonFilter::EQ),
            $this->filterHelper->getFilterValue('test')
        );
        $this->assertTrue($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testBooleanWithNeqOperator()
    {
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filters->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValues->expects($this->once())
            ->method('get')
            ->with('filter[test]')
            ->willReturn(new FilterValue('filter[test]', true, ComparisonFilter::NEQ));

        $this->assertEquals(
            new FilterValue('filter[test]', true, ComparisonFilter::NEQ),
            $this->filterHelper->getFilterValue('test')
        );
        $this->assertFalse($this->filterHelper->getBooleanFilterValue('test'));
    }
}
