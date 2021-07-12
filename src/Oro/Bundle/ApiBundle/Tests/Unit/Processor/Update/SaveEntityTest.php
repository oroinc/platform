<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Processor\Update\SaveEntity;
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

    public function testProcessWhenEntityAlreadySaved()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->flushDataHandler->expects(self::never())
            ->method('flushData');

        $this->context->setProcessed(SaveEntity::OPERATION_NAME);
        $this->context->setResult(new \stdClass());
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

    public function testProcessForManageableEntity()
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);

        $this->flushDataHandler->expects(self::once())
            ->method('flushData')
            ->with(self::identicalTo($em), self::isInstanceOf(FlushDataHandlerContext::class))
            ->willReturnCallback(function (EntityManagerInterface $em, FlushDataHandlerContext $context) {
                self::assertSame($this->context, $context->getEntityContexts()[0]);
                self::assertSame($this->context->getSharedData(), $context->getSharedData());
            });

        $this->context->setResult($entity);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(SaveEntity::OPERATION_NAME));
    }
}
