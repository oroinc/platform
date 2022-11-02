<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Filter;

use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilterFactory;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolverFactory;

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
