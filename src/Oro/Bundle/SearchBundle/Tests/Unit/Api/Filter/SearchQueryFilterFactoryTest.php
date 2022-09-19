<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Filter;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolverFactory;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilterFactory;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

class SearchQueryFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AbstractSearchMappingProvider */
    private $searchMappingProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SearchFieldResolverFactory */
    private $searchFieldResolverFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExpressionVisitor */
    private $searchQueryCriteriaVisitor;

    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);
        $this->searchFieldResolverFactory = $this->createMock(SearchFieldResolverFactory::class);
        $this->searchQueryCriteriaVisitor = $this->createMock(ExpressionVisitor::class);
    }

    public function testCreateFilter(): void
    {
        $dataType = 'string';

        $expectedFilter = new SearchQueryFilter($dataType);
        $expectedFilter->setSearchMappingProvider($this->searchMappingProvider);
        $expectedFilter->setSearchFieldResolverFactory($this->searchFieldResolverFactory);
        $expectedFilter->setSearchQueryCriteriaVisitor($this->searchQueryCriteriaVisitor);

        $factory = new SearchQueryFilterFactory(
            $this->searchMappingProvider,
            $this->searchFieldResolverFactory,
            $this->searchQueryCriteriaVisitor
        );

        self::assertEquals($expectedFilter, $factory->createFilter($dataType));
    }

    public function testCreateFilterWithoutSearchQueryCriteriaVisitor(): void
    {
        $dataType = 'string';

        $expectedFilter = new SearchQueryFilter($dataType);
        $expectedFilter->setSearchMappingProvider($this->searchMappingProvider);
        $expectedFilter->setSearchFieldResolverFactory($this->searchFieldResolverFactory);

        $factory = new SearchQueryFilterFactory(
            $this->searchMappingProvider,
            $this->searchFieldResolverFactory
        );

        self::assertEquals($expectedFilter, $factory->createFilter($dataType));
    }
}
