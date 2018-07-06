<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\AddMetaPropertyFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class AddMetaPropertyFilterTest extends GetProcessorTestCase
{
    /** @var AddMetaPropertyFilter */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AddMetaPropertyFilter();
    }

    public function testProcessWhenMetaFilterAlreadyAdded()
    {
        $filter = new MetaPropertyFilter(DataType::STRING);

        $this->context->getFilters()->add(AddMetaPropertyFilter::FILTER_KEY, $filter);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get(AddMetaPropertyFilter::FILTER_KEY));
    }

    public function testProcessWhenMetaFilterDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->disableMetaProperties();

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->has(AddMetaPropertyFilter::FILTER_KEY));
    }

    public function testProcessWhenMetaFilterShouldBeAdded()
    {
        $config = new EntityDefinitionConfig();

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $expectedFilter = new MetaPropertyFilter(DataType::STRING, AddMetaPropertyFilter::FILTER_DESCRIPTION);
        $expectedFilter->setArrayAllowed(true);

        self::assertEquals($expectedFilter, $this->context->getFilters()->get(AddMetaPropertyFilter::FILTER_KEY));
    }
}
