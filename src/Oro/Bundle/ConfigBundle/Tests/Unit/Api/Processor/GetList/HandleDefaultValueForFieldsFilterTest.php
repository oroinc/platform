<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Processor\GetList\HandleDefaultValueForFieldsFilter;

class HandleDefaultValueForFieldsFilterTest extends GetListProcessorTestCase
{
    /** @var HandleDefaultValueForFieldsFilter */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new HandleDefaultValueForFieldsFilter();
    }

    public function testProcessWhenFilterFieldsConfigExtraDoesNotExist()
    {
        $this->context->setClassName('Test\Class');
        $this->processor->process($this->context);

        $this->assertEquals(
            new FilterFieldsConfigExtra([$this->context->getClassName() => ['id']]),
            $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testProcessWhenFilterFieldsConfigExtraExists()
    {
        $this->context->setClassName('Test\Class');
        $this->context->addConfigExtra(
            new FilterFieldsConfigExtra([$this->context->getClassName() => ['options']])
        );
        $this->processor->process($this->context);

        $this->assertEquals(
            new FilterFieldsConfigExtra([$this->context->getClassName() => ['options']]),
            $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME)
        );
    }
}
