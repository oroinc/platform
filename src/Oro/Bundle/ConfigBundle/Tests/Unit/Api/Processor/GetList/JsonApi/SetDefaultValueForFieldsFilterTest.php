<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AddFieldsFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AddIncludeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Processor\GetList\JsonApi\SetDefaultValueForFieldsFilter;

class SetDefaultValueForFieldsFilterTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var SetDefaultValueForFieldsFilter */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SetDefaultValueForFieldsFilter($this->valueNormalizer);
    }

    public function testProcessWhenNoFilters()
    {
        $entityClass = 'Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection';
        $entityType = 'configuration';

        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    [
                        $entityClass,
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        $entityType
                    ],
                    [
                        'Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption',
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        'configurationoptions'
                    ],
                ]
            );

        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        $expectedConfigurationOptionsFieldsFilter = new FieldsFilter(
            'string',
            sprintf(AddFieldsFilter::FILTER_DESCRIPTION_TEMPLATE, 'configurationoptions')
        );
        $expectedConfigurationOptionsFieldsFilter->setArrayAllowed(true);
        $expectedIncludeFilter = new IncludeFilter(
            'string',
            AddIncludeFilter::FILTER_DESCRIPTION
        );
        $expectedIncludeFilter->setArrayAllowed(true);
        $this->assertEquals(
            [
                'fields[configurationoptions]' => $expectedConfigurationOptionsFieldsFilter,
                'include'                      => $expectedIncludeFilter,
            ],
            iterator_to_array($this->context->getFilters()->getIterator())
        );
    }

    public function testProcessWhenConfigurationSectionFieldsFilterExist()
    {
        $entityClass = 'Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection';
        $entityType = 'configuration';

        $configurationSectionFieldsFilter = new FieldsFilter(
            'string',
            sprintf(AddFieldsFilter::FILTER_DESCRIPTION_TEMPLATE, 'configuration')
        );
        $configurationSectionFieldsFilter->setArrayAllowed(true);

        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    [
                        $entityClass,
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        $entityType
                    ],
                    [
                        'Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption',
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        'configurationoptions'
                    ],
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->getFilters()->add('fields[configuration]', $configurationSectionFieldsFilter);
        $this->processor->process($this->context);

        $expectedConfigurationSectionFieldsFilter = new FieldsFilter(
            'string',
            sprintf(AddFieldsFilter::FILTER_DESCRIPTION_TEMPLATE, 'configuration')
            . ' To get configuration options use \'id,options\' or \'options\'.'
        );
        $expectedConfigurationSectionFieldsFilter->setArrayAllowed(true);
        $expectedConfigurationSectionFieldsFilter->setDefaultValue('id');
        $expectedConfigurationOptionsFieldsFilter = new FieldsFilter(
            'string',
            sprintf(AddFieldsFilter::FILTER_DESCRIPTION_TEMPLATE, 'configurationoptions')
        );
        $expectedConfigurationOptionsFieldsFilter->setArrayAllowed(true);
        $expectedIncludeFilter = new IncludeFilter(
            'string',
            AddIncludeFilter::FILTER_DESCRIPTION
        );
        $expectedIncludeFilter->setArrayAllowed(true);
        $this->assertEquals(
            [
                'fields[configuration]'        => $expectedConfigurationSectionFieldsFilter,
                'fields[configurationoptions]' => $expectedConfigurationOptionsFieldsFilter,
                'include'                      => $expectedIncludeFilter,
            ],
            iterator_to_array($this->context->getFilters()->getIterator())
        );
    }

    public function testProcessWhenConfigurationOptionsFieldsAndIncludeFiltersAlreadyExist()
    {
        $entityClass = 'Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection';
        $entityType = 'configuration';

        $configurationOptionsFieldsFilter = new FieldsFilter(
            'string',
            'fields filter description'
        );
        $includeFilter = new IncludeFilter(
            'string',
            'include description'
        );

        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    [
                        $entityClass,
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        $entityType
                    ],
                    [
                        'Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption',
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        'configurationoptions'
                    ],
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->getFilters()->add('fields[configurationoptions]', $configurationOptionsFieldsFilter);
        $this->context->getFilters()->add('include', $includeFilter);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                'fields[configurationoptions]' => $configurationOptionsFieldsFilter,
                'include'                      => $includeFilter,
            ],
            iterator_to_array($this->context->getFilters()->getIterator())
        );
    }
}
