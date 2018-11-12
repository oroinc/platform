<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\CreateEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class CreateEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityLoader */
    private $entityLoader;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityInstantiator */
    private $entityInstantiator;

    /** @var CreateEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(EntityLoader::class);
        $this->entityInstantiator = $this->createMock(EntityInstantiator::class);

        $this->processor = new CreateEntity(
            $this->doctrineHelper,
            $this->entityLoader,
            $this->entityInstantiator
        );
    }

    public function testProcessWithoutEntityId()
    {
        $entityClass = Entity\Product::class;
        $entity = new $entityClass();
        $config = new EntityDefinitionConfig();

        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForNotManageableEntity()
    {
        $entityClass = Entity\Product::class;
        $entity = new $entityClass();
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId(123);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForEntityWithIdGenerator()
    {
        $entityClass = Entity\Product::class;
        $entity = new $entityClass();
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(true);

        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId(123);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForEntityWithoutIdGeneratorAndEntityDoesNotExist()
    {
        $entityClass = Entity\Product::class;
        $entityId = 123;
        $entity = new $entityClass();
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(false);

        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($entityClass, $entityId, self::identicalTo($metadata))
            ->willReturn(null);
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForEntityWithoutIdGeneratorAndEntityAlreadyExists()
    {
        $entityClass = Entity\Product::class;
        $entityId = 123;
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(false);

        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($entityClass, $entityId, self::identicalTo($metadata))
            ->willReturn(new Entity\Product());
        $this->entityInstantiator->expects(self::never())
            ->method('instantiate');

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [Error::createConflictValidationError('The entity already exists')],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenEntityIsAlreadyCreated()
    {
        $entityClass = Entity\Product::class;
        $entity = new $entityClass();
        $config = new EntityDefinitionConfig();

        $this->entityInstantiator->expects(self::never())
            ->method('instantiate');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForApiResourceBasedOnManageableEntity()
    {
        $entityClass = Entity\UserProfile::class;
        $parentResourceClass = Entity\User::class;
        $entity = new $parentResourceClass();
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(true);

        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->willReturnMap([
                [$entityClass, false],
                [$parentResourceClass, true]
            ]);
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($parentResourceClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId(123);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }
}
