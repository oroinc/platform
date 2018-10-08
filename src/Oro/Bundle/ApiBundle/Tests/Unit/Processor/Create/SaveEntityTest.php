<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SaveEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var SaveEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new SaveEntity($this->doctrineHelper);
    }

    public function testProcessWhenNoEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->processor->process($this->context);
    }

    public function testProcessForNotSupportedEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
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

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));
        $em->expects(self::once())
            ->method('flush')
            ->with(null);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
    }

    public function testProcessForManageableEntityWithCompositeId()
    {
        $entity = new \stdClass();
        $entityId = ['id1' => 1, 'id2' => 2];

        $metadata = $this->createMock(EntityMetadata::class);
        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));
        $em->expects(self::once())
            ->method('flush')
            ->with(null);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
    }

    public function testProcessForManageableEntityWhenIdWasNotGenerated()
    {
        $entity = new \stdClass();

        $metadata = $this->createMock(EntityMetadata::class);
        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn(null);

        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));
        $em->expects(self::once())
            ->method('flush')
            ->with(null);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
    }

    public function testProcessWhenEntityAlreadyExists()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);
        $exception = $this->createMock(UniqueConstraintViolationException::class);

        $metadata = $this->createMock(EntityMetadata::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);
        $em->expects(self::never())
            ->method('getClassMetadata');

        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));
        $em->expects(self::once())
            ->method('flush')
            ->willThrowException($exception);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
        self::assertEquals(
            [
                Error::createConflictValidationError('The entity already exists')
                    ->setInnerException($exception)
            ],
            $this->context->getErrors()
        );
    }
}
