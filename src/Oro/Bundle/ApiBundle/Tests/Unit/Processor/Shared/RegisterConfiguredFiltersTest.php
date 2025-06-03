<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\CompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\RegisterConfiguredFilters;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\RequestAwareFilterStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RegisterConfiguredFiltersTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var FilterFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filterFactory;

    /** @var RegisterConfiguredFilters */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->context->setAction('get_list');

        $this->filterFactory = $this->createMock(FilterFactoryInterface::class);

        $this->processor = new RegisterConfiguredFilters(
            $this->filterFactory,
            $this->doctrineHelper
        );
    }

    private function getComparisonFilter(string $dataType, bool $isCollection = false): ComparisonFilter
    {
        $filter = new ComparisonFilter($dataType);
        $filter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $filter->setCollection($isCollection);

        return $filter;
    }

    private function createFiltersConfig(?FilterFieldConfig $filter = null, string $fieldName = 'filter'): FiltersConfig
    {
        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();
        if ($filter) {
            $filtersConfig->addField($fieldName, $filter);
        }

        return $filtersConfig;
    }

    private function createFilterConfig(
        string $dataType,
        string $propertyPath = '',
        bool $isCollection = false,
        string $description = '',
        string $type = '',
        array $options = [],
        bool $arrayAllowed = false,
        array $operators = [],
        bool $rangeAllowed = false
    ): FilterFieldConfig {
        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType($dataType);
        $filterConfig->setIsCollection($isCollection);
        $filterConfig->setPropertyPath($propertyPath);
        $filterConfig->setDescription($description);
        $filterConfig->setType($type);
        $filterConfig->setOptions($options);
        $filterConfig->setOperators($operators);
        $filterConfig->setArrayAllowed($arrayAllowed);
        $filterConfig->setRangeAllowed($rangeAllowed);

        return $filterConfig;
    }

    public function testProcessWithEmptyFiltersConfig(): void
    {
        $filtersConfig = new FiltersConfig();

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);
    }

    public function testProcessWhenFilterCreationFailed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The filter "someField" for "%s" cannot be created.',
            Entity\Category::class
        ));

        $exception = new \TypeError('some error');
        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willThrowException($exception);

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters(
            $this->createFiltersConfig($this->createFilterConfig(DataType::STRING), 'someField')
        );
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $this->context->getFilters();
    }

    public function testProcessWhenFilterCreationFailedForNotManageableEntity(): void
    {
        $this->notManageableClassNames = [Entity\Category::class];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The filter "someField" for "%s" cannot be created.',
            Entity\Category::class
        ));

        $exception = new \TypeError('some error');
        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willThrowException($exception);

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters(
            $this->createFiltersConfig($this->createFilterConfig(DataType::STRING), 'someField')
        );
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $this->context->getFilters();
    }

    public function testProcessForComparisonFilterForNotManageableEntity(): void
    {
        $className = 'Test\Class';
        $this->notManageableClassNames = [$className];

        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING), 'someField');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName($className);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('someField');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('someField', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForManageableEntity(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING), 'someField');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('someField');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('someField', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForFilterWithOptions(): void
    {
        $filterConfig = $this->createFilterConfig(
            DataType::INTEGER,
            'someField',
            false,
            'filter description',
            'someFilter',
            ['some_option' => 'val'],
            true,
            [FilterOperator::EQ, '<', '>']
        );
        $filtersConfig = $this->createFiltersConfig($filterConfig);

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('someFilter', ['some_option' => 'val', 'data_type' => 'integer'])
            ->willReturnCallback(function ($filterType, array $options) {
                return $this->getComparisonFilter($options['data_type']);
            });

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setDescription('filter description');
        $expectedFilter->setDataType('integer');
        $expectedFilter->setField('someField');
        $expectedFilter->setArrayAllowed(true);
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, '<', '>']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForFieldAwareFilterWhenFieldConfiguredExplicitly(): void
    {
        $filterConfig = $this->createFilterConfig(
            DataType::INTEGER,
            'someField',
            false,
            'filter description',
            'someFilter',
            ['field' => 'configuredField']
        );
        $filtersConfig = $this->createFiltersConfig($filterConfig);

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('someFilter', ['field' => 'configuredField', 'data_type' => 'integer'])
            ->willReturnCallback(function ($filterType, array $options) {
                $filter = $this->getComparisonFilter($options['data_type']);
                $filter->setField($options['field']);

                return $filter;
            });

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setDescription('filter description');
        $expectedFilter->setDataType('integer');
        $expectedFilter->setField('configuredField');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForFieldAwareFilterForComputedField(): void
    {
        $filterConfig = $this->createFilterConfig(
            DataType::INTEGER,
            ConfigUtil::IGNORE_PROPERTY_PATH,
            false,
            'filter description',
            'someFilter'
        );
        $filtersConfig = $this->createFiltersConfig($filterConfig, 'filterForComputedField');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('someFilter', ['data_type' => 'integer'])
            ->willReturnCallback(function ($filterType, array $options) {
                return $this->getComparisonFilter($options['data_type']);
            });

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setDescription('filter description');
        $expectedFilter->setDataType('integer');
        $expectedFilter->setField('filterForComputedField');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filterForComputedField', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterWithAttributesInitializedInFactory(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(
            DataType::STRING,
            '',
            false,
            '',
            'someFilter',
            [],
            true,
            [],
            true
        ));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('someFilter', ['data_type' => 'string'])
            ->willReturnCallback(function ($filterType, array $options) {
                $filter = $this->getComparisonFilter($options['data_type']);
                $filter->setDescription('default filter description');
                $filter->setArrayAllowed(true);
                $filter->setRangeAllowed(true);
                $filter->setSupportedOperators(['=']);

                return $filter;
            });

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setDescription('default filter description');
        $expectedFilter->setDataType('string');
        $expectedFilter->setField('filter');
        $expectedFilter->setArrayAllowed(true);
        $expectedFilter->setRangeAllowed(true);
        $expectedFilter->setSupportedOperators(['=']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterWithAttributesInitializedInFactoryAndOverriddenInConfig(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(
            DataType::STRING,
            '',
            false,
            'filter description',
            'someFilter',
            [],
            false,
            ['=', '!=', '~']
        ));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('someFilter', ['data_type' => 'string'])
            ->willReturnCallback(function ($filterType, array $options) {
                $filter = $this->getComparisonFilter($options['data_type']);
                $filter->setDescription('default filter description');
                $filter->setArrayAllowed(true);
                $filter->setRangeAllowed(true);
                $filter->setSupportedOperators(['=']);

                return $filter;
            });

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setDescription('filter description');
        $expectedFilter->setDataType('string');
        $expectedFilter->setField('filter');
        $expectedFilter->setArrayAllowed(false);
        $expectedFilter->setRangeAllowed(false);
        $expectedFilter->setSupportedOperators(['=', '!=', '~']);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToOneAssociation(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING, 'category'));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToOneAssociationField(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING, 'category.name'));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.name');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToOneAssociationWithConfiguredOperators(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(
            DataType::STRING,
            'category',
            false,
            '',
            '',
            [],
            false,
            [FilterOperator::EQ, FilterOperator::GT]
        ));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category');
        $expectedFilter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::EXISTS,
            FilterOperator::NEQ_OR_NULL
        ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToManyAssociation(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING, 'groups', true));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string', true));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setCollection(true);
        $expectedFilter->setField('groups');
        $expectedFilter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::CONTAINS,
            FilterOperator::NOT_CONTAINS
        ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToManyAssociationField(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(
            DataType::STRING,
            'groups.name',
            false,
            '',
            '',
            [],
            false,
            [
                FilterOperator::EQ,
                FilterOperator::NEQ,
                FilterOperator::CONTAINS,
                FilterOperator::NOT_CONTAINS
            ]
        ));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string', true));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setCollection(true);
        $expectedFilter->setField('groups.name');
        $expectedFilter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::CONTAINS,
            FilterOperator::NOT_CONTAINS
        ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToManyAssociationWithConfiguredOperators(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(
            DataType::STRING,
            'groups',
            true,
            '',
            '',
            [],
            false,
            [FilterOperator::EQ, FilterOperator::GT]
        ));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string', true));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setCollection(true);
        $expectedFilter->setField('groups');
        $expectedFilter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::EXISTS,
            FilterOperator::NEQ_OR_NULL,
            FilterOperator::CONTAINS,
            FilterOperator::NOT_CONTAINS
        ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToOneAssociationFieldForModelInheritedFromManageableEntity(): void
    {
        $this->notManageableClassNames = [Entity\UserProfile::class];

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass(Entity\User::class);

        $filtersConfig = new FiltersConfig();
        $filtersConfig->setExcludeAll();

        $filterConfig = new FilterFieldConfig();
        $filterConfig->setDataType('integer');
        $filterConfig->setOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::GT,
            FilterOperator::LT
        ]);
        $filtersConfig->addField('owner', $filterConfig);

        $existingAssociationFilter = new ComparisonFilter('string');
        $existingAssociationFilter->setDataType('integer');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('integer', [])
            ->willReturn($this->getComparisonFilter('integer'));

        $this->context->setClassName(Entity\UserProfile::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('integer');
        $expectedFilter->setField('owner');
        $expectedFilter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::EXISTS,
            FilterOperator::NEQ_OR_NULL
        ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('owner', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForSortFilter(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING), 'sort');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn(new SortFilter('string'));

        $this->context->setClassName(Entity\Category::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);

        $expectedFilter = new SortFilter('string');
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('sort', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForRequestTypeAwareFilter(): void
    {
        $className = 'Test\Class';
        $this->notManageableClassNames = [$className];

        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING), 'someField');

        $filter = new RequestAwareFilterStub('string');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($filter);

        $this->context->setClassName($className);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertSame($this->context->getRequestType(), $filter->getRequestType());
    }

    public function testProcessForComparisonFilterForAssociationWithCustomIdField(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING, 'category'));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with(DataType::STRING, [])
            ->willReturn($this->getComparisonFilter(DataType::STRING));

        $rootConfig = new EntityDefinitionConfig();
        $rootConfig->setIdentifierFieldNames(['id']);
        $fieldConfig = $rootConfig->addField('category');
        $fieldConfig->setTargetClass(Entity\Category::class);
        $targetConfig = $fieldConfig->createAndSetTargetEntity();
        $targetConfig->setIdentifierFieldNames(['id']);
        $targetConfig->addField('id')->setPropertyPath('label');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig($rootConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter(DataType::STRING);
        $expectedFilter->setCollection(false);
        $expectedFilter->setField('category.label');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForRenamedAssociationWithCustomIdField(): void
    {
        $filterConfig = $this->createFilterConfig(DataType::STRING, 'category');
        $filtersConfig = $this->createFiltersConfig($filterConfig, 'renamedCategory');

        $rootConfig = new EntityDefinitionConfig();
        $rootConfig->setIdentifierFieldNames(['id']);
        $fieldConfig = $rootConfig->addField('renamedCategory');
        $fieldConfig->setPropertyPath('category');
        $fieldConfig->setTargetClass(Entity\Category::class);
        $targetConfig = $fieldConfig->createAndSetTargetEntity();
        $targetConfig->setIdentifierFieldNames(['id']);
        $targetConfig->addField('id')->setPropertyPath('label');

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with('string', [])
            ->willReturn($this->getComparisonFilter('string'));

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig($rootConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter('string');
        $expectedFilter->setField('category.label');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('renamedCategory', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForAssociationWithConfiguredCustomIdFieldEqualsToPrimaryKey(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING, 'category'));

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with(DataType::STRING, [])
            ->willReturn($this->getComparisonFilter(DataType::STRING));

        $rootConfig = new EntityDefinitionConfig();
        $rootConfig->setIdentifierFieldNames(['id']);
        $fieldConfig = $rootConfig->addField('category');
        $fieldConfig->setTargetClass(Entity\Category::class);
        $targetConfig = $fieldConfig->createAndSetTargetEntity();
        $targetConfig->setIdentifierFieldNames(['id']);
        $targetConfig->addField('id')->setPropertyPath('name');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig($rootConfig);
        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter(DataType::STRING);
        $expectedFilter->setCollection(false);
        $expectedFilter->setField('category');
        $expectedFilter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForConfigAwareAndMetadataAwareFilters(): void
    {
        $className = Entity\User::class;

        $filtersConfig = $this->createFiltersConfig();
        $filtersConfig->addField('filter1', $this->createFilterConfig(DataType::STRING, 'association1'));
        $filtersConfig->addField('filter2', $this->createFilterConfig(DataType::STRING, 'association2'));

        $configAwareFilter = new ExtendedAssociationFilter(DataType::STRING);
        $metadataAwareFilter = new CompositeIdentifierFilter(DataType::STRING);

        $this->filterFactory->expects(self::exactly(2))
            ->method('createFilter')
            ->with(DataType::STRING, [])
            ->willReturnOnConsecutiveCalls($configAwareFilter, $metadataAwareFilter);

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->createAndSetTargetEntity();
        $config->addField('association2')->createAndSetTargetEntity();

        $metadata = new EntityMetadata($className);
        $metadata->addAssociation(new AssociationMetadata('association1'))
            ->setTargetMetadata($this->createMock(EntityMetadata::class));
        $metadata->addAssociation(new AssociationMetadata('association2'))
            ->setTargetMetadata($this->createMock(EntityMetadata::class));

        $this->context->setClassName($className);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedConfigAwareFilter = new ExtendedAssociationFilter(DataType::STRING);
        $expectedConfigAwareFilter->setRequestType($this->context->getRequestType());
        $expectedConfigAwareFilter->setField('association1');
        $expectedConfigAwareFilter->setConfig($config);

        $expectedMetadataAwareFilter = new CompositeIdentifierFilter(DataType::STRING);
        $expectedMetadataAwareFilter->setRequestType($this->context->getRequestType());
        $expectedMetadataAwareFilter->setMetadata($metadata);

        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter1', $expectedConfigAwareFilter);
        $expectedFilters->add('filter2', $expectedMetadataAwareFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }
}
