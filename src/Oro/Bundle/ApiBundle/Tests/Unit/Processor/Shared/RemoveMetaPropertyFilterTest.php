<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveMetaPropertyFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class RemoveMetaPropertyFilterTest extends GetProcessorTestCase
{
    /** @var RemoveMetaPropertyFilter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getMetaPropertyFilterName')
            ->willReturn('meta');

        $this->processor = new RemoveMetaPropertyFilter(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenNoConfig()
    {
        $filter = new MetaPropertyFilter(DataType::STRING);

        $this->context->setConfig(null);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('meta'));
    }

    public function testProcessWhenMetaFilterIsDisabled()
    {
        $filter = new MetaPropertyFilter(DataType::STRING);
        $config = new EntityDefinitionConfig();
        $config->disableMetaProperties();

        $this->context->setConfig($config);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->has('meta'));
    }

    public function testProcessWhenMetaFilterIsEnabled()
    {
        $filter = new MetaPropertyFilter(DataType::STRING);
        $config = new EntityDefinitionConfig();

        $this->context->setConfig($config);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('meta'));
    }
}
