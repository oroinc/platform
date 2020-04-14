<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolverFactory;
use Oro\Bundle\ApiBundle\Filter\SearchQueryFilter;
use Oro\Bundle\ApiBundle\Filter\SearchQueryFilterFactory;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

class SearchQueryFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AbstractSearchMappingProvider */
    private $searchMappingProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SearchFieldResolverFactory */
    private $searchFieldResolverFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExpressionVisitor */
    private $searchQueryCriteriaVisitor;

    /** @var SearchQueryFilterFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);
        $this->searchFieldResolverFactory = $this->createMock(SearchFieldResolverFactory::class);
        $this->searchQueryCriteriaVisitor = $this->createMock(ExpressionVisitor::class);

        $this->factory = new SearchQueryFilterFactory(
            $this->searchMappingProvider,
            $this->searchFieldResolverFactory,
            $this->searchQueryCriteriaVisitor
        );
    }

    public function testCreateFilter()
    {
        $dataType = 'string';

        $expectedFilter = new SearchQueryFilter($dataType);
        $expectedFilter->setSearchMappingProvider($this->searchMappingProvider);
        $expectedFilter->setSearchFieldResolverFactory($this->searchFieldResolverFactory);
        $expectedFilter->setSearchQueryCriteriaVisitor($this->searchQueryCriteriaVisitor);

        self::assertEquals(
            $expectedFilter,
            $this->factory->createFilter($dataType)
        );
    }
}
