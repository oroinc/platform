<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SaveEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var SaveEntity */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new SaveEntity($this->doctrineHelper);
    }

    public function testProcessWhenNoEntity()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->processor->process($this->context);
    }

    public function testProcessForNotSupportedEntity()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn(null);

        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntityWithSingleId()
    {
        $entity = new \stdClass();
        $entityId = 123;

        $metadata = $this->createMock(EntityMetadata::class);
        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);
        $metadata->expects($this->once())
            ->method('getIdentifierValue')
            ->with($this->identicalTo($entity))
            ->willReturn($entityId);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->with(null);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $this->assertEquals($entityId, $this->context->getId());
    }

    public function testProcessForManageableEntityWithCompositeId()
    {
        $entity = new \stdClass();
        $entityId = ['id1' => 1, 'id2' => 2];

        $metadata = $this->createMock(EntityMetadata::class);
        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);
        $metadata->expects($this->once())
            ->method('getIdentifierValue')
            ->with($this->identicalTo($entity))
            ->willReturn($entityId);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->with(null);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $this->assertEquals($entityId, $this->context->getId());
    }

    public function testProcessForManageableEntityWhenIdWasNotGenerated()
    {
        $entity = new \stdClass();

        $metadata = $this->createMock(EntityMetadata::class);
        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);
        $metadata->expects($this->once())
            ->method('getIdentifierValue')
            ->with($this->identicalTo($entity))
            ->willReturn(null);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->with(null);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getId());
    }

    public function testProcessWhenEntityAlreadyExists()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);
        $exception = $this->createMock(UniqueConstraintViolationException::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);
        $em->expects($this->never())
            ->method('getClassMetadata');

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->context->setResult($entity);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getId());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::CONFLICT, 'The entity already exists')
                    ->setStatusCode(Response::HTTP_CONFLICT)
                    ->setInnerException($exception)
            ],
            $this->context->getErrors()
        );
    }
}
