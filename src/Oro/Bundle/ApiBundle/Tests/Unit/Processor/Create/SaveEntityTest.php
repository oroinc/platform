<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SaveEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FlushDataHandlerInterface */
    private $flushDataHandler;

    /** @var SaveEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->flushDataHandler = $this->createMock(FlushDataHandlerInterface::class);

        $this->processor = new SaveEntity($this->doctrineHelper, $this->flushDataHandler);
    }

    private function expectsFlushData(EntityManagerInterface $em, object $entity): void
    {
        $this->flushDataHandler->expects(self::once())
            ->method('flushData')
            ->with(self::identicalTo($em), self::isInstanceOf(FlushDataHandlerContext::class))
            ->willReturnCallback(function (EntityManagerInterface $em, FlushDataHandlerContext $context) {
                self::assertSame($this->context, $context->getEntityContexts()[0]);
                self::assertSame($this->context->getSharedData(), $context->getSharedData());
            });
    }

    public function testProcessWhenEntityAlreadySaved()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setProcessed(SaveEntity::OPERATION_NAME);
        $this->context->setResult(new \stdClass());
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForNotSupportedEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn(null);

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setResult($entity);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableEntityButNoApiMetadata()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setResult($entity);
        $this->context->setMetadata(null);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableEntityWithSingleId()
    {
        $entity = new \stdClass();
        $entityId = 123;

        $em = $this->createMock(EntityManager::class);

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $em->expects(self::never())
            ->method('getClassMetadata');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $this->expectsFlushData($em, $entity);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
        self::assertTrue($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableEntityWithCompositeId()
    {
        $entity = new \stdClass();
        $entityId = ['id1' => 1, 'id2' => 2];

        $em = $this->createMock(EntityManager::class);

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $this->expectsFlushData($em, $entity);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals($entityId, $this->context->getId());
        self::assertTrue($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableEntityWhenIdWasNotGenerated()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('getIdentifierValue')
            ->with(self::identicalTo($entity))
            ->willReturn(null);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $this->expectsFlushData($em, $entity);

        $this->context->setResult($entity);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
        self::assertTrue($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessWhenEntityAlreadyExists()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);
        $exception = $this->createMock(UniqueConstraintViolationException::class);

        $metadata = $this->createMock(EntityMetadata::class);

        $em->expects(self::never())
            ->method('getClassMetadata');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $this->flushDataHandler->expects(self::once())
            ->method('flushData')
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
        self::assertTrue($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }
}
