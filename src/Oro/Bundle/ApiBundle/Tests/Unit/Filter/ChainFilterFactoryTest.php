<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ChainFilterFactory;
use Oro\Bundle\ApiBundle\Filter\SimpleFilterFactory;

class ChainFilterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainFilterFactory */
    protected $filterFactory;

    /** @var SimpleFilterFactory */
    protected $simpleFilterFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filterFactory = new ChainFilterFactory();

        $this->simpleFilterFactory = new SimpleFilterFactory();
        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            list($type, $className, $exists) = $filter;
            if ($exists) {
                $this->simpleFilterFactory->addFilter($type, $className);
            }
        }
    }

    public function testAddCreateFilterFactory()
    {
        $this->filterFactory->addFilterFactory($this->simpleFilterFactory);

        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            list($type, , $exists) = $filter;

            if ($exists) {
                $this->assertNotNull($this->filterFactory->createFilter($type));
            } else {
                $this->assertNull($this->filterFactory->createFilter($type));
            }
        }
    }

    /**
     * @return array
     */
    protected function getFilters()
    {
        return [
            ['integer',           'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', true],
            ['unsignedInteger',   'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', true],
            ['string',            'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', true],
            ['boolean',           'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', true],
            ['datetime',          'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', true],
            ['entityAlias',       'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', true],
            ['entityPluralAlias', 'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', true],
            ['doNotExistingOne',  'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', false],
            ['doNotExistingTwo',  'Oro\Bundle\ApiBundle\Filter\ComparisonFilter', false],
        ];
    }
}
