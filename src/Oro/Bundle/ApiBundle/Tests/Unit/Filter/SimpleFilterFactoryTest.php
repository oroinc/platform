<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Filter\SimpleFilterFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SimpleFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    private function getFilterFactory(
        array $filters = [],
        array $filterFactories = [],
        array $factories = []
    ): SimpleFilterFactory {
        $factoryContainer = $this->createMock(ContainerInterface::class);
        $factoryContainer->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($serviceId) use ($factories) {
                return $factories[$serviceId];
            });

        return new SimpleFilterFactory(
            $filters,
            $filterFactories,
            $factoryContainer,
            PropertyAccess::createPropertyAccessor(),
            new FilterOperatorRegistry([
                FilterOperator::EQ => '=',
                FilterOperator::NEQ => '!=',
            ])
        );
    }

    public function testForUnknownFilter()
    {
        $filterFactory = $this->getFilterFactory();
        self::assertNull($filterFactory->createFilter('unknown'));
    }

    public function testForFilterWithoutAdditionalParameters()
    {
        $filterType = 'string';

        $filterFactory = $this->getFilterFactory(
            [$filterType => [ComparisonFilter::class, []]]
        );

        $expectedFilter = new ComparisonFilter($filterType);

        self::assertEquals(
            $expectedFilter,
            $filterFactory->createFilter($filterType)
        );
    }

    public function testForFilterWithAdditionalParameters()
    {
        $filterType = 'string';

        $filterFactory = $this->getFilterFactory(
            [$filterType => [ComparisonFilter::class, ['supported_operators' => ['=', '!=']]]]
        );

        $expectedFilter = new ComparisonFilter($filterType);
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);

        self::assertEquals(
            $expectedFilter,
            $filterFactory->createFilter($filterType)
        );
    }

    public function testOverrideParameters()
    {
        $filterType = 'string';

        $filterFactory = $this->getFilterFactory(
            [$filterType => [ComparisonFilter::class, ['supported_operators' => ['=', '!=']]]]
        );

        $expectedFilter = new ComparisonFilter($filterType);
        $expectedFilter->setSupportedOperators([FilterOperator::EQ]);

        self::assertEquals(
            $expectedFilter,
            $filterFactory->createFilter($filterType, ['supported_operators' => ['=']])
        );
    }

    public function testWhenFilterTypeDoesNotEqualToDataType()
    {
        $filterType = 'someFilter';
        $dataType = 'integer';

        $filterFactory = $this->getFilterFactory(
            [$filterType => [ComparisonFilter::class, []]]
        );

        $expectedFilter = new ComparisonFilter($dataType);

        self::assertEquals(
            $expectedFilter,
            $filterFactory->createFilter($filterType, ['data_type' => $dataType])
        );
    }

    public function testWhenFilterShouldBeCreatedByOwnFactory()
    {
        $filterType = 'test';
        $filter = new ComparisonFilter($filterType);

        $filterFactory = $this->getFilterFactory(
            [],
            [$filterType => ['filter1', 'create', []]],
            ['filter1' => new FilterFactoryStub($filter)]
        );

        self::assertSame(
            $filter,
            $filterFactory->createFilter($filterType)
        );
    }
}
