<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Processor\HandleSearchAggregationFilter;
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
    }

    public function testProcessForNotSupportedQuery(): void
    {
        $filter = $this->createMock(SearchAggregationFilter::class);

        $filter->expects(self::never())
            ->method('applyToSearchQuery');

        $this->context->setQuery($this->createMock(QueryBuilder::class));
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
    }

    public function testProcessForNotSupportedFilter(): void
    {
        $this->context->setQuery($this->createMock(SearchQueryInterface::class));
        $this->context->getFilters()->add('aggregations', $this->createMock(StandaloneFilter::class));
        $this->processor->process($this->context);
    }

    public function testProcess(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);
        $filter = $this->createMock(SearchAggregationFilter::class);

        $filter->expects(self::once())
            ->method('applyToSearchQuery')
            ->with(self::identicalTo($query));

        $this->context->setQuery($query);
        $this->context->getFilters()->add('aggregations', $filter);
        $this->processor->process($this->context);
    }
}
