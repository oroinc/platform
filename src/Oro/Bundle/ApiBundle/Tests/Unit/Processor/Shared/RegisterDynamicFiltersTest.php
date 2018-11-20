<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\InvalidFilterValueKeyException;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\RegisterDynamicFilters;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\RequestAwareFilterStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\SelfIdentifiableFilterStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\HttpFoundation\Request;

class RegisterDynamicFiltersTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var RegisterDynamicFilters */
    private $processor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterFactoryInterface */
    private $filterFactory;

    protected function setUp()
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
            new FilterNamesRegistry([[$filterNames, null]], new RequestExpressionMatcher())
        );
    }

    /**
     * @param Request $request
     *
     * @return RestFilterValueAccessor
     */
    private function getRestFilterValueAccessor(Request $request)
    {
        return new RestFilterValueAccessor(
            $request,
            '!?=',
            [ComparisonFilter::EQ => '=', ComparisonFilter::NEQ => '!=']
        );
    }

    /**
     * @param string $dataType
     * @param bool   $isCollection
     *
     * @return ComparisonFilter
     */
    private function getComparisonFilter($dataType, $isCollection = false)
    {
        $filter = new ComparisonFilter($dataType);
        $filter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
        $filter->setCollection($isCollection);

        return $filter;
    }

    /**
     * @param string[] $fields
     * @param array    $filterFields
     *
     * @return Config
     */
    private function getConfig(array $fields = [], array $filterFields = [])
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
    private function getEntityDefinitionConfig(array $fields = [])
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
    private function getFiltersConfig(array $filterFields = [])
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
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getRequest($queryString)
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('getQueryString')
            ->willReturn($queryString);

        return $request;
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

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setLastGroup('initialize');
        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues($this->getRestFilterValueAccessor($this->getRequest('')));
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('name');
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->setDefaultGroupName('filter');
        $expectedFilters->add('filter[name]', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
        self::assertEquals('filter', $this->context->getFilterValues()->getDefaultGroupName());
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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
        $expectedFilter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage some error
     */
    public function testProcessForSelfIdentifiableFilterWhenSearchFilterKeyThrowsUnexpectedException()
    {
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
