<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterHelper;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class FilterHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterCollection */
    private $filters;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterValueAccessorInterface */
    private $filterValues;

    /** @var FilterHelper */
    private $filterHelper;

    protected function setUp()
    {
        $this->filters = new FilterCollection();
        $this->filterValues = $this->createMock(FilterValueAccessorInterface::class);

        $this->filterHelper = new FilterHelper($this->filters, $this->filterValues);
    }

    public function testEmptyFilters()
    {
        self::assertNull($this->filterHelper->getFilterValue('test'));
        self::assertNull($this->filterHelper->getPageNumber());
        self::assertNull($this->filterHelper->getPageSize());
        self::assertNull($this->filterHelper->getOrderBy());
        self::assertNull($this->filterHelper->getBooleanFilterValue('test'));
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

        $this->filterValues->expects(self::any())
            ->method('get')
            ->willReturn(null);

        self::assertNull($this->filterHelper->getFilterValue('test'));
        self::assertNull($this->filterHelper->getPageNumber());
        self::assertNull($this->filterHelper->getPageSize());
        self::assertNull($this->filterHelper->getOrderBy());
        self::assertNull($this->filterHelper->getBooleanFilterValue('test'));
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

        $this->filterValues->expects(self::any())
            ->method('get')
            ->willReturn(null);

        self::assertNull($this->filterHelper->getFilterValue('test'));
        self::assertSame(1, $this->filterHelper->getPageNumber());
        self::assertSame(10, $this->filterHelper->getPageSize());
        self::assertEquals(['id' => 'ASC'], $this->filterHelper->getOrderBy());
        self::assertNull($this->filterHelper->getBooleanFilterValue('test'));
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

        $this->filterValues->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    ['page[number]', new FilterValue('page[number]', 2)],
                    ['page[size]', new FilterValue('page[size]', 20)],
                    ['sorting', new FilterValue('sorting', ['id' => 'DESC'])],
                    ['filter[test]', new FilterValue('filter[test]', true)]
                ]
            );

        self::assertEquals(
            new FilterValue('filter[test]', true),
            $this->filterHelper->getFilterValue('test')
        );
        self::assertSame(2, $this->filterHelper->getPageNumber());
        self::assertSame(20, $this->filterHelper->getPageSize());
        self::assertEquals(['id' => 'DESC'], $this->filterHelper->getOrderBy());
        self::assertTrue($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testBooleanWithEqOperator()
    {
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filters->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValues->expects(self::once())
            ->method('get')
            ->with('filter[test]')
            ->willReturn(new FilterValue('filter[test]', true, ComparisonFilter::EQ));

        self::assertEquals(
            new FilterValue('filter[test]', true, ComparisonFilter::EQ),
            $this->filterHelper->getFilterValue('test')
        );
        self::assertTrue($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testBooleanWithNeqOperator()
    {
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filters->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValues->expects(self::once())
            ->method('get')
            ->with('filter[test]')
            ->willReturn(new FilterValue('filter[test]', true, ComparisonFilter::NEQ));

        self::assertEquals(
            new FilterValue('filter[test]', true, ComparisonFilter::NEQ),
            $this->filterHelper->getFilterValue('test')
        );
        self::assertFalse($this->filterHelper->getBooleanFilterValue('test'));
    }
}
