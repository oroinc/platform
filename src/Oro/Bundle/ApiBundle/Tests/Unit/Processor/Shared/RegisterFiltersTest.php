<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\RegisterFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class RegisterFiltersTest extends GetListProcessorTestCase
{
    /** @var RegisterFilters */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $filterFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->filterFactory = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface');

        $this->processor = new RegisterFilters($this->filterFactory);
    }

    public function testProcessWithEmptyFiltersConfig()
    {
        $filtersConfig = new FiltersConfig();

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Expected "all" exclusion policy for filters. Got: none.
     */
    public function testProcessWithNotNormalizedFiltersConfig()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeNone();

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filter1Config = new FilterFieldConfig();
        $filter1Config->setDataType('integer');
        $filter1Config->setDescription('filter1 description');
        $filtersConfig->addField('filter1', $filter1Config);

        $filter2Config = new FilterFieldConfig();
        $filter2Config->setDataType('string');
        $filter2Config->setDescription('filter2 description');
        $filter2Config->setArrayAllowed(true);
        $filtersConfig->addField('filter2', $filter2Config);

        $this->filterFactory->expects($this->exactly(2))
            ->method('createFilter')
            ->willReturnMap(
                [
                    ['integer', new ComparisonFilter('integer')],
                    ['string', new SortFilter('string')],
                ]
            );

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(2, $filters->count());
        /** @var ComparisonFilter $filter1 */
        $filter1 = $filters->get('filter1');
        $this->assertEquals('filter1', $filter1->getField());
        $this->assertEquals($filter1Config->getDataType(), $filter1->getDataType());
        $this->assertEquals($filter1->getDescription(), $filter1->getDescription());
        $this->assertFalse($filter1->isArrayAllowed());
        /** @var SortFilter $filter2 */
        $filter2 = $filters->get('filter2');
        $this->assertEquals($filter2Config->getDataType(), $filter2->getDataType());
        $this->assertEquals($filter2->getDescription(), $filter2->getDescription());
        $this->assertTrue($filter2->isArrayAllowed());
    }
}
