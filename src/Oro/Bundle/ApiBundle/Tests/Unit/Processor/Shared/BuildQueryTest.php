<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\LoadEntityIdsQueryInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildQuery;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntitiesByEntitySerializer;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class BuildQueryTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var FilterNamesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $filterNamesRegistry;

    /** @var BuildQuery */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filterNamesRegistry = $this->createMock(FilterNamesRegistry::class);

        $this->processor = new BuildQuery($this->doctrineHelper, $this->filterNamesRegistry);
    }

    public function testProcessForNotManageableEntity(): void
    {
        $className = 'Test\Class';

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    public function testProcessWhenQueryIsAlreadyBuilt(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $this->context->setQuery($qb);
        $this->context->setClassName(Entity\User::class);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessManageableEntity(): void
    {
        $className = Entity\User::class;

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertEquals(
            sprintf('SELECT e FROM %s e', $className),
            $query->getDQL()
        );
    }

    public function testProcessForResourceBasedOnManageableEntity(): void
    {
        $entityClass = Entity\UserProfile::class;
        $parentResourceClass = Entity\User::class;
        $this->notManageableClassNames = [$entityClass];

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertEquals(
            sprintf('SELECT e FROM %s e', $parentResourceClass),
            $query->getDQL()
        );
    }

    public function testProcessForResourceBasedOnNotManageableEntity(): void
    {
        $entityClass = 'Test\Class';
        $parentResourceClass = 'Test\ParentClass';
        $this->notManageableClassNames = [$entityClass, $parentResourceClass];

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    public function testProcessManageableEntityAndWithLoadEntityIdsQuery(): void
    {
        $className = Entity\User::class;

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $loadEntityIdsQuery = $this->createMock(LoadEntityIdsQueryInterface::class);
        $loadEntityIdsQuery->expects(self::once())
            ->method('getEntityIds')
            ->willReturn([1, 2]);
        $loadEntityIdsQuery->expects(self::once())
            ->method('getEntityTotalCount')
            ->willReturn(5);

        $this->filterNamesRegistry->expects(self::never())
            ->method('getFilterNames');

        $this->context->setQuery($loadEntityIdsQuery);
        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertInstanceOf(QueryBuilder::class, $query);
        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id IN (:ids)', $className),
            $query->getDQL()
        );
        self::assertEquals([1, 2], $query->getParameter('ids')->getValue());

        self::assertIsCallable($this->context->getTotalCountCallback());
        self::assertEquals(5, $this->context->getTotalCountCallback()());

        self::assertEquals([1, 2], $this->context->get(LoadEntitiesByEntitySerializer::ENTITY_IDS));

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessManageableEntityAndWithLoadEntityIdsQueryAndInvalidSorting(): void
    {
        $className = Entity\User::class;

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $loadEntityIdsQuery = $this->createMock(LoadEntityIdsQueryInterface::class);
        $loadEntityIdsQuery->expects(self::once())
            ->method('getEntityIds')
            ->willThrowException(new InvalidSorterException('Invalid sorting'));

        $filterNames = $this->createMock(FilterNames::class);
        $this->filterNamesRegistry->expects(self::once())
            ->method('getFilterNames')
            ->with($this->context->getRequestType())
            ->willReturn($filterNames);
        $filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort_filter');

        $this->context->setQuery($loadEntityIdsQuery);
        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->context->getFilterValues()->set('sort_filter', FilterValue::createFromSource('sort', 'sort', 'test'));
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        self::assertSame($loadEntityIdsQuery, $this->context->getQuery());
        self::assertNull($this->context->getTotalCountCallback());
        self::assertFalse($this->context->has(LoadEntitiesByEntitySerializer::ENTITY_IDS));

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Invalid sorting')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessManageableEntityAndWithLoadEntityIdsQueryAndInvalidSortingAndNoSortFilterValue(): void
    {
        $className = Entity\User::class;

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $loadEntityIdsQuery = $this->createMock(LoadEntityIdsQueryInterface::class);
        $loadEntityIdsQuery->expects(self::once())
            ->method('getEntityIds')
            ->willThrowException(new InvalidSorterException('Invalid sorting'));

        $filterNames = $this->createMock(FilterNames::class);
        $this->filterNamesRegistry->expects(self::once())
            ->method('getFilterNames')
            ->with($this->context->getRequestType())
            ->willReturn($filterNames);
        $filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort_filter');

        $this->context->setQuery($loadEntityIdsQuery);
        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        self::assertSame($loadEntityIdsQuery, $this->context->getQuery());
        self::assertNull($this->context->getTotalCountCallback());
        self::assertFalse($this->context->has(LoadEntitiesByEntitySerializer::ENTITY_IDS));

        self::assertEquals(
            [
                Error::createValidationError(Constraint::SORT, 'Invalid sorting')
                    ->setSource(ErrorSource::createByParameter('sort_filter'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessManageableEntityWithoutIdentifierAndWithLoadEntityIdsQuery(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The entity must have one identifier field.');

        $className = Entity\User::class;

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames([]);

        $loadEntityIdsQuery = $this->createMock(LoadEntityIdsQueryInterface::class);
        $loadEntityIdsQuery->expects(self::never())
            ->method('getEntityIds');

        $this->context->setQuery($loadEntityIdsQuery);
        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($loadEntityIdsQuery, $this->context->getQuery());
        self::assertNull($this->context->getTotalCountCallback());
        self::assertFalse($this->context->has(LoadEntitiesByEntitySerializer::ENTITY_IDS));
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessManageableEntityWithCompositeIdentifierAndWithLoadEntityIdsQuery(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The entity must have one identifier field.');

        $className = Entity\User::class;

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id1', 'id2']);

        $loadEntityIdsQuery = $this->createMock(LoadEntityIdsQueryInterface::class);
        $loadEntityIdsQuery->expects(self::never())
            ->method('getEntityIds');

        $this->context->setQuery($loadEntityIdsQuery);
        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($loadEntityIdsQuery, $this->context->getQuery());
        self::assertNull($this->context->getTotalCountCallback());
        self::assertFalse($this->context->has(LoadEntitiesByEntitySerializer::ENTITY_IDS));
        self::assertFalse($this->context->hasErrors());
    }
}
