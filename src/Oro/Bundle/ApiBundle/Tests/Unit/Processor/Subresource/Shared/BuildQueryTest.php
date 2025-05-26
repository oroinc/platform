<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildQuery;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class BuildQueryTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    private BuildQuery $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new BuildQuery($this->doctrineHelper);
    }

    public function testProcessWhenQueryIsAlreadyBuilt(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity(): void
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    public function testProcessManageableEntity(): void
    {
        $className = Entity\User::class;

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->context->setAssociationName('testAssociation');
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
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->context->setAssociationName('testAssociation');
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
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    public function testProcessManageableEntityAndComputedAssociation(): void
    {
        $className = Entity\User::class;

        $associationQuery = $this->doctrineHelper
            ->getEntityRepositoryForClass($className)
            ->createQueryBuilder('r')
            ->innerJoin('e.owner', 'e');

        $parentConfig = new EntityDefinitionConfig();
        $associationField = $parentConfig->addField('testAssociation');
        $associationField->setTargetClass($className);
        $associationField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationField->setAssociationQuery($associationQuery);

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertEquals(
            sprintf('SELECT r FROM %s r INNER JOIN e.owner e', $className),
            $query->getDQL()
        );
        self::assertNotSame($associationQuery, $query);
    }

    public function testProcessManageableEntityAndComputedAssociationWithoutAssociationQuery(): void
    {
        $className = Entity\User::class;

        $parentConfig = new EntityDefinitionConfig();
        $associationField = $parentConfig->addField('testAssociation');
        $associationField->setTargetClass($className);
        $associationField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasQuery());
    }

    public function testProcessManageableEntityAndNotComputedAssociation(): void
    {
        $className = Entity\User::class;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField('testAssociation');

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertEquals(
            sprintf('SELECT e FROM %s e', $className),
            $query->getDQL()
        );
    }
}
