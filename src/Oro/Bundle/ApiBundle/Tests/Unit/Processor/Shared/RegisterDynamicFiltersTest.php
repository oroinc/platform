<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\RegisterDynamicFilters;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class RegisterDynamicFiltersTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var RegisterDynamicFilters */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $filterFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->context->setAction('get_list');

        $this->filterFactory = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface');

        $this->processor = new RegisterDynamicFilters(
            $this->filterFactory,
            $this->doctrineHelper,
            $this->configProvider
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

    /**
     * @param string[] $fields
     * @param array    $filterFields
     *
     * @return Config
     */
    protected function getConfig(array $fields = [], array $filterFields = [])
    {
        $config = new Config();
        $config->setDefinition($this->getEntityDefinitionConfig($fields));
        $config->setFilters($this->getFiltersConfig($filterFields));

        return $config;
    }

    /**
     * @param string[] $fields
     *
     * @return EntityDefinitionConfig
     */
    protected function getEntityDefinitionConfig(array $fields = [])
    {
        $config = new EntityDefinitionConfig();
        foreach ($fields as $field) {
            $config->addField($field);
        }

        return $config;
    }

    /**
     * @param array $filterFields
     *
     * @return FiltersConfig
     */
    protected function getFiltersConfig(array $filterFields = [])
    {
        $config = new FiltersConfig();
        $config->setExcludeAll();
        foreach ($filterFields as $field => $dataType) {
            $config->addField($field)->setDataType($dataType);
        }

        return $config;
    }

    /**
     * @param $queryString
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequest($queryString)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn($queryString);

        return $request;
    }

    public function testProcessForPrimaryEntityFields()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $request = $this->getRequest('filter[name]=val1');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('name');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter[name]', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForUnknownPrimaryEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[label1]=test');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getFilters());
        $this->assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Filter "%s" is not supported.', 'filter[label1]')
                )->setSource(ErrorSource::createByParameter('filter[label1]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForPrimaryEntityFieldWhichCannotBeUsedForFiltering()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'unsupported']);

        $request = $this->getRequest('filter[label]=test');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getFilters());
        $this->assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Filter "%s" is not supported.', 'filter[label]')
                )->setSource(ErrorSource::createByParameter('filter[label]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForRelatedEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name]=test');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter[category.name]', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRelatedEntityFieldWithNotEqualOperator()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name]!=test');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter[category.name]', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForToManyRelatedEntityFieldWithNotEqualOperator()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'groups']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[groups.name]!=test');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('groups.name');
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter[groups.name]', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRelatedEntityFieldWhenAssociationDoesNotExist()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name]=test');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getFilters());
        $this->assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Filter "%s" is not supported.', 'filter[category1.name]')
                )->setSource(ErrorSource::createByParameter('filter[category1.name]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForRelatedEntityFieldWhenAssociationIsRenamed()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name]=test');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter[category1.name]', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRenamedRelatedEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name1]=test');

        $categoryConfig = $this->getConfig(
            ['name1'],
            ['name1' => 'string']
        );
        $categoryConfig->getDefinition()->getField('name1')->setPropertyPath('name');
        $categoryConfig->getFilters()->getField('name1')->setPropertyPath('name');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($categoryConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter[category.name1]', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRenamedAssociationAndRenamedRelatedEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name1]=test');

        $categoryConfig = $this->getConfig(
            ['name1'],
            ['name1' => 'string']
        );
        $categoryConfig->getDefinition()->getField('name1')->setPropertyPath('name');
        $categoryConfig->getFilters()->getField('name1')->setPropertyPath('name');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($categoryConfig);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators(['=', '!=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter[category1.name1]', $expectedFilter);

        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }
}
