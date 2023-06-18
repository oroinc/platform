<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateMetaPropertyFilterSupported;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ValidateMetaPropertyFilterSupportedTest extends GetProcessorTestCase
{
    private ValidateMetaPropertyFilterSupported $processor;

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

    public function testProcessWhenNoMetaFilterValue(): void
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithMetaFilterValue(): void
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();

        $this->context->setConfig($config);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test'));
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithMetaFilterValueAndDisabledMetaProperties(): void
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

    public function testProcessWithAllowedMetaFilterValue(): void
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();
        $config->disableMetaProperty('test2');

        $configExtra = new MetaPropertiesConfigExtra();
        $configExtra->addMetaProperty('test1', 'string');

        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test1', 'string');
        $filter->addAllowedMetaProperty('test2', 'string');

        $this->context->setConfig($config);
        $this->context->addConfigExtra($configExtra);
        $this->context->getFilters()->add('meta', $filter);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test1'));
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessNoDisabledMetaProperties(): void
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();

        $configExtra = new MetaPropertiesConfigExtra();
        $configExtra->addMetaProperty('test1', 'string');

        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test1', 'string');
        $filter->addAllowedMetaProperty('test2', 'string');

        $this->context->setConfig($config);
        $this->context->addConfigExtra($configExtra);
        $this->context->getFilters()->add('meta', $filter);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test1'));
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenNoMetaPropertiesConfigExtra(): void
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();
        $config->disableMetaProperty('test2');

        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test1', 'string');
        $filter->addAllowedMetaProperty('test2', 'string');

        $this->context->setConfig($config);
        $this->context->getFilters()->add('meta', $filter);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test1'));
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotAllowedMetaFilterValue(): void
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();
        $config->disableMetaProperty('test2');

        $configExtra = new MetaPropertiesConfigExtra();
        $configExtra->addMetaProperty('test2', 'string');

        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test1', 'string');
        $filter->addAllowedMetaProperty('test2', 'string');

        $this->context->setConfig($config);
        $this->context->addConfigExtra($configExtra);
        $this->context->getFilters()->add('meta', $filter);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test2'));
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    'The "test2" is not allowed meta property. Allowed properties: test1.'
                )->setSource(ErrorSource::createByParameter('meta'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithNotAllowedMetaFilterValueWhenNoMetaPropertyFilter(): void
    {
        $config = new EntityDefinitionConfig();
        $config->enableMetaProperties();
        $config->disableMetaProperty('test2');

        $configExtra = new MetaPropertiesConfigExtra();
        $configExtra->addMetaProperty('test2', 'string');

        $this->context->setConfig($config);
        $this->context->addConfigExtra($configExtra);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test2'));
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    'The "test2" is not allowed meta property. Allowed properties: .'
                )->setSource(ErrorSource::createByParameter('meta'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithNotAllowedMetaFilterValueAndDisabledMetaProperties(): void
    {
        $config = new EntityDefinitionConfig();
        $config->disableMetaProperties();
        $config->disableMetaProperty('test2');

        $configExtra = new MetaPropertiesConfigExtra();
        $configExtra->addMetaProperty('test2', 'string');

        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test1', 'string');
        $filter->addAllowedMetaProperty('test2', 'string');

        $this->context->setConfig($config);
        $this->context->addConfigExtra($configExtra);
        $this->context->getFilters()->add('meta', $filter);
        $this->context->getFilterValues()->set('meta', new FilterValue('meta', 'test2'));
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
