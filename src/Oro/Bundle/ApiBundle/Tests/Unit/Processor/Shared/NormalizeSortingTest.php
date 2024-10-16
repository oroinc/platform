<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeSorting;
use Oro\Bundle\ApiBundle\Processor\Shared\Provider\AssociationSortersProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeSortingTest extends GetListProcessorOrmRelatedTestCase
{
    private NormalizeSorting $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->context->setAction(ApiAction::GET_LIST);

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->processor = new NormalizeSorting(
            $this->doctrineHelper,
            new AssociationSortersProvider($this->doctrineHelper, $this->configProvider)
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

    private function prepareOrderingCriteria(array $orderBy): void
    {
        $criteria = new Criteria();
        if ($orderBy) {
            $criteria->orderBy($orderBy);
        }
        $this->context->setCriteria($criteria);
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

        $this->prepareOrderingCriteria(['id' => 'DESC']);

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            ['id' => 'DESC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenNoSorters(): void
    {
        $sortersConfig = $this->getSortersConfig();

        $this->prepareOrderingCriteria(['id' => 'DESC']);

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            ['id' => 'DESC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByNotAllowedFieldRequested(): void
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded();

        $this->prepareOrderingCriteria(['id' => 'DESC']);

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            ['id' => 'DESC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortBySeveralNotAllowedFieldRequested(): void
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded();

        $this->prepareOrderingCriteria(['id' => 'ASC', 'label' => 'DESC']);

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            ['id' => 'ASC', 'label' => 'DESC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByAllowedFieldRequested(): void
    {
        $sortersConfig = $this->getSortersConfig(['id']);

        $this->prepareOrderingCriteria(['id' => 'DESC']);

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        self::assertEquals(
            ['id' => 'DESC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByAllowedRenamedFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name1']);
        $primaryEntityConfig->getField('name1')->setPropertyPath('name');
        $primarySortersConfig = $this->getSortersConfig(['name1']);
        $primarySortersConfig->getField('name1')->setPropertyPath('name');

        $this->prepareOrderingCriteria(['name1' => 'ASC']);

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfSorters($primarySortersConfig);

        $this->processor->process($this->context);

        self::assertEquals(
            ['name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByAllowedAssociationFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareOrderingCriteria(['category.name' => 'ASC']);

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
            ['category.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByAllowedRenamedAssociationRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareOrderingCriteria(['category1.name' => 'ASC']);

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
            ['category.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByAllowedRenamedAssociationAndRenamedRelatedFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');

        $categoryConfig = $this->getConfig(['name1'], ['name1']);
        $categoryConfig->getDefinition()->getField('name1')->setPropertyPath('name');
        $categoryConfig->getSorters()->getField('name1')->setPropertyPath('name');

        $this->prepareOrderingCriteria(['category1.name1' => 'ASC']);

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
            ['category.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByAllowedAssociationFieldRequestedForModelInheritedFromManageableEntity(): void
    {
        $this->notManageableClassNames = [UserProfile::class];

        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareOrderingCriteria(['category.name' => 'ASC']);

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

        self::assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByNotAllowedAssociationFieldRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['id', 'name'], ['id']);

        $this->prepareOrderingCriteria(['category.name' => 'ASC']);

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
            ['category.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByUnknownAssociationConfigRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);

        $this->prepareOrderingCriteria(['category1.name' => 'ASC']);

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->processor->process($this->context);

        self::assertEquals(
            ['category1.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByUnknownAssociationRequested(): void
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);

        $this->prepareOrderingCriteria(['category1.name' => 'ASC']);

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->processor->process($this->context);

        self::assertEquals(
            ['category1.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }

    public function testProcessWhenSortByAssociationRequestedButForNotManageableEntity(): void
    {
        $this->notManageableClassNames = [User::class];

        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);

        $this->prepareOrderingCriteria(['category.name' => 'ASC']);

        $this->context->setClassName(User::class);
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->processor->process($this->context);

        self::assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getCriteria()->getOrderings()
        );
    }
}
