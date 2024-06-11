<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\Provider\AssociationSortersProvider;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateSorting;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ValidateSortingTest extends GetListProcessorOrmRelatedTestCase
{
    private ValidateSorting $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context->setAction(ApiAction::GET_LIST);

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->processor = new ValidateSorting(
            $this->doctrineHelper,
            new AssociationSortersProvider($this->doctrineHelper, $this->configProvider),
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    private function getConfig(array $fieldNames = [], array $sortFieldNames = []): Config
    {
        $config = new Config();
        $config->setDefinition($this->getEntityDefinitionConfig($fieldNames));
        $config->setSorters($this->getSortersConfig($sortFieldNames));

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

    private function getSortersConfig(array $fieldNames = []): SortersConfig
    {
        $config = new SortersConfig();
        foreach ($fieldNames as $fieldName) {
            $config->addField($fieldName);
        }

        return $config;
    }

    private function prepareFilters(string $sortBy): void
    {
        $filterValueAccessor = $this->context->getFilterValues();
        $filterValueAccessor->set('sort', new FilterValue('sort', $sortBy));

        // emulate sort normalizer
        $orderBy = [];
        $items = explode(',', $sortBy);
        foreach ($items as $item) {
            $item = trim($item);
            if (str_starts_with($item, '-')) {
                $orderBy[substr($item, 1)] = 'DESC';
            } else {
                $orderBy[$item] = 'ASC';
            }
        }
        $filterValueAccessor->getOne('sort')->setValue($orderBy);

        $this->context->setFilterValues($filterValueAccessor);
        $this->context->getFilters()->add('sort', new SortFilter(DataType::ORDER_BY));
    }

    public function testProcessWhenQueryIsAlreadyBuilt(): void
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertSame($query, $this->context->getQuery());
    }

    public function testProcessWhenSortByExcludedFieldRequested(): void
    {
        $sortersConfig = $this->getSortersConfig(['id']);
        $sortersConfig->getField('id')->setExcluded();

        $this->prepareFilters('-id');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByExcludedFieldRequestedAndSortFilterHasSourceKey(): void
    {
        $sortersConfig = $this->getSortersConfig(['id']);
        $sortersConfig->getField('id')->setExcluded();

        $this->prepareFilters('-id');
        $sortFilterValue = $this->context->getFilterValues()->getOne('sort');
        $sortFilterValue->setSource(
            FilterValue::createFromSource('sortFilterSourceKey', $sortFilterValue->getPath(), '')
        );

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sortFilterSourceKey'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenNoSorters(): void
    {
        $sortersConfig = $this->getSortersConfig();

        $this->prepareFilters('-id');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByNotAllowedFieldRequested(): void
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded();

        $this->prepareFilters('-id');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortBySeveralNotAllowedFieldRequested(): void
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded();

        $this->prepareFilters('id,-label');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "id, label" fields are not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByAllowedFieldRequested(): void
    {
        $sortersConfig = $this->getSortersConfig(['id']);

        $this->prepareFilters('-id');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByAllowedRenamedFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name1']);
        $primaryEntityConfig->getField('name1')->setPropertyPath('name');
        $primarySortersConfig = $this->getSortersConfig(['name1']);
        $primarySortersConfig->getField('name1')->setPropertyPath('name');

        $this->prepareFilters('name1');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfSorters($primarySortersConfig);

        $this->processor->process($this->context);

        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByAllowedAssociationFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareFilters('category.name');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                Category::class,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByAllowedRenamedAssociationRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareFilters('category1.name');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                Category::class,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByAllowedRenamedAssociationAndRenamedRelatedFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');

        $categoryConfig = $this->getConfig(['name1'], ['name1']);
        $categoryConfig->getDefinition()->getField('name1')->setPropertyPath('name');
        $categoryConfig->getSorters()->getField('name1')->setPropertyPath('name');

        $this->prepareFilters('category1.name1');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                Category::class,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByAllowedAssociationFieldRequestedForModelInheritedFromManageableEntity(): void
    {
        $this->notManageableClassNames = [UserProfile::class];

        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareFilters('category.name');

        $this->context->setClassName(UserProfile::class);
        $this->context->setConfig($primaryEntityConfig);
        $primaryEntityConfig->setParentResourceClass(User::class);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                Category::class,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByNotAllowedAssociationFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['id', 'name'], ['id']);

        $this->prepareFilters('category.name');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                Category::class,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "category.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByUnknownAssociationConfigRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);

        $this->prepareFilters('category1.name');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "category1.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByUnknownAssociationRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);

        $this->prepareFilters('category1.name');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "category1.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByAssociationRequestedButForNotManageableEntity(): void
    {
        $this->notManageableClassNames = [User::class];

        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);

        $this->prepareFilters('category.name');

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Sorting by "category.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }
}
