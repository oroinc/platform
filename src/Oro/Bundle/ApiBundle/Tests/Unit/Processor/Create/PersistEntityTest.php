<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\PersistEntity;
use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class PersistEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var PersistEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new PersistEntity($this->doctrineHelper);
    }

    public function testProcessWhenEntityAlreadySaved()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setProcessed(SaveEntity::OPERATION_NAME);
        $this->context->setResult(new \stdClass());
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForNotSupportedEntity()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

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

        $em->expects(self::never())
            ->method('persist');

        $this->context->setResult($entity);
        $this->context->setMetadata(null);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }

    public function testProcessForManageableEntity()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));

        $this->context->setResult($entity);
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }
}
