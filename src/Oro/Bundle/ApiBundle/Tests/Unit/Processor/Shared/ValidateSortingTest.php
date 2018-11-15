<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateSorting;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\TestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class ValidateSortingTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var ValidateSorting */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context->setAction('get_list');

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->processor = new ValidateSorting(
            $this->doctrineHelper,
            $this->configProvider,
            new FilterNamesRegistry([[$filterNames, null]], new RequestExpressionMatcher())
        );
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertSame($query, $this->context->getQuery());
    }

    public function testProcessWhenSortByExcludedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['id']);
        $sortersConfig->getField('id')->setExcluded(true);

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByExcludedFieldRequestedAndSortFilterHasSourceKey()
    {
        $sortersConfig = $this->getSortersConfig(['id']);
        $sortersConfig->getField('id')->setExcluded(true);

        $this->prepareFilters();
        $sortFilterValue = $this->context->getFilterValues()->get('sort');
        $sortFilterValue->setSource(
            FilterValue::createFromSource('sortFilterSourceKey', $sortFilterValue->getPath(), '')
        );

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sortFilterSourceKey'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenNoSorters()
    {
        $sortersConfig = $this->getSortersConfig();

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByNotAllowedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded(true);

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortBySeveralNotAllowedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded(true);

        $this->prepareFilters('id,-label');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id, label" fields are not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByAllowedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['id']);

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByAllowedRenamedFieldRequested()
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
        self::assertEquals(
            ['name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByAllowedAssociationFieldRequested()
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
        self::assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByAllowedRenamedAssociationRequested()
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
        self::assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByAllowedRenamedAssociationAndRenamedRelatedFieldRequested()
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
        self::assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByAllowedAssociationFieldRequestedForModelInheritedFromManageableEntity()
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
        self::assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByNotAllowedAssociationFieldRequested()
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
                Error::createValidationError('sort constraint', 'Sorting by "category.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByUnknownAssociationConfigRequested()
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
                Error::createValidationError('sort constraint', 'Sorting by "category1.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByUnknownAssociationRequested()
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
                Error::createValidationError('sort constraint', 'Sorting by "category1.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByAssociationRequestedButForNotManageableEntity()
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
                Error::createValidationError('sort constraint', 'Sorting by "category.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @param string $sortBy
     */
    private function prepareFilters($sortBy = '-id')
    {
        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('sort', new FilterValue('sort', $sortBy));

        // emulate sort normalizer
        $orderBy = [];
        $items = explode(',', $sortBy);
        foreach ($items as $item) {
            $item = trim($item);
            if (0 === strpos($item, '-')) {
                $orderBy[substr($item, 1)] = 'DESC';
            } else {
                $orderBy[$item] = 'ASC';
            }
        }
        $filterValues->get('sort')->setValue($orderBy);

        $this->context->setFilterValues($filterValues);
        $this->context->getFilters()->add('sort', new SortFilter(DataType::ORDER_BY));
    }

    /**
     * @param string[] $fields
     * @param string[] $sortFields
     *
     * @return Config
     */
    private function getConfig(array $fields = [], array $sortFields = [])
    {
        $config = new Config();
        $config->setDefinition($this->getEntityDefinitionConfig($fields));
        $config->setSorters($this->getSortersConfig($sortFields));

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
     * @param string[] $fields
     *
     * @return SortersConfig
     */
    private function getSortersConfig(array $fields = [])
    {
        $config = new SortersConfig();
        foreach ($fields as $field) {
            $config->addField($field);
        }

        return $config;
    }
}
