<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueKeyException;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\RegisterDynamicFilters;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\RequestAwareFilterStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\SelfIdentifiableFilterStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RegisterDynamicFiltersTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var RegisterDynamicFilters */
    private $processor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterFactoryInterface */
    private $filterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context->setAction('get_list');

        $this->filterFactory = $this->createMock(FilterFactoryInterface::class);

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getDataFilterGroupName')
            ->willReturn('filter');

        $this->processor = new RegisterDynamicFilters(
            $this->filterFactory,
            $this->doctrineHelper,
            $this->configProvider,
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    private function getRestFilterValueAccessor(Request $request): RestFilterValueAccessor
    {
        return new RestFilterValueAccessor(
            $request,
            '(!|<|>|%21|%3C|%3E)?(=|%3D)|<>|%3C%3E|<|>|\*|%3C|%3E|%2A|(!|%21)?(\*|~|\^|\$|%2A|%7E|%5E|%24)',
            [FilterOperator::EQ => '=', FilterOperator::NEQ => '!=']
        );
    }

    private function getComparisonFilter(string $dataType, bool $isCollection = false): ComparisonFilter
    {
        $filter = new ComparisonFilter($dataType);
        $filter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $filter->setCollection($isCollection);

        return $filter;
    }

    private function getConfig(array $fieldNames = [], array $filterFields = []): Config
    {
        $config = new Config();
        $config->setDefinition($this->getEntityDefinitionConfig($fieldNames));
        $config->setFilters($this->getFiltersConfig($filterFields));

        return $config;
    }

    private function getEntityDefinitionConfig(array $fieldNames = []): EntityDefinitionConfig
    {
        $config = new EntityDefinitionConfig();
        foreach ($fieldNames as $fieldName) {
            $config->addField($fieldName);
        }

        return $config;
    }

    private function getFiltersConfig(array $filterFields = []): FiltersConfig
    {
        $config = new FiltersConfig();
        $config->setExcludeAll();
        foreach ($filterFields as $fieldName => $dataType) {
            $config->addField($fieldName)->setDataType($dataType);
        }

        return $config;
    }

    private function getRequest(string $queryString): Request
    {
        return new Request([], [], [], [], [], ['QUERY_STRING' => $queryString]);
    }

    public function testProcessWhenThisWorkAlreadyDone()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $request = $this->getRequest('filter[name]=val1');

        $this->context->setProcessed(RegisterDynamicFilters::OPERATION_NAME);
        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
        self::assertNull($this->context->getFilterValues()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testProcessWhenPrepareFiltersForDocumentation()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $filter = new ComparisonFilter('string');
        $filter->setField('name');
        $this->context->getFilters()->add('name', $filter);
        $ungroupedFilter = new ComparisonFilter('string');
        $this->context->getFilters()->add('ungrouped', $ungroupedFilter, false);

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setLastGroup(ApiActionGroup::INITIALIZE);
        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($this->getRequest('')));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('name');
        $expectedUngroupedFilter = new ComparisonFilter('string');
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $expectedFilter);
        $expectedFilters->add('ungrouped', $expectedUngroupedFilter, false);

        self::assertEquals($expectedFilters, $this->context->getFilters());
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
        self::assertEquals('filter', $this->context->getFilters()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testProcessForExistingNotGroupedFilter()
    {
        $request = $this->getRequest('filter1=val1');
        $filter = $this->createMock(FilterInterface::class);

        $this->context->setClassName(Entity\Category::class);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('filter1', $filter, false);
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('filter1'));
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
        self::assertEquals('filter', $this->context->getFilters()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testProcessForExistingNotGroupedFilterAndDefaultGroupIsSet()
    {
        $request = $this->getRequest('filter1=val1');
        $filter = $this->createMock(FilterInterface::class);

        $this->context->setClassName(Entity\Category::class);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('filter1', $filter, false);
        $this->context->getFilterValues()->setDefaultGroupName('filter');
        $this->context->getFilters()->setDefaultGroupName('filter');
        $this->processor->process($this->context);

        self::assertSame($filter, $this->context->getFilters()->get('filter1'));
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
        self::assertEquals('filter', $this->context->getFilters()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testProcessForPrimaryEntityFields()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $request = $this->getRequest('filter[name]=val1');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
        self::assertEquals('filter', $this->context->getFilters()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testProcessForPrimaryEntityFieldsAndDefaultGroupIsSet()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $request = $this->getRequest('filter[name]=val1');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilterValues()->setDefaultGroupName('filter');
        $this->context->getFilters()->setDefaultGroupName('filter');
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
        self::assertEquals('filter', $this->context->getFilters()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testProcessForPrimaryEntityFieldsWhenFilterExistsInFilterCollection()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $filter = $this->getComparisonFilter('string');

        $request = $this->getRequest('filter[name]=val1');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('name', $filter);
        $this->processor->process($this->context);

        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $filter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
        self::assertEquals('filter', $this->context->getFilters()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testProcessForPrimaryEntityFieldsWhenFilterExistsInFilterCollectionAndDefaultGroupIsSet()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $filter = $this->getComparisonFilter('string');

        $request = $this->getRequest('filter[name]=val1');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('name', $filter);
        $this->context->getFilterValues()->setDefaultGroupName('filter');
        $this->context->getFilters()->setDefaultGroupName('filter');
        $this->processor->process($this->context);

        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $filter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
        self::assertEquals('filter', $this->context->getFilters()->getDefaultGroupName());
        self::assertTrue($this->context->isProcessed(RegisterDynamicFilters::OPERATION_NAME));
    }

    public function testShouldRemoveNotUsedFiltersForPrimaryEntityFieldsFromFilterCollection()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $idFilter = $this->getComparisonFilter('integer');
        $nameFilter = $this->getComparisonFilter('string');

        $request = $this->getRequest('filter[name]=val1');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('id', $idFilter);
        $this->context->getFilters()->add('name', $nameFilter);
        $this->processor->process($this->context);

        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $nameFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testShouldNotRemoveNotUsedFiltersWithDefaultValueForPrimaryEntityFieldsFromFilterCollection()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $nameFilter = new StandaloneFilterWithDefaultValue('string');

        $request = $this->getRequest('');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('name', $nameFilter);
        $this->processor->process($this->context);

        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $nameFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForUnknownPrimaryEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[label1]=test');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForPrimaryEntityFieldWhichCannotBeUsedForFiltering()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'unsupported']);

        $request = $this->getRequest('filter[label]=test');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForRelatedEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name]=test');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[category.name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRelatedEntityFieldForModelInheritedFromManageableEntity()
    {
        $this->notManageableClassNames = [Entity\UserProfile::class];

        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityConfig->setParentResourceClass(Entity\User::class);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name]=test');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\UserProfile::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[category.name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRelatedEntityFieldWithNotEqualOperator()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name]!=test');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[category.name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForToManyRelatedEntityFieldWithNotEqualOperator()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'groups']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[groups.name]!=test');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('groups.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[groups.name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRelatedEntityFieldWhenAssociationDoesNotExist()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name]=test');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForRelatedEntityFieldWhenAssociationIsRenamed()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name]=test');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[category1.name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
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

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($categoryConfig);

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[category.name1]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
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

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($categoryConfig);

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[category1.name1]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRequestTypeAwareFilter()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name]=test');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $filter = new RequestAwareFilterStub('string');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($filter);

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->processor->process($this->context);

        self::assertSame($this->context->getRequestType(), $filter->getRequestType());
    }

    public function testProcessForSelfIdentifiableFilter()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[target][users]=123');

        $filter = new SelfIdentifiableFilterStub('integer');
        $filter->setFoundFilterKeys(['filter[target.users]']);

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('target', $filter);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->has('filter[target]'));
        self::assertSame($filter, $this->context->getFilters()->get('filter[target.users]'));

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForSelfIdentifiableFilterWhenFilterValueWasNotFound()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[target][users]=123');

        $filter = new SelfIdentifiableFilterStub('integer');
        $filter->setFoundFilterKeys(null);

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('target', $filter);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->has('filter[target]'));

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForSelfIdentifiableFilterWhenInvalidFilterValueKeyExceptionOccurred()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[target][users]=123');

        $filter = new SelfIdentifiableFilterStub('integer');
        $filterValue = FilterValue::createFromSource('filter[target][users]', 'target.users', '123');
        $exception = new InvalidFilterValueKeyException('some error', $filterValue);
        $filter->setFoundFilterKeys($exception);

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('target', $filter);
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setInnerException($exception)
                    ->setSource(ErrorSource::createByParameter('filter[target][users]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForSelfIdentifiableFilterWhenInvalidFilterValueKeyExceptionOccurredNoSourceKey()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[target][users]=123');

        $filter = new SelfIdentifiableFilterStub('integer');
        $filterValue = new FilterValue('target.users', '123');
        $exception = new InvalidFilterValueKeyException('some error', $filterValue);
        $filter->setFoundFilterKeys($exception);

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('target', $filter);
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setInnerException($exception)
                    ->setSource(ErrorSource::createByParameter('filter[target]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForSelfIdentifiableFilterWhenSearchFilterKeyThrowsUnexpectedException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error');

        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[target][users]=123');

        $filter = new SelfIdentifiableFilterStub('integer');
        $exception = new \Exception('some error');
        $filter->setFoundFilterKeys($exception);

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($request));
        $this->context->getFilters()->add('target', $filter);
        $this->processor->process($this->context);
    }
}
