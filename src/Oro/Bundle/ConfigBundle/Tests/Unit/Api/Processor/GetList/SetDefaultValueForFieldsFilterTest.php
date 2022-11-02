<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\GetList;

use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\AddFieldsFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\AddIncludeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetList\SetDefaultValueForFieldsFilter;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class SetDefaultValueForFieldsFilterTest extends GetListProcessorTestCase
{
    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var SetDefaultValueForFieldsFilter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context->getRequestType()->add(RequestType::JSON_API);

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $jsonApiFilterNames = $this->createMock(FilterNames::class);
        $jsonApiFilterNames->expects(self::any())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');
        $jsonApiFilterNames->expects(self::any())
            ->method('getIncludeFilterName')
            ->willReturn('include');
        $defaultFilterNames = $this->createMock(FilterNames::class);
        $defaultFilterNames->expects(self::any())
            ->method('getFieldsFilterTemplate')
            ->willReturn(null);
        $defaultFilterNames->expects(self::any())
            ->method('getIncludeFilterName')
            ->willReturn(null);

        $this->processor = new SetDefaultValueForFieldsFilter(
            new FilterNamesRegistry(
                [
                    ['json_api_filter_names', RequestType::JSON_API],
                    ['default_filter_names', null]
                ],
                TestContainerBuilder::create()
                    ->add('json_api_filter_names', $jsonApiFilterNames)
                    ->add('default_filter_names', $defaultFilterNames)
                    ->getContainer($this),
                new RequestExpressionMatcher()
            ),
            $this->valueNormalizer
        );
    }

    public function testProcessWhenNoFilters()
    {
        $entityClass = ConfigurationSection::class;
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
                        false,
                        $entityType
                    ],
                    [
                        ConfigurationOption::class,
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
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
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('fields[configurationoptions]'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('include'));
    }

    public function testProcessWhenConfigurationSectionFieldsFilterExist()
    {
        $entityClass = ConfigurationSection::class;
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
                        false,
                        $entityType
                    ],
                    [
                        ConfigurationOption::class,
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        false,
                        'configurationoptions'
                    ],
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->getFilters()->add('fields[configuration]', $configurationSectionFieldsFilter, false);
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
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('fields[configuration]'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('fields[configurationoptions]'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('include'));
    }

    public function testProcessWhenConfigurationOptionsFieldsAndIncludeFiltersAlreadyExist()
    {
        $entityClass = ConfigurationSection::class;
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
                        false,
                        $entityType
                    ],
                    [
                        ConfigurationOption::class,
                        DataType::ENTITY_TYPE,
                        $this->context->getRequestType(),
                        false,
                        false,
                        'configurationoptions'
                    ],
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->getFilters()->add('fields[configurationoptions]', $configurationOptionsFieldsFilter, false);
        $this->context->getFilters()->add('include', $includeFilter, false);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                'fields[configurationoptions]' => $configurationOptionsFieldsFilter,
                'include'                      => $includeFilter,
            ],
            iterator_to_array($this->context->getFilters()->getIterator())
        );
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('fields[configurationoptions]'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('include'));
    }

    public function testProcessWhenFieldsAndIncludeFiltersAreNotSupported()
    {
        $entityClass = ConfigurationSection::class;

        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');

        $this->context->getRequestType()->clear();
        $this->context->getRequestType()->add(RequestType::REST);
        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getFilters());
    }
}
