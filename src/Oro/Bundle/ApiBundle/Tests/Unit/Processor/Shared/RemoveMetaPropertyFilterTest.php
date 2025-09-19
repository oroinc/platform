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
use PHPUnit\Framework\MockObject\MockObject;

class RemoveMetaPropertyFilterTest extends GetProcessorTestCase
{
    private FilterNames&MockObject $filterNames;
    private RemoveMetaPropertyFilter $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->filterNames = $this->createMock(FilterNames::class);

        $this->processor = new RemoveMetaPropertyFilter(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $this->filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenNoConfig(): void
    {
        $filter = new MetaPropertyFilter(DataType::STRING);

        $this->filterNames->expects(self::never())
            ->method('getMetaPropertyFilterName');

        $this->context->setConfig(null);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('meta'));
    }

    public function testProcessWhenMetaFilterIsNotSupported(): void
    {
        $filter = new MetaPropertyFilter(DataType::STRING);
        $config = new EntityDefinitionConfig();
        $config->disableMetaProperties();

        $this->filterNames->expects(self::once())
            ->method('getMetaPropertyFilterName')
            ->willReturn(null);

        $this->context->setConfig($config);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertTrue($this->context->getFilters()->has('meta'));
    }

    public function testProcessWhenMetaFilterIsDisabled(): void
    {
        $filter = new MetaPropertyFilter(DataType::STRING);
        $config = new EntityDefinitionConfig();
        $config->disableMetaProperties();

        $this->filterNames->expects(self::once())
            ->method('getMetaPropertyFilterName')
            ->willReturn('meta');

        $this->context->setConfig($config);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->has('meta'));
    }

    public function testProcessWhenMetaFilterIsEnabled(): void
    {
        $filter = new MetaPropertyFilter(DataType::STRING);
        $config = new EntityDefinitionConfig();

        $this->filterNames->expects(self::never())
            ->method('getMetaPropertyFilterName');

        $this->context->setConfig($config);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('meta'));
    }
}
