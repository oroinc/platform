<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\SearchAggregationFilter;
use Oro\Bundle\ApiBundle\Filter\SearchAggregationFilterFactory;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolverFactory;

class SearchAggregationFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|SearchFieldResolverFactory */
    private $searchFieldResolverFactory;

    /** @var SearchAggregationFilterFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->searchFieldResolverFactory = $this->createMock(SearchFieldResolverFactory::class);

        $this->factory = new SearchAggregationFilterFactory(
            $this->searchFieldResolverFactory
        );
    }

    public function testCreateFilter()
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
