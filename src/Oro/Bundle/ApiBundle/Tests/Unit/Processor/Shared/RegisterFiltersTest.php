<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
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

    public function testProcessOnEmptyFiltersConfig()
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
    public function testProcessOnExcludedConfigFilters()
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
        $idConfig = new FilterFieldConfig();
        $idConfig->setDataType('integer');
        $idConfig->setDescription('idFieldDescription');
        $idConfig->setExcluded(true);
        $filtersConfig->addField('id', $idConfig);
        $nameConfig = new FilterFieldConfig();
        $nameConfig->setExcluded(true);
        $nameConfig->setDefaultValue('test');
        $nameConfig->setDataType('string');
        $nameConfig->setArrayAllowed(true);
        $nameConfig->setDescription('name field');
        $filtersConfig->addField('name', $nameConfig);

        $this->filterFactory->expects($this->exactly(2))
            ->method('createFilter')
            ->willReturnMap(
                [
                    ['integer', new ComparisonFilter('integer')],
                    ['string', new FieldsFilter('string')],
                ]
            );

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(2, $filters->count());
        /** @var ComparisonFilter $idFilter */
        $idFilter = $filters->get('id');
        $this->assertEquals('integer', $idFilter->getDataType());
        $this->assertFalse($idFilter->isArrayAllowed());
        $this->assertNull($idFilter->getDefaultValue());
        $this->assertEquals('idFieldDescription', $idFilter->getDescription());
        /** @var ComparisonFilter $nameFilter */
        $nameFilter = $filters->get('name');
        $this->assertEquals('string', $nameFilter->getDataType());
        $this->assertTrue($nameFilter->isArrayAllowed());
        $this->assertEquals('test', $nameFilter->getDefaultValue());
        $this->assertEquals('name field', $nameFilter->getDescription());
    }
}
