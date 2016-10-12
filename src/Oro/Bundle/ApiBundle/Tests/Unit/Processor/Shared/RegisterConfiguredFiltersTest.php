<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\RegisterConfiguredFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class RegisterConfiguredFiltersTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var RegisterConfiguredFilters */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $filterFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->context->setAction('get_list');

        $this->filterFactory = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface');

        $this->processor = new RegisterConfiguredFilters(
            $this->filterFactory,
            $this->doctrineHelper
        );
    }

    /**
     * @param string $dataType
     *
     * @return ComparisonFilter
     */
    protected function getComparisonFilter($dataType)
    {
        $filter = new ComparisonFilter($dataType);
        $filter->setSupportedOperators(['=', '!=']);

        return $filter;
    }

    public function testProcessWithEmptyFiltersConfig()
    {
        $filtersConfig = new FiltersConfig();

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage Expected "all" exclusion policy for filters. Got: none.
     */
    public function testProcessWithNotNormalizedFiltersConfig()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeNone();

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);
    }

    public function testProcessForComparisonFilterForNotManageableEntity()
    {
        $className = 'Test\Class';
        $this->notManageableClassNames = [$className];

        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('string');
        $filtersConfig->addField('someField', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName($className);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('someField');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('someField', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForManageableEntity()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('string');
        $filtersConfig->addField('someField', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('someField');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('someField', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForFilterWithOptions()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDescription('filter description');
        $filterConfig->setType('someFilter');
        $filterConfig->setOptions(['some_option' => 'val']);
        $filterConfig->setDataType('integer');
        $filterConfig->setPropertyPath('someField');
        $filterConfig->setArrayAllowed();
        $filterConfig->setOperators(['=', '<', '>']);
        $filtersConfig->addField('filter', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('someFilter', ['some_option' => 'val', 'data_type' => 'integer'])
            ->willReturnCallback(
                function ($filterType, array $options) {
                    return $this->getComparisonFilter($options['data_type']);
                }
            );

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setDescription('filter description');
        $expectedFilter->setDataType('integer');
        $expectedFilter->setField('someField');
        $expectedFilter->setArrayAllowed(true);
        $expectedFilter->setSupportedOperators(['=', '<', '>']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToOneAssociation()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('string');
        $filterConfig->setPropertyPath('category');
        $filtersConfig->addField('filter', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToOneAssociationField()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('string');
        $filterConfig->setPropertyPath('category.name');
        $filtersConfig->addField('filter', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToManyAssociation()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('string');
        $filterConfig->setPropertyPath('groups');
        $filtersConfig->addField('filter', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('groups');
        $expectedFilter->setSupportedOperators(['=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToManyAssociationField()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('string');
        $filterConfig->setPropertyPath('groups.name');
        $filtersConfig->addField('filter', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('groups.name');
        $expectedFilter->setSupportedOperators(['=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForSortFilter()
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('string');
        $filtersConfig->addField('sort', $filterConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn(new SortFilter('string'));

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new SortFilter('string');
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('sort', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }
}
