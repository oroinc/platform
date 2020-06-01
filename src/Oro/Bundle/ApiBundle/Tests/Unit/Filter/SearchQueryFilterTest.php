<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolver;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolverFactory;
use Oro\Bundle\ApiBundle\Filter\SearchQueryFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

class SearchQueryFilterTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';
    private const ENTITY_ALIAS = 'test_entity';

    /** @var \PHPUnit\Framework\MockObject\MockObject|AbstractSearchMappingProvider */
    private $searchMappingProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SearchFieldResolver */
    private $searchFieldResolver;

    /** @var SearchQueryFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);
        $this->searchFieldResolver = $this->createMock(SearchFieldResolver::class);

        $fieldMappings = ['field1' => 'field_1'];
        $searchFieldResolverFactory = $this->createMock(SearchFieldResolverFactory::class);
        $searchFieldResolverFactory->expects(self::any())
            ->method('createFieldResolver')
            ->with(self::ENTITY_CLASS, $fieldMappings)
            ->willReturn($this->searchFieldResolver);

        $this->searchMappingProvider->expects(self::any())
            ->method('getEntityConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(['alias' => self::ENTITY_ALIAS]);

        $this->searchFieldResolver->expects(self::any())
            ->method('resolveFieldName')
            ->willReturnCallback(function ($fieldName) use ($fieldMappings) {
                if (isset($fieldMappings[$fieldName])) {
                    $fieldName = $fieldMappings[$fieldName];
                }

                return $fieldName;
            });
        $this->searchFieldResolver->expects(self::any())
            ->method('resolveFieldType')
            ->willReturn('text');

        $this->filter = new SearchQueryFilter(DataType::STRING);
        $this->filter->setSearchMappingProvider($this->searchMappingProvider);
        $this->filter->setSearchFieldResolverFactory($searchFieldResolverFactory);
        $this->filter->setEntityClass(self::ENTITY_CLASS);
        $this->filter->setFieldMappings($fieldMappings);
    }

    public function testValidFilter()
    {
        $criteria = new Criteria();
        $this->filter->apply($criteria, new FilterValue('searchQuery', 'field1 = "test"'));

        self::assertEquals(
            new Comparison('text.field_1', '=', new Value('test')),
            $criteria->getWhereExpression()
        );
    }

    public function testValidFilterWithSearchQueryCriteriaVisitor()
    {
        $criteria = new Criteria();
        $searchQueryCriteriaVisitor = $this->createMock(ExpressionVisitor::class);
        $this->filter->setSearchQueryCriteriaVisitor($searchQueryCriteriaVisitor);

        $searchQueryCriteriaVisitor->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (Comparison $expr) {
                return new Comparison($expr->getField() . '_updated', $expr->getOperator(), $expr->getValue());
            });

        $this->filter->apply($criteria, new FilterValue('searchQuery', 'field1 = "test"'));

        self::assertEquals(
            new Comparison('text.field_1_updated', '=', new Value('test')),
            $criteria->getWhereExpression()
        );
    }

    public function testInvalidFilter()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage('Not allowed operator.');

        $criteria = new Criteria();
        $this->filter->apply($criteria, new FilterValue('searchQuery', 'field1 . "test"'));
    }

    public function testEmptyFilterValue()
    {
        $criteria = new Criteria();
        $this->filter->apply($criteria, new FilterValue('searchQuery', ''));

        self::assertNull($criteria->getWhereExpression());
    }

    public function testEmptyFilterValueWithSearchQueryCriteriaVisitor()
    {
        $criteria = new Criteria();
        $searchQueryCriteriaVisitor = $this->createMock(ExpressionVisitor::class);
        $this->filter->setSearchQueryCriteriaVisitor($searchQueryCriteriaVisitor);

        $searchQueryCriteriaVisitor->expects(self::never())
            ->method('dispatch');

        $this->filter->apply($criteria, new FilterValue('searchQuery', ''));

        self::assertNull($criteria->getWhereExpression());
    }
}
