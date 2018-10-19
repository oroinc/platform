<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Filter\SimpleFilterFactory;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class SimpleFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SimpleFilterFactory */
    private $filterFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filterFactory = new SimpleFilterFactory(
            new PropertyAccessor(),
            new FilterOperatorRegistry([
                ComparisonFilter::EQ  => '=',
                ComparisonFilter::NEQ => '!='
            ])
        );
    }

    public function testForUnknownFilter()
    {
        self::assertNull($this->filterFactory->createFilter('unknown'));
    }

    public function testForFilterWithoutAdditionalParameters()
    {
        $filterType = 'string';

        $this->filterFactory->addFilter($filterType, ComparisonFilter::class);

        $expectedFilter = new ComparisonFilter($filterType);

        self::assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($filterType)
        );
    }

    public function testForFilterWithAdditionalParameters()
    {
        $filterType = 'string';

        $this->filterFactory->addFilter(
            $filterType,
            ComparisonFilter::class,
            ['supported_operators' => ['=', '!=']]
        );

        $expectedFilter = new ComparisonFilter($filterType);
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);

        self::assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($filterType)
        );
    }

    public function testOverrideParameters()
    {
        $filterType = 'string';

        $this->filterFactory->addFilter(
            $filterType,
            ComparisonFilter::class,
            ['supported_operators' => ['=', '!=']]
        );

        $expectedFilter = new ComparisonFilter($filterType);
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ]);

        self::assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($filterType, ['supported_operators' => ['=']])
        );
    }

    public function testWhenFilterTypeDoesNotEqualToDataType()
    {
        $filterType = 'someFilter';
        $dataType = 'integer';

        $this->filterFactory->addFilter(
            $filterType,
            ComparisonFilter::class
        );

        $expectedFilter = new ComparisonFilter($dataType);

        self::assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($filterType, ['data_type' => $dataType])
        );
    }

    public function testWhenFilterShouldBeCreatedByOwnFactory()
    {
        $filterType = 'test';
        $filter = new ComparisonFilter($filterType);

        $this->filterFactory->addFilterFactory(
            $filterType,
            new FilterFactoryStub($filter),
            'create'
        );

        self::assertSame(
            $filter,
            $this->filterFactory->createFilter($filterType)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "unknownMethod($dataType)" public method must be declared in the "Oro\Bundle\ApiBundle\Tests\Unit\Filter\FilterFactoryStub" class.
     */
    // @codingStandardsIgnoreEnd
    public function testAddFilterFactoryWhenFactoryMethodDoesNotExist()
    {
        $this->filterFactory->addFilterFactory(
            'test',
            new FilterFactoryStub(),
            'unknownMethod'
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "privateCreate($dataType)" public method must be declared in the "Oro\Bundle\ApiBundle\Tests\Unit\Filter\FilterFactoryStub" class.
     */
    // @codingStandardsIgnoreEnd
    public function testAddFilterFactoryWhenFactoryMethodIsNotPublic()
    {
        $this->filterFactory->addFilterFactory(
            'test',
            new FilterFactoryStub(),
            'privateCreate'
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "createWithoutDataType($dataType)" public method must be declared in the "Oro\Bundle\ApiBundle\Tests\Unit\Filter\FilterFactoryStub" class.
     */
    // @codingStandardsIgnoreEnd
    public function testAddFilterFactoryWhenFactoryMethodHasInvalidSignature()
    {
        $this->filterFactory->addFilterFactory(
            'test',
            new FilterFactoryStub(),
            'createWithoutDataType'
        );
    }
}
