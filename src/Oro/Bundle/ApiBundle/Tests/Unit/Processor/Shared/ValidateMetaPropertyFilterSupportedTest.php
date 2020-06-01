<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateMetaPropertyFilterSupported;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ValidateMetaPropertyFilterSupportedTest extends GetProcessorTestCase
{
    /** @var ValidateMetaPropertyFilterSupported */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getMetaPropertyFilterName')
            ->willReturn('meta');

        $this->processor = new ValidateMetaPropertyFilterSupported(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenNoMetaFilterValue()
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithMetaFilterValue()
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();

        $this->context->setConfig($config);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test'));
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithMetaFilterValueAndDisabledMetaProperties()
    {
        $config = new EntityDefinitionConfig();
        $config->disableMetaProperties();

        $this->context->setConfig($config);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test'));
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER, 'The filter is not supported.')
                    ->setSource(ErrorSource::createByParameter('meta'))
            ],
            $this->context->getErrors()
        );
    }
}
