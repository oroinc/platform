<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\AddMetaPropertyFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class AddMetaPropertyFilterTest extends GetProcessorTestCase
{
    /** @var AddMetaPropertyFilter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getMetaPropertyFilterName')
            ->willReturn('meta');

        $this->processor = new AddMetaPropertyFilter(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenMetaFilterAlreadyAdded()
    {
        $filter = new MetaPropertyFilter(DataType::STRING);

        $this->context->getFilters()->add('meta', $filter);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('meta'));
    }

    public function testProcessWhenMetaFilterShouldBeAdded()
    {
        $config = new EntityDefinitionConfig();

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $expectedFilter = new MetaPropertyFilter(DataType::STRING, AddMetaPropertyFilter::FILTER_DESCRIPTION);
        $expectedFilter->setArrayAllowed(true);
        $expectedFilter->addAllowedMetaProperty('title', DataType::STRING);

        self::assertEquals($expectedFilter, $this->context->getFilters()->get('meta'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('meta'));
    }
}
