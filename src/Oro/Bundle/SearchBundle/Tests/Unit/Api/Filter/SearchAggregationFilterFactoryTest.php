<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Filter;

use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilterFactory;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolverFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchAggregationFilterFactoryTest extends TestCase
{
    private SearchFieldResolverFactoryInterface&MockObject $searchFieldResolverFactory;
    private SearchAggregationFilterFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchFieldResolverFactory = $this->createMock(SearchFieldResolverFactoryInterface::class);

        $this->factory = new SearchAggregationFilterFactory(
            $this->searchFieldResolverFactory
        );
    }

    public function testCreateFilter(): void
    {
        $dataType = 'string';

        $expectedFilter = new SearchAggregationFilter($dataType);
        $expectedFilter->setSearchFieldResolverFactory($this->searchFieldResolverFactory);
        $expectedFilter->setArrayAllowed(true);

        self::assertEquals(
            $expectedFilter,
            $this->factory->createFilter($dataType)
        );
    }
}
