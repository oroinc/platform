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
    /** @var FilterNames|\PHPUnit\Framework\MockObject\MockObject */
    private $filterNames;

    /** @var RemoveMetaPropertyFilter */
    private $processor;

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

    public function testProcessWhenNoConfig()
    {
        $filter = new MetaPropertyFilter(DataType::STRING);

        $this->filterNames->expects(self::never())
            ->method('getMetaPropertyFilterName');

        $this->context->setConfig(null);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('meta'));
    }

    public function testProcessWhenMetaFilterIsNotSupported()
    {
        $filter = new MetaPropertyFilter(DataType::STRING);
        $config = new EntityDefinitionConfig();
        $config->disableMetaProperties();

        $this->filterNames->expects(self::once())
            ->method('getMetaPropertyFilterName')
            ->willReturn('');

        $this->context->setConfig($config);
        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertTrue($this->context->getFilters()->has('meta'));
    }

    public function testProcessWhenMetaFilterIsDisabled()
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

    public function testProcessWhenMetaFilterIsEnabled()
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
