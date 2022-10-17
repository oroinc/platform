<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\RegisterConfiguredFilters;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\RequestAwareFilterStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

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

    public function testProcessWithEmptyFiltersConfig(): void
    {
        $filtersConfig = new FiltersConfig();

        $this->filterFactory->expects(self::never())
            ->method('createFilter');

        $this->context->setConfigOfFilters($filtersConfig);
        $this->processor->process($this->context);
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
        $filtersConfig = $this->createFiltersConfig($filterConfig, 'filter');

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
            ['=', '!=', '~'],
            false
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
        $filtersConfig = $this->createFiltersConfig(
            $this->createFilterConfig(DataType::STRING, 'category')
        );

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
            FilterOperator::NEQ
        ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForComparisonFilterForToOneAssociationField(): void
    {
        $filtersConfig = $this->createFiltersConfig(
            $this->createFilterConfig(DataType::STRING, 'category.name')
        );

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
        $expectedFilter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ
        ]);
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
            [FilterOperator::EQ, FilterOperator::GT],
            false
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

    public function testProcessForComparisonFilterForToManyAssociationCustomIdentifierField(): void
    {
        $filtersConfig = $this->createFiltersConfig(
            $this->createFilterConfig(DataType::STRING, 'groups', false)
        );

        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with(DataType::STRING, [])
            ->willReturn($this->getComparisonFilter(DataType::STRING, false));

        $rootConfigs = $this->createMock(EntityDefinitionConfig::class);
        $fieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $targetConfigs = $this->createMock(EntityDefinitionConfig::class);
        $targetFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $rootConfigs->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootConfigs->expects(self::once())
            ->method('hasField')
            ->with('groups')
            ->willReturn(true);
        $rootConfigs->expects(self::exactly(2))
            ->method('getField')
            ->with('groups')
            ->willReturn($fieldConfig);
        $fieldConfig->expects(self::exactly(3))
            ->method('getTargetEntity')
            ->willReturn($targetConfigs);
        $targetConfigs->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $targetConfigs->expects(self::once())
            ->method('getField')
            ->with('id')
            ->willReturn($targetFieldConfig);
        $targetFieldConfig->expects(self::once())
            ->method('getPropertyPath')
            ->willReturn('newIdentifier');

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig($rootConfigs);

        $this->processor->process($this->context);

        $expectedFilter = new ComparisonFilter(DataType::STRING);
        $expectedFilter->setCollection(false);
        $expectedFilter->setField('groups.newIdentifier');
        $expectedFilter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ
        ]);
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
    }

    public function testProcessForMetadataFieldAwareFilterForToManyAssociationField(): void
    {
        $filtersConfig = $this->createFiltersConfig($this->createFilterConfig(DataType::STRING, 'groups', false));
        $filter = new AssociationCompositeIdentifierFilter(DataType::STRING);
        $registry = $this->createMock(EntityIdTransformerRegistry::class);
        $filter->setEntityIdTransformerRegistry($registry);
        $this->filterFactory->expects(self::once())
            ->method('createFilter')
            ->with(DataType::STRING, [])
            ->willReturn($filter);

        $metadata = $this->createMock(EntityMetadata::class);
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $targetMetadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with('groups')
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociation')
            ->with('groups')
            ->willReturn($associationMetadata);
        $associationMetadata->expects(self::once())
            ->method('getTargetMetadata')
            ->willReturn($targetMetadata);

        $this->context->setClassName(Entity\User::class);
        $this->context->setConfigOfFilters($filtersConfig);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedFilter = new AssociationCompositeIdentifierFilter(DataType::STRING);
        $expectedFilter->setEntityIdTransformerRegistry($registry);
        $expectedFilter->setField('groups');
        $expectedFilter->setMetadata($targetMetadata);
        $expectedFilter->setSupportedOperators([FilterOperator::EQ]);
        $expectedFilter->setRequestType($this->context->getRequestType());
        $expectedFilters = new FilterCollection();
        $expectedFilters->add('filter', $expectedFilter);

        self::assertEquals($expectedFilters, $this->context->getFilters());
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
}
