<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SearchAggregationFilter;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolver;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolverFactory;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchAggregationFilterTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    /** @var \PHPUnit\Framework\MockObject\MockObject|SearchFieldResolver */
    private $searchFieldResolver;

    /** @var SearchAggregationFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->searchFieldResolver = $this->createMock(SearchFieldResolver::class);

        $fieldMappings = ['field1' => 'field_1'];
        $fieldTypes = ['field1' => 'integer'];

        $searchFieldResolverFactory = $this->createMock(SearchFieldResolverFactory::class);
        $searchFieldResolverFactory->expects(self::any())
            ->method('createFieldResolver')
            ->with(self::ENTITY_CLASS, $fieldMappings)
            ->willReturn($this->searchFieldResolver);

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
            ->willReturnCallback(function ($fieldName) use ($fieldTypes) {
                return $fieldTypes[$fieldName] ?? 'text';
            });

        $this->filter = new SearchAggregationFilter(DataType::STRING);
        $this->filter->setArrayAllowed(true);
        $this->filter->setSearchFieldResolverFactory($searchFieldResolverFactory);
        $this->filter->setEntityClass(self::ENTITY_CLASS);
        $this->filter->setFieldMappings($fieldMappings);
    }

    public function testGetAggregationDataTypes()
    {
        $this->filter->apply(new Criteria(), new FilterValue('path', ['field2 count', 'field1 sum']));

        self::assertEquals(
            ['field2Count' => 'text', 'field1Sum' => 'integer'],
            $this->filter->getAggregationDataTypes()
        );
    }

    /**
     * @dataProvider validFilterDataProvider
     */
    public function testValidFilter($filterValue, $expectedAggregations)
    {
        $query = new IndexerQuery($this->createMock(Indexer::class), new SearchQuery());
        $this->filter->apply(new Criteria(), $filterValue);
        $this->filter->applyToSearchQuery($query);

        self::assertEquals($expectedAggregations, $query->getAggregations());
    }

    public function validFilterDataProvider()
    {
        return [
            'no filter value'       => [
                null,
                []
            ],
            'field with mapping'    => [
                new FilterValue('path', 'field1 count'),
                ['field1Count' => ['field' => 'integer.field_1', 'function' => 'count']]
            ],
            'field without mapping' => [
                new FilterValue('path', 'field2 count'),
                ['field2Count' => ['field' => 'text.field2', 'function' => 'count']]
            ],
            'custom field alias'    => [
                new FilterValue('path', 'field2 count myCount'),
                ['myCount' => ['field' => 'text.field2', 'function' => 'count']]
            ],
            'several aggregations'  => [
                new FilterValue('path', ['field2 count', 'field1 sum']),
                [
                    'field2Count' => ['field' => 'text.field2', 'function' => 'count'],
                    'field1Sum'   => ['field' => 'integer.field_1', 'function' => 'sum']
                ]
            ]
        ];
    }

    public function testNoFunction()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1" must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }

    public function testNotSupportedFunction()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage('The aggregating function "someFunction" is not supported.');

        $value = 'field1 someFunction';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }

    public function testNotSupportedFunctionForDataType()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage('The aggregating function "sum" is not supported for the field type "text".');

        $value = 'field2 sum';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }

    public function testExtraElement()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1 count alias someOther" must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1 count alias someOther';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }

    public function testEmptyDefinition()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "" must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = '';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }

    public function testEmptyField()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value " " must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = ' ';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }

    public function testEmptyFunction()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1 " must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1 ';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }

    public function testEmptyAlias()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1 count " must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1 count ';
        $this->filter->apply(new Criteria(), new FilterValue('path', $value));
    }
}
