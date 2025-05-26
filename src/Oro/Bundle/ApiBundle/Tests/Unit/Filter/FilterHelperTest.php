<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterHelper;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilterHelperTest extends TestCase
{
    private FilterCollection $filterCollection;
    private FilterValueAccessorInterface&MockObject $filterValueAccessor;
    private FilterHelper $filterHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->filterCollection = new FilterCollection();
        $this->filterValueAccessor = $this->createMock(FilterValueAccessorInterface::class);

        $this->filterHelper = new FilterHelper($this->filterCollection, $this->filterValueAccessor);
    }

    public function testEmptyFilters(): void
    {
        self::assertNull($this->filterHelper->getFilterValue('test'));
        self::assertNull($this->filterHelper->getPageNumber());
        self::assertNull($this->filterHelper->getPageSize());
        self::assertNull($this->filterHelper->getOrderBy());
        self::assertNull($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testWithoutFilterValuesAndWithoutDefaultValues(): void
    {
        $this->filterCollection->add(
            'page[number]',
            new PageNumberFilter(DataType::UNSIGNED_INTEGER, 'page number')
        );
        $this->filterCollection->add(
            'page[size]',
            new PageSizeFilter(DataType::INTEGER, 'page size')
        );
        $this->filterCollection->add(
            'sorting',
            new SortFilter(DataType::ORDER_BY, 'sorting')
        );
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filterCollection->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValueAccessor->expects(self::any())
            ->method('getOne')
            ->willReturn(null);

        self::assertNull($this->filterHelper->getFilterValue('test'));
        self::assertNull($this->filterHelper->getPageNumber());
        self::assertNull($this->filterHelper->getPageSize());
        self::assertNull($this->filterHelper->getOrderBy());
        self::assertNull($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testWithoutFilterValues(): void
    {
        $this->filterCollection->add(
            'page[number]',
            new PageNumberFilter(DataType::UNSIGNED_INTEGER, 'page number', 1)
        );
        $this->filterCollection->add(
            'page[size]',
            new PageSizeFilter(DataType::INTEGER, 'page size', 10)
        );
        $this->filterCollection->add(
            'sorting',
            new SortFilter(DataType::ORDER_BY, 'sorting', ['id' => 'ASC'])
        );
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filterCollection->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValueAccessor->expects(self::any())
            ->method('getOne')
            ->willReturn(null);

        self::assertNull($this->filterHelper->getFilterValue('test'));
        self::assertSame(1, $this->filterHelper->getPageNumber());
        self::assertSame(10, $this->filterHelper->getPageSize());
        self::assertEquals(['id' => 'ASC'], $this->filterHelper->getOrderBy());
        self::assertNull($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testWithFilterValues(): void
    {
        $this->filterCollection->add(
            'page[number]',
            new PageNumberFilter(DataType::UNSIGNED_INTEGER, 'page number', 1)
        );
        $this->filterCollection->add(
            'page[size]',
            new PageSizeFilter(DataType::INTEGER, 'page size', 10)
        );
        $this->filterCollection->add(
            'sorting',
            new SortFilter(DataType::ORDER_BY, 'sorting', ['id' => 'ASC'])
        );
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filterCollection->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValueAccessor->expects(self::any())
            ->method('getOne')
            ->willReturnMap([
                ['page[number]', new FilterValue('page[number]', 2)],
                ['page[size]', new FilterValue('page[size]', 20)],
                ['sorting', new FilterValue('sorting', ['id' => 'DESC'])],
                ['filter[test]', new FilterValue('filter[test]', true)]
            ]);

        self::assertEquals(
            new FilterValue('filter[test]', true),
            $this->filterHelper->getFilterValue('test')
        );
        self::assertSame(2, $this->filterHelper->getPageNumber());
        self::assertSame(20, $this->filterHelper->getPageSize());
        self::assertEquals(['id' => 'DESC'], $this->filterHelper->getOrderBy());
        self::assertTrue($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testBooleanWithEqOperator(): void
    {
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filterCollection->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValueAccessor->expects(self::once())
            ->method('getOne')
            ->with('filter[test]')
            ->willReturn(new FilterValue('filter[test]', true, FilterOperator::EQ));

        self::assertEquals(
            new FilterValue('filter[test]', true, FilterOperator::EQ),
            $this->filterHelper->getFilterValue('test')
        );
        self::assertTrue($this->filterHelper->getBooleanFilterValue('test'));
    }

    public function testBooleanWithNeqOperator(): void
    {
        $testFilter = new ComparisonFilter(DataType::BOOLEAN, 'test filter');
        $testFilter->setField('test');
        $this->filterCollection->add(
            'filter[test]',
            $testFilter
        );

        $this->filterValueAccessor->expects(self::once())
            ->method('getOne')
            ->with('filter[test]')
            ->willReturn(new FilterValue('filter[test]', true, FilterOperator::NEQ));

        self::assertEquals(
            new FilterValue('filter[test]', true, FilterOperator::NEQ),
            $this->filterHelper->getFilterValue('test')
        );
        self::assertFalse($this->filterHelper->getBooleanFilterValue('test'));
    }
}
