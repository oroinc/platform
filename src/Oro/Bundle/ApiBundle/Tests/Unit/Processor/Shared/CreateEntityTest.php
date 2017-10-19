<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\CreateEntity;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class CreateEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityLoader */
    protected $entityLoader;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInstantiator */
    protected $entityInstantiator;

    /** @var CreateEntity */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function setUp()
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

        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForNotManageableEntity()
    {
        $entityClass = Entity\Product::class;
        $entity = new $entityClass();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId(123);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForEntityWithIdGenerator()
    {
        $entityClass = Entity\Product::class;
        $entity = new $entityClass();
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(true);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($entityClass)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId(123);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForEntityWithoutIdGeneratorAndEntityDoesNotExist()
    {
        $entityClass = Entity\Product::class;
        $entityId = 123;
        $entity = new $entityClass();
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(false);

        $this->doctrineHelper->expects(self::once())
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
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForEntityWithoutIdGeneratorAndEntityAlreadyExists()
    {
        $entityClass = Entity\Product::class;
        $entityId = 123;
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(false);

        $this->doctrineHelper->expects(self::once())
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
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::CONFLICT, 'The entity already exists')
                    ->setStatusCode(Response::HTTP_CONFLICT)
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenEntityIsAlreadyCreated()
    {
        $entityClass = Entity\Product::class;
        $entity = new $entityClass();

        $this->entityInstantiator->expects(self::never())
            ->method('instantiate');

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }
}
