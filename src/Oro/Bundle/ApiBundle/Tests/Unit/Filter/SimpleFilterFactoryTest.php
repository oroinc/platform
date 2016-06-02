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
        $dataType = 'string';

        $this->filterFactory->addFilter(
            $dataType,
            'Oro\Bundle\ApiBundle\Filter\ComparisonFilter'
        );

        $expectedFilter = new ComparisonFilter($dataType);

        $this->assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($dataType)
        );
    }

    public function testForFilterWithAdditionalParameters()
    {
        $dataType = 'string';
        $supportedOperators = ['=', '!='];

        $this->filterFactory->addFilter(
            $dataType,
            'Oro\Bundle\ApiBundle\Filter\ComparisonFilter',
            ['supported_operators' => $supportedOperators]
        );

        $expectedFilter = new ComparisonFilter($dataType);
        $expectedFilter->setSupportedOperators($supportedOperators);

        $this->assertEquals(
            $expectedFilter,
            $this->filterFactory->createFilter($dataType)
        );
    }
}
