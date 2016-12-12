<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\SimpleFilterFactory;

class SimpleFilterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var SimpleFilterFactory */
    protected $filterFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filterFactory = new SimpleFilterFactory(new PropertyAccessor());
    }

    public function testForUnknownFilter()
    {
        $this->assertNull($this->filterFactory->createFilter('unknown'));
    }

    public function testForFilterWithoutAdditionalParameters()
    {
        $filterType = 'string';

        $this->filterFactory->addFilter(
            $filterType,
            'Oro\Bundle\ApiBundle\Filter\ComparisonFilter'
        );

        $expectedFilter = new ComparisonFilter($filterType);

        $this->assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($filterType)
        );
    }

    public function testForFilterWithAdditionalParameters()
    {
        $filterType = 'string';
        $supportedOperators = ['=', '!='];

        $this->filterFactory->addFilter(
            $filterType,
            'Oro\Bundle\ApiBundle\Filter\ComparisonFilter',
            ['supported_operators' => $supportedOperators]
        );

        $expectedFilter = new ComparisonFilter($filterType);
        $expectedFilter->setSupportedOperators($supportedOperators);

        $this->assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($filterType)
        );
    }

    public function testOverrideParameters()
    {
        $filterType = 'string';

        $this->filterFactory->addFilter(
            $filterType,
            'Oro\Bundle\ApiBundle\Filter\ComparisonFilter',
            ['supported_operators' => ['=', '!=']]
        );

        $expectedFilter = new ComparisonFilter($filterType);
        $expectedFilter->setSupportedOperators(['=']);

        $this->assertEquals(
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
            'Oro\Bundle\ApiBundle\Filter\ComparisonFilter'
        );

        $expectedFilter = new ComparisonFilter($dataType);

        $this->assertEquals(
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

        $this->assertSame(
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
