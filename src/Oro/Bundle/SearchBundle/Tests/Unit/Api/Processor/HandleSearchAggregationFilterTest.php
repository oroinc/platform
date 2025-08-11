<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Model\LoadEntityIdsBySearchQuery;
use Oro\Bundle\SearchBundle\Api\Processor\HandleSearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Processor\NormalizeSearchAggregatedData;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class HandleSearchAggregationFilterTest extends GetListProcessorTestCase
{
    /** @var HandleSearchAggregationFilter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new HandleSearchAggregationFilter();
    }

    public function testProcessWhenNoQuery(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);

        $filter->expects(self::never())
            ->method('applyToSearchQuery');

        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertFalse($this->context->has(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES));
        self::assertFalse($this->context->isProcessed(HandleSearchAggregationFilter::OPERATION_NAME));
    }

    public function testProcessForNotSupportedQuery(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);

        $filter->expects(self::never())
            ->method('applyToSearchQuery');

        $this->context->setQuery($this->createMock(QueryBuilder::class));
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertFalse($this->context->has(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES));
        self::assertFalse($this->context->isProcessed(HandleSearchAggregationFilter::OPERATION_NAME));
    }

    public function testProcessForNotSupportedFilter(): void
    {
        $this->context->setQuery($this->createMock(SearchQuery::class));
        $this->context->getFilters()->add('aggregations', $this->createMock(StandaloneFilter::class));
        $this->processor->process($this->context);
        self::assertFalse($this->context->has(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES));
        self::assertFalse($this->context->isProcessed(HandleSearchAggregationFilter::OPERATION_NAME));
    }

    public function testProcessWhenFilterIsAlreadyApplied(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);

        $filter->expects(self::never())
            ->method('applyToSearchQuery');

        $this->context->setProcessed(HandleSearchAggregationFilter::OPERATION_NAME);
        $this->context->setQuery($this->createMock(SearchQuery::class));
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertFalse($this->context->has(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES));
        self::assertTrue($this->context->isProcessed(HandleSearchAggregationFilter::OPERATION_NAME));
    }

    public function testProcess(): void
    {
        $query = $this->createMock(SearchQuery::class);
        $filter = $this->createMock(SearchAggregationFilter::class);
        $aggregationDataTypes = ['field1' => 'integer'];

        $filter->expects(self::once())
            ->method('applyToSearchQuery')
            ->with(self::identicalTo($query));
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn($aggregationDataTypes);

        $this->context->setQuery($query);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame(
            $aggregationDataTypes,
            $this->context->get(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES)
        );
        self::assertTrue($this->context->isProcessed(HandleSearchAggregationFilter::OPERATION_NAME));
    }

    public function testProcessForSearchQueryInterface(): void
    {
        $query = $this->createMock(SearchQuery::class);
        $filter = $this->createMock(SearchAggregationFilter::class);
        $aggregationDataTypes = ['field1' => 'integer'];

        $filter->expects(self::once())
            ->method('applyToSearchQuery')
            ->with(self::identicalTo($query));
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn($aggregationDataTypes);

        $queryWrapper = $this->createMock(SearchQueryInterface::class);
        $queryWrapper->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->context->setQuery($queryWrapper);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame(
            $aggregationDataTypes,
            $this->context->get(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES)
        );
        self::assertTrue($this->context->isProcessed(HandleSearchAggregationFilter::OPERATION_NAME));
    }

    public function testProcessForLoadEntityIdsBySearchQuery(): void
    {
        $query = $this->createMock(SearchQuery::class);
        $filter = $this->createMock(SearchAggregationFilter::class);
        $aggregationDataTypes = ['field1' => 'integer'];

        $filter->expects(self::once())
            ->method('applyToSearchQuery')
            ->with(self::identicalTo($query));
        $filter->expects(self::once())
            ->method('getAggregationDataTypes')
            ->willReturn($aggregationDataTypes);

        $queryWrapper = $this->createMock(LoadEntityIdsBySearchQuery::class);
        $queryWrapper->expects(self::once())
            ->method('getSearchQuery')
            ->willReturn($query);

        $this->context->setQuery($queryWrapper);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
        self::assertSame(
            $aggregationDataTypes,
            $this->context->get(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES)
        );
        self::assertTrue($this->context->isProcessed(HandleSearchAggregationFilter::OPERATION_NAME));
    }
}
