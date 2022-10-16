<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildSingleItemQuery;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;

class BuildSingleItemQueryTest extends GetProcessorOrmRelatedTestCase
{
    /** @var BuildSingleItemQuery */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new BuildSingleItemQuery(
            $this->doctrineHelper,
            new EntityIdHelper()
        );
    }

    public function testProcessWhenDataAlreadyExist()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    public function testProcessForManageableEntityWithSingleId()
    {
        $entityClass = Entity\User::class;
        $entityId = 123;
        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'));

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id', $entityClass),
            $query->getDQL()
        );
        /** @var Parameter $parameter */
        $parameter = $query->getParameters()->first();
        self::assertEquals('id', $parameter->getName());
        self::assertEquals($entityId, $parameter->getValue());
    }

    public function testProcessForManageableEntityWithCompositeId()
    {
        $entityClass = Entity\CompositeKeyEntity::class;
        $entityId = ['id' => 123, 'title' => 'test'];
        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id', 'title']);
        $metadata->addField(new FieldMetadata('id'));
        $metadata->addField(new FieldMetadata('title'));

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id1 AND e.title = :id2', $entityClass),
            $query->getDQL()
        );
        /** @var Parameter $parameter */
        $parameters = $query->getParameters();
        $idParameter = $parameters[0];
        self::assertEquals('id1', $idParameter->getName());
        self::assertEquals($entityId['id'], $idParameter->getValue());
        $titleParameter = $parameters[1];
        self::assertEquals('id2', $titleParameter->getName());
        self::assertEquals($entityId['title'], $titleParameter->getValue());
    }

    public function testProcessForResourceBasedOnManageableEntity()
    {
        $entityClass = Entity\UserProfile::class;
        $parentResourceClass = Entity\User::class;
        $entityId = 123;
        $this->notManageableClassNames = [$entityClass];
        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'));

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id', $parentResourceClass),
            $query->getDQL()
        );
        /** @var Parameter $parameter */
        $parameter = $query->getParameters()->first();
        self::assertEquals('id', $parameter->getName());
        self::assertEquals($entityId, $parameter->getValue());
    }

    public function testProcessForResourceBasedOnNotManageableEntity()
    {
        $entityClass = 'Test\Class';
        $parentResourceClass = 'Test\ParentClass';
        $this->notManageableClassNames = [$entityClass, $parentResourceClass];
        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'));

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }
}
